<?php

use App\Libraries\PrivacyAudit;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * M-2: pruebas de comportamiento (no de cadenas de texto) para el sello de
 * integridad de la bitacora de auditoria y para los triggers append-only.
 */
final class PrivacyAuditTest extends CIUnitTestCase
{
    private function row(array $overrides = []): array
    {
        return array_merge([
            'cliente_id' => 1, 'usuario_id' => 5, 'actor_tipo' => 'usuario',
            'accion' => 'radicar', 'entidad' => 'solicitud', 'entidad_id' => 10,
            'antes_json' => null, 'despues_json' => '{"estado":"recibida"}',
            'ip' => '127.0.0.1', 'user_agent' => 'agent', 'created_at' => '2026-07-13 10:00:00',
        ], $overrides);
    }

    public function testCanonicalHashIsDeterministic(): void
    {
        $row = $this->row();
        $this->assertSame(
            PrivacyAudit::chain('', PrivacyAudit::canonical($row)),
            PrivacyAudit::chain('', PrivacyAudit::canonical($row))
        );
    }

    public function testChainLinksEachRecordToThePrevious(): void
    {
        $h1 = PrivacyAudit::chain('', PrivacyAudit::canonical($this->row(['accion' => 'radicar'])));
        $h2 = PrivacyAudit::chain($h1, PrivacyAudit::canonical($this->row(['accion' => 'cerrar'])));

        // El segundo eslabon depende del hash del primero: si se elimina o reordena
        // el primero, el hash esperado del segundo cambia (evidencia de manipulacion).
        $h2WithoutPrevious = PrivacyAudit::chain('', PrivacyAudit::canonical($this->row(['accion' => 'cerrar'])));
        $this->assertNotSame($h2, $h2WithoutPrevious);
    }

    public function testTamperingAnyFieldBreaksTheSeal(): void
    {
        $original = PrivacyAudit::chain('prev', PrivacyAudit::canonical($this->row(['accion' => 'exportar'])));
        foreach ([['accion' => 'ocultar'], ['cliente_id' => 2], ['despues_json' => '{"estado":"borrada"}'], ['created_at' => '2000-01-01 00:00:00']] as $tamper) {
            $tampered = PrivacyAudit::chain('prev', PrivacyAudit::canonical($this->row(array_merge(['accion' => 'exportar'], $tamper))));
            $this->assertNotSame($original, $tampered, 'Alterar ' . json_encode($tamper) . ' debe romper el sello.');
        }
    }

    public function testAuditTrailIsAppendOnlyOnMysql(): void
    {
        $db = \Config\Database::connect('default');
        if ($db->DBDriver !== 'MySQLi' || ! $db->tableExists('dp_auditoria') || ! $db->fieldExists('evento_hash', 'dp_auditoria')) {
            $this->markTestSkipped('Requiere MySQL/MariaDB con la migracion 000018 aplicada (el suite corre en SQLite).');
        }
        $cliente = $db->table('clientes')->select('id')->orderBy('id', 'ASC')->limit(1)->get()->getRowArray();
        if (! $cliente) {
            $this->markTestSkipped('No hay clientes de referencia para la prueba de integridad.');
        }

        // Cada operacion se prueba en su propio ciclo insert+rollback aislado: la
        // primera consulta bloqueada por el trigger aborta su statement, por lo que
        // reutilizar la misma fila para la segunda daria un falso negativo.
        $this->assertTrue(
            $this->blocksWriteOnProbe($db, (int) $cliente['id'], 'UPDATE dp_auditoria SET accion = ? WHERE id = ?', ['tampered']),
            'El UPDATE sobre dp_auditoria debe ser rechazado por el trigger append-only.'
        );
        $this->assertTrue(
            $this->blocksWriteOnProbe($db, (int) $cliente['id'], 'DELETE FROM dp_auditoria WHERE id = ?', []),
            'El DELETE sobre dp_auditoria debe ser rechazado por el trigger append-only.'
        );
    }

    /**
     * Inserta una fila de prueba en una transaccion, ejecuta $sql (con el id de la
     * fila como ultimo parametro) y devuelve si el trigger la rechazo. El rollback
     * deshace el INSERT sin ejecutar DELETE, por lo que no deja residuo.
     */
    private function blocksWriteOnProbe(\CodeIgniter\Database\BaseConnection $db, int $clienteId, string $sql, array $params): bool
    {
        $db->transException(true);
        $db->transBegin();
        $blocked = false;
        try {
            $db->table('dp_auditoria')->insert([
                'cliente_id' => $clienteId, 'actor_tipo' => 'sistema', 'accion' => 'probe_test',
                'entidad' => 'test', 'ip' => '127.0.0.1', 'created_at' => date('Y-m-d H:i:s'),
                'hash_anterior' => '', 'evento_hash' => hash('sha256', 'probe'),
            ]);
            $id = (int) $db->insertID();
            try {
                $db->query($sql, array_merge($params, [$id]));
            } catch (\Throwable $e) {
                $blocked = true;
            }
        } finally {
            $db->transRollback();
            $db->transException(false);
        }
        return $blocked;
    }
}
