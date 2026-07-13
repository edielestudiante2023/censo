<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A-3: agrega integridad referencial (llaves foraneas) al modulo dp_*, que hasta
 * ahora dependia exclusivamente de la capa de aplicacion para el aislamiento por
 * cliente y para evitar huerfanos. Las FK usan ON DELETE RESTRICT (retencion y
 * backstop anti-huerfano; la baja de cliente es logica/soft-delete) y ON UPDATE
 * CASCADE. La migracion alinea los tipos (cliente_id pasa a coincidir con
 * clientes.id) y aborta si detecta huerfanos: una migracion nunca debe borrar
 * ni reasignar evidencia para conseguir que una restriccion pueda crearse.
 */
class AddPrivacyForeignKeys extends Migration
{
    /** @var list<array{0:string,1:string,2:string,3:string,4:string}> child, col, parent, parentCol, onDelete */
    private array $map = [
        // Aislamiento por cliente
        ['dp_programas', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_bases_datos', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_finalidades', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_terceros', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_documentos', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_consentimientos', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_exclusiones', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_solicitudes', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_notificaciones', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_ai_runs', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_auditoria', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_aviso_variantes', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_aviso_publicaciones', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_consentimiento_eventos', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_consentimiento_verificaciones', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_finalidad_datos_sensibles', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_restauraciones_privacidad', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_incidentes_privacidad', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_asignaciones_seguridad', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_controles_seguridad', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_usuario_privacidad', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_compromisos_confidencialidad', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_subencargados', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_acuerdos_encargado', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_encargado_instrucciones', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        ['dp_encargado_certificaciones', 'cliente_id', 'clientes', 'id', 'RESTRICT'],
        // Integridad intra-modulo
        ['dp_finalidades', 'base_id', 'dp_bases_datos', 'id', 'RESTRICT'],
        ['dp_finalidad_datos_sensibles', 'base_id', 'dp_bases_datos', 'id', 'RESTRICT'],
        ['dp_finalidad_datos_sensibles', 'finalidad_id', 'dp_finalidades', 'id', 'RESTRICT'],
        ['dp_solicitud_bases', 'solicitud_id', 'dp_solicitudes', 'id', 'RESTRICT'],
        ['dp_solicitud_bases', 'base_id', 'dp_bases_datos', 'id', 'RESTRICT'],
        ['dp_solicitud_terceros', 'solicitud_id', 'dp_solicitudes', 'id', 'RESTRICT'],
        ['dp_solicitud_terceros', 'tercero_id', 'dp_terceros', 'id', 'RESTRICT'],
        ['dp_solicitud_eventos', 'solicitud_id', 'dp_solicitudes', 'id', 'RESTRICT'],
        ['dp_consentimientos', 'documento_id', 'dp_documentos', 'id', 'RESTRICT'],
        ['dp_consentimiento_eventos', 'consentimiento_id', 'dp_consentimientos', 'id', 'RESTRICT'],
        ['dp_notificaciones', 'solicitud_id', 'dp_solicitudes', 'id', 'RESTRICT'],
        ['dp_notificacion_eventos', 'notificacion_id', 'dp_notificaciones', 'id', 'RESTRICT'],
        ['dp_aviso_variantes', 'documento_id', 'dp_documentos', 'id', 'RESTRICT'],
        ['dp_aviso_publicaciones', 'variante_id', 'dp_aviso_variantes', 'id', 'RESTRICT'],
        ['dp_incidente_eventos', 'incidente_id', 'dp_incidentes_privacidad', 'id', 'RESTRICT'],
        ['dp_subencargados', 'tercero_id', 'dp_terceros', 'id', 'RESTRICT'],
        ['dp_acuerdos_encargado', 'tercero_id', 'dp_terceros', 'id', 'RESTRICT'],
        ['dp_acuerdos_encargado', 'documento_id', 'dp_documentos', 'id', 'RESTRICT'],
        ['dp_acuerdo_encargado_eventos', 'acuerdo_id', 'dp_acuerdos_encargado', 'id', 'RESTRICT'],
        ['dp_encargado_instrucciones', 'acuerdo_id', 'dp_acuerdos_encargado', 'id', 'RESTRICT'],
        ['dp_encargado_certificaciones', 'acuerdo_id', 'dp_acuerdos_encargado', 'id', 'RESTRICT'],
        ['dp_compromisos_confidencialidad', 'documento_id', 'dp_documentos', 'id', 'RESTRICT'],
        ['dp_compromisos_confidencialidad', 'usuario_id', 'usuarios', 'id', 'RESTRICT'],
        ['dp_compromiso_eventos', 'compromiso_id', 'dp_compromisos_confidencialidad', 'id', 'RESTRICT'],
    ];

    public function up(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return; // Solo MySQL/MariaDB de produccion; el suite corre en SQLite.
        }
        $schema = $this->db->getDatabase();
        foreach ($this->map as [$child, $col, $parent, $parentCol, $onDelete]) {
            $this->addForeignKeySafely($schema, $child, $col, $parent, $parentCol, $onDelete);
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            throw new \RuntimeException("No existe la relacion esperada {$child}.{$col} -> {$parent}.{$parentCol}");
        }
        $schema = $this->db->getDatabase();
        foreach ($this->map as [$child, $col, $parent, $parentCol, $onDelete]) {
            $name = $this->constraintName($child, $col);
            if ($this->constraintExists($schema, $name)) {
                $this->db->query("ALTER TABLE `{$child}` DROP FOREIGN KEY `{$name}`");
            }
        }
    }

    private function addForeignKeySafely(string $schema, string $child, string $col, string $parent, string $parentCol, string $onDelete): void
    {
        if (! $this->db->tableExists($child) || ! $this->db->tableExists($parent) || ! $this->db->fieldExists($col, $child)) {
            return;
        }
        $name = $this->constraintName($child, $col);
        if ($this->constraintExists($schema, $name)) {
            return;
        }
        $this->alignColumnType($schema, $child, $col, $parent, $parentCol);
        $this->assertNoOrphans($child, $col, $parent, $parentCol);
        $this->db->query("ALTER TABLE `{$child}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$col}`) REFERENCES `{$parent}`(`{$parentCol}`) ON DELETE {$onDelete} ON UPDATE CASCADE");
    }

    private function alignColumnType(string $schema, string $child, string $col, string $parent, string $parentCol): void
    {
        $parentType = $this->columnType($schema, $parent, $parentCol);
        $childType = $this->columnType($schema, $child, $col);
        if ($parentType === '' || $childType === '' || strcasecmp($parentType, $childType) === 0) {
            return;
        }
        $nullable = $this->db->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$schema, $child, $col])->getRowArray();
        $null = ($nullable['IS_NULLABLE'] ?? 'YES') === 'YES' ? 'NULL' : 'NOT NULL';
        $this->db->query("ALTER TABLE `{$child}` MODIFY `{$col}` {$parentType} {$null}");
    }

    private function assertNoOrphans(string $child, string $col, string $parent, string $parentCol): void
    {
        $orphans = "`{$col}` IS NOT NULL AND `{$col}` NOT IN (SELECT `{$parentCol}` FROM `{$parent}`)";
        $row = $this->db->query("SELECT COUNT(*) AS total FROM `{$child}` WHERE {$orphans}")->getRowArray();
        if ((int) ($row['total'] ?? 0) > 0) {
            throw new \RuntimeException("Integridad invalida: {$child}.{$col} contiene {$row['total']} referencias huerfanas");
        }
    }

    private function columnType(string $schema, string $table, string $column): string
    {
        $row = $this->db->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$schema, $table, $column])->getRowArray();
        return (string) ($row['COLUMN_TYPE'] ?? '');
    }

    private function constraintExists(string $schema, string $name): bool
    {
        $row = $this->db->query("SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'", [$schema, $name])->getRowArray();
        return (int) ($row['c'] ?? 0) > 0;
    }

    private function constraintName(string $child, string $col): string
    {
        return 'fk_' . $child . '_' . $col;
    }
}
