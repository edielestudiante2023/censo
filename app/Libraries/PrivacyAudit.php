<?php

namespace App\Libraries;

final class PrivacyAudit
{
    public static function record(int $clienteId, string $accion, string $entidad, ?int $entidadId = null, ?array $antes = null, ?array $despues = null, string $actorTipo = 'usuario'): void
    {
        $db = db_connect();
        $lockName = 'dp_audit_' . $clienteId;
        $locked = false;
        $now = date('Y-m-d H:i:s');
        $request = service('request');
        $hasIp = method_exists($request, 'getIPAddress');
        $hasUserAgent = method_exists($request, 'getUserAgent');
        $row = [
            'cliente_id' => $clienteId,
            'usuario_id' => session()->get('user_id') ?: null,
            'actor_tipo' => $actorTipo,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'antes_json' => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'despues_json' => $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip' => $hasIp ? $request->getIPAddress() : '127.0.0.1',
            'user_agent' => $hasUserAgent ? substr((string) $request->getUserAgent(), 0, 500) : 'CLI',
            'created_at' => $now,
        ];

        // Cadena de integridad append-only: cada registro sella el hash del anterior
        // del mismo cliente, de modo que borrar o alterar una fila rompe la cadena y
        // es detectable con verifyChain(). Requiere las columnas de la migracion 000018.
        try {
            if ($db->DBDriver === 'MySQLi') {
                $lock = $db->query('SELECT GET_LOCK(?, 10) AS acquired', [$lockName])->getRowArray();
                if ((int) ($lock['acquired'] ?? 0) !== 1) {
                    throw new \RuntimeException('No fue posible serializar la bitacora de auditoria.');
                }
                $locked = true;
            }
            $db->transException(true)->transBegin();
            if ($db->fieldExists('evento_hash', 'dp_auditoria')) {
                $sql = 'SELECT evento_hash FROM dp_auditoria WHERE cliente_id = ? ORDER BY id DESC LIMIT 1';
                if ($db->DBDriver === 'MySQLi') {
                    $sql .= ' FOR UPDATE';
                }
                $previous = $db->query($sql, [$clienteId])->getRowArray();
                $previousHash = (string) ($previous['evento_hash'] ?? '');
                $row['hash_anterior'] = $previousHash;
                $row['evento_hash'] = self::chain($previousHash, self::canonical($row));
            }
            $db->table('dp_auditoria')->insert((new PrivacyVault())->encryptRow('dp_auditoria', $row));
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        } finally {
            $db->transException(false);
            if ($locked) {
                $db->query('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        }
    }

    /**
     * Serializacion canonica de una fila de auditoria para el calculo del hash.
     * Debe permanecer estable: cualquier cambio invalida cadenas ya selladas.
     */
    public static function canonical(array $row): string
    {
        return (string) json_encode([
            'cliente_id' => (int) $row['cliente_id'],
            'usuario_id' => isset($row['usuario_id']) && $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
            'actor_tipo' => (string) ($row['actor_tipo'] ?? 'usuario'),
            'accion' => (string) $row['accion'],
            'entidad' => (string) $row['entidad'],
            'entidad_id' => isset($row['entidad_id']) && $row['entidad_id'] !== null ? (int) $row['entidad_id'] : null,
            'antes_json' => $row['antes_json'] ?? null,
            'despues_json' => $row['despues_json'] ?? null,
            'ip' => $row['ip'] ?? null,
            'user_agent' => $row['user_agent'] ?? null,
            'created_at' => (string) $row['created_at'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function chain(string $previousHash, string $canonical): string
    {
        return hash('sha256', $previousHash . '|' . $canonical);
    }

    /**
     * Recalcula la cadena de un cliente y devuelve el primer id que no cuadra,
     * o null si la cadena esta integra. Uso probatorio y de verificacion posterior.
     */
    public static function verifyChain(int $clienteId): ?int
    {
        $db = db_connect();
        if (! $db->fieldExists('evento_hash', 'dp_auditoria')) {
            return null;
        }
        $previousHash = '';
        $rows = (new PrivacyVault())->decryptRows('dp_auditoria', $db->table('dp_auditoria')->where('cliente_id', $clienteId)->orderBy('id', 'ASC')->get()->getResultArray());
        foreach ($rows as $row) {
            $expected = self::chain($previousHash, self::canonical($row));
            if (! hash_equals($expected, (string) $row['evento_hash'])) {
                return (int) $row['id'];
            }
            $previousHash = (string) $row['evento_hash'];
        }
        return null;
    }
}
