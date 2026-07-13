<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Removes cascade paths and makes evidence ledgers append-only at the database layer. */
final class HardenPrivacyEvidenceRelations extends Migration
{
    private array $eventTables = [
        'dp_solicitud_eventos', 'dp_consentimiento_eventos', 'dp_notificacion_eventos',
        'dp_incidente_eventos', 'dp_compromiso_eventos', 'dp_acuerdo_encargado_eventos',
    ];

    private array $evidenceRoots = [
        'dp_solicitudes', 'dp_consentimientos', 'dp_notificaciones',
        'dp_incidentes_privacidad', 'dp_compromisos_confidencialidad',
        'dp_acuerdos_encargado', 'dp_documentos',
    ];

    public function up(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        $cascades = $this->db->query(
            "SELECT kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.CONSTRAINT_NAME,
                    kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME
             FROM information_schema.REFERENTIAL_CONSTRAINTS rc
             JOIN information_schema.KEY_COLUMN_USAGE kcu
               ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
              AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
              AND kcu.TABLE_NAME = rc.TABLE_NAME
             WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
               AND rc.DELETE_RULE = 'CASCADE'
               AND LEFT(kcu.TABLE_NAME, 3) = 'dp_'"
        )->getResultArray();

        foreach ($cascades as $fk) {
            foreach ($fk as $value) {
                if (! preg_match('/^[A-Za-z0-9_]+$/', (string) $value)) {
                    throw new \RuntimeException('Metadato de llave foranea no seguro.');
                }
            }
            $this->db->query("ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
            $this->db->query("ALTER TABLE `{$fk['TABLE_NAME']}` ADD CONSTRAINT `{$fk['CONSTRAINT_NAME']}` " .
                "FOREIGN KEY (`{$fk['COLUMN_NAME']}`) REFERENCES `{$fk['REFERENCED_TABLE_NAME']}`(`{$fk['REFERENCED_COLUMN_NAME']}`) " .
                'ON DELETE RESTRICT ON UPDATE CASCADE');
        }

        foreach ($this->eventTables as $table) {
            if (! $this->db->tableExists($table)) {
                throw new \RuntimeException('Falta la tabla de evidencia ' . $table);
            }
            $this->immutableTrigger($table, 'UPDATE');
            $this->immutableTrigger($table, 'DELETE');
        }
        foreach ($this->evidenceRoots as $table) {
            if (! $this->db->tableExists($table)) {
                throw new \RuntimeException('Falta la tabla probatoria ' . $table);
            }
            $this->immutableTrigger($table, 'DELETE');
        }

        $remaining = $this->db->query(
            "SELECT COUNT(*) AS total FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND DELETE_RULE = 'CASCADE' AND LEFT(TABLE_NAME, 3) = 'dp_'"
        )->getRowArray();
        if ((int) ($remaining['total'] ?? 0) !== 0) {
            throw new \RuntimeException('Persisten relaciones CASCADE en el modulo de privacidad.');
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }
        foreach (array_merge($this->eventTables, $this->evidenceRoots) as $table) {
            foreach (['UPDATE', 'DELETE'] as $event) {
                $name = $this->triggerName($table, $event);
                $this->db->query("DROP TRIGGER IF EXISTS `{$name}`");
            }
        }
    }

    private function immutableTrigger(string $table, string $event): void
    {
        $name = $this->triggerName($table, $event);
        $this->db->query("DROP TRIGGER IF EXISTS `{$name}`");
        $this->db->query("CREATE TRIGGER `{$name}` BEFORE {$event} ON `{$table}` FOR EACH ROW " .
            "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Evidencia de privacidad inmutable'");
    }

    private function triggerName(string $table, string $event): string
    {
        return 'trg_' . substr($table, 3, 34) . '_immutable_' . strtolower($event);
    }
}
