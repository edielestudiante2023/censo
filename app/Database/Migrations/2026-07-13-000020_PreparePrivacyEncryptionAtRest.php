<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\PrivacyEncryption;

final class PreparePrivacyEncryptionAtRest extends Migration
{
    public function up(): void
    {
        /** @var PrivacyEncryption $config */
        $config = config(PrivacyEncryption::class);
        foreach ($config->tables as $table => $spec) {
            if (! $this->db->tableExists($table)) {
                throw new \RuntimeException('Falta la tabla cifrada requerida: ' . $table);
            }
            if ($this->db->DBDriver === 'MySQLi') {
                $this->dropPlaintextIndexes($table, $spec);
            }
            foreach ($spec['blind'] ?? [] as $blind) {
                $column = $blind['col'];
                if (! $this->db->fieldExists($column, $table)) {
                    $this->forge->addColumn($table, [
                        $column => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
                    ]);
                }
                if ($this->db->DBDriver === 'MySQLi') {
                    $index = $this->indexName($table, $column);
                    if (! $this->indexExists($table, $index)) {
                        $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$index}` (`{$column}`)");
                    }
                    if ($this->db->fieldExists('cliente_id', $table)) {
                        $tenantIndex = $this->indexName($table, 'cliente_id_' . $column);
                        if (! $this->indexExists($table, $tenantIndex)) {
                            $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$tenantIndex}` (`cliente_id`, `{$column}`)");
                        }
                    }
                }
            }
            if ($this->db->DBDriver === 'MySQLi') {
                foreach ($spec['encrypt'] as $column) {
                    $this->expandEncryptedColumn($table, $column);
                }
            }
        }

        if (! $this->db->tableExists('dp_cifrado_estado')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tabla' => ['type' => 'VARCHAR', 'constraint' => 100],
                'ultimo_id' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
                'filas_cifradas' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
                'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pendiente'],
                'clave_version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'verificacion_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
                'iniciado_at' => ['type' => 'DATETIME', 'null' => true],
                'completado_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('tabla');
            $this->forge->createTable('dp_cifrado_estado');
        }
    }

    public function down(): void
    {
        // El ciphertext puede exceder los tipos originales. El rollback de datos se
        // hace con privacy:encrypt-at-rest --decrypt antes de revertir esta migracion.
        $this->forge->dropTable('dp_cifrado_estado', true);
        /** @var PrivacyEncryption $config */
        $config = config(PrivacyEncryption::class);
        foreach ($config->tables as $table => $spec) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            foreach ($spec['blind'] ?? [] as $blind) {
                $column = $blind['col'];
                if ($this->db->fieldExists($column, $table)) {
                    $this->forge->dropColumn($table, $column);
                }
            }
        }
    }

    private function expandEncryptedColumn(string $table, string $column): void
    {
        if (! $this->db->fieldExists($column, $table)) {
            throw new \RuntimeException("Falta el campo cifrado requerido {$table}.{$column}");
        }
        $meta = $this->db->query(
            'SELECT DATA_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        )->getRowArray();
        if (($meta['DATA_TYPE'] ?? '') === 'longtext') {
            return;
        }
        $null = ($meta['IS_NULLABLE'] ?? 'YES') === 'YES' ? 'NULL' : 'NOT NULL';
        $this->db->query("ALTER TABLE `{$table}` MODIFY `{$column}` LONGTEXT {$null}");
    }

    private function dropPlaintextIndexes(string $table, array $spec): void
    {
        $encrypted = array_fill_keys($spec['encrypt'], true);
        $rows = $this->db->query(
            "SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_list
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME <> 'PRIMARY'
             GROUP BY INDEX_NAME, NON_UNIQUE",
            [$table]
        )->getResultArray();
        foreach ($rows as $row) {
            $columns = explode(',', (string) $row['columns_list']);
            if (! array_intersect_key($encrypted, array_fill_keys($columns, true))) {
                continue;
            }
            if ((int) $row['NON_UNIQUE'] === 0) {
                throw new \RuntimeException('El indice unico ' . $table . '.' . $row['INDEX_NAME'] . ' requiere una sustitucion explicita antes del cifrado.');
            }
            $remaining = array_values(array_filter($columns, static fn (string $column): bool => ! isset($encrypted[$column])));
            if ($remaining !== []) {
                $replacement = $this->indexName($table, implode('_', $remaining));
                if (! $this->indexExists($table, $replacement)) {
                    $quoted = implode('`, `', $remaining);
                    $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$replacement}` (`{$quoted}`)");
                }
            }
            $name = (string) $row['INDEX_NAME'];
            if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                throw new \RuntimeException('Nombre de indice no seguro en ' . $table);
            }
            $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        }
    }

    private function indexName(string $table, string $column): string
    {
        return substr('idx_' . $table . '_' . $column, 0, 64);
    }

    private function indexExists(string $table, string $name): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $name]
        )->getRowArray();
        return (int) ($row['c'] ?? 0) > 0;
    }
}
