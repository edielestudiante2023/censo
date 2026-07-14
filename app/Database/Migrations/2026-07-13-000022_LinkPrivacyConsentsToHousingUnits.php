<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Vincula cada decision residencial con la unidad que el Titular identifico. */
final class LinkPrivacyConsentsToHousingUnits extends Migration
{
    private const FOREIGN_KEY = 'fk_dp_consentimientos_cliente_inmueble';

    public function up(): void
    {
        if (! $this->db->fieldExists('inmueble_id', 'dp_consentimientos')) {
            $this->forge->addColumn('dp_consentimientos', [
                'inmueble_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'cliente_id',
                ],
            ]);
        }

        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        $this->addIndex('inmuebles', 'idx_inmuebles_cliente_id_id', '`cliente_id`, `id`', true);
        $this->addIndex('dp_consentimientos', 'idx_dp_consentimientos_cliente_inmueble', '`cliente_id`, `inmueble_id`');

        if (! $this->constraintExists(self::FOREIGN_KEY)) {
            $this->db->query(
                'ALTER TABLE `dp_consentimientos` ADD CONSTRAINT `' . self::FOREIGN_KEY . '` ' .
                'FOREIGN KEY (`cliente_id`, `inmueble_id`) REFERENCES `inmuebles` (`cliente_id`, `id`) ' .
                'ON DELETE RESTRICT ON UPDATE CASCADE'
            );
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            if ($this->constraintExists(self::FOREIGN_KEY)) {
                $this->db->query('ALTER TABLE `dp_consentimientos` DROP FOREIGN KEY `' . self::FOREIGN_KEY . '`');
            }
            $this->dropIndex('dp_consentimientos', 'idx_dp_consentimientos_cliente_inmueble');
        }

        if ($this->db->fieldExists('inmueble_id', 'dp_consentimientos')) {
            $this->forge->dropColumn('dp_consentimientos', 'inmueble_id');
        }
    }

    private function addIndex(string $table, string $name, string $columns, bool $unique = false): void
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.STATISTICS ' .
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $name]
        )->getRowArray();
        if ((int) ($row['total'] ?? 0) === 0) {
            $kind = $unique ? 'UNIQUE INDEX' : 'INDEX';
            $this->db->query("ALTER TABLE `{$table}` ADD {$kind} `{$name}` ({$columns})");
        }
    }

    private function dropIndex(string $table, string $name): void
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.STATISTICS ' .
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $name]
        )->getRowArray();
        if ((int) ($row['total'] ?? 0) > 0) {
            $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        }
    }

    private function constraintExists(string $name): bool
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$name]
        )->getRowArray();

        return (int) ($row['total'] ?? 0) > 0;
    }
}
