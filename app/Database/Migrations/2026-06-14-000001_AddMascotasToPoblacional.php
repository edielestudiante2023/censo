<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMascotasToPoblacional extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('tiene_mascotas', 'censos_poblacionales')) {
            $this->forge->addColumn('censos_poblacionales', [
                'tiene_mascotas' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'after' => 'discapacidad_descripcion',
                ],
            ]);
        }

        $this->dropForeignKeyForColumn('mascotas', 'censo_mascota_id');

        $this->db->query('ALTER TABLE `mascotas` MODIFY `censo_mascota_id` INT(11) UNSIGNED NULL');

        if (! $this->db->fieldExists('censo_poblacional_id', 'mascotas')) {
            $this->db->query('ALTER TABLE `mascotas` ADD `censo_poblacional_id` INT(11) UNSIGNED NULL AFTER `censo_mascota_id`');
            $this->db->query('ALTER TABLE `mascotas` ADD INDEX `mascotas_censo_poblacional_id_index` (`censo_poblacional_id`)');
        }

        if (! $this->foreignKeyExists('mascotas_censo_mascota_id_foreign')) {
            $this->db->query('ALTER TABLE `mascotas` ADD CONSTRAINT `mascotas_censo_mascota_id_foreign` FOREIGN KEY (`censo_mascota_id`) REFERENCES `censos_mascotas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        }

        if (! $this->foreignKeyExists('mascotas_censo_poblacional_id_foreign')) {
            $this->db->query('ALTER TABLE `mascotas` ADD CONSTRAINT `mascotas_censo_poblacional_id_foreign` FOREIGN KEY (`censo_poblacional_id`) REFERENCES `censos_poblacionales`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('censo_poblacional_id', 'mascotas')) {
            $this->db->table('mascotas')->where('censo_mascota_id', null)->delete();
            $this->dropForeignKeyForColumn('mascotas', 'censo_poblacional_id');
            $this->db->query('ALTER TABLE `mascotas` DROP COLUMN `censo_poblacional_id`');
        }

        $this->dropForeignKeyForColumn('mascotas', 'censo_mascota_id');
        $this->db->query('ALTER TABLE `mascotas` MODIFY `censo_mascota_id` INT(11) UNSIGNED NOT NULL');

        if (! $this->foreignKeyExists('mascotas_censo_mascota_id_foreign')) {
            $this->db->query('ALTER TABLE `mascotas` ADD CONSTRAINT `mascotas_censo_mascota_id_foreign` FOREIGN KEY (`censo_mascota_id`) REFERENCES `censos_mascotas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        }

        if ($this->db->fieldExists('tiene_mascotas', 'censos_poblacionales')) {
            $this->forge->dropColumn('censos_poblacionales', 'tiene_mascotas');
        }
    }

    private function dropForeignKeyForColumn(string $table, string $column): void
    {
        $rows = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column]
        )->getResultArray();

        foreach ($rows as $row) {
            $this->db->query('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $row['CONSTRAINT_NAME'] . '`');
        }
    }

    private function foreignKeyExists(string $constraintName): bool
    {
        return (bool) $this->db->query(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = "FOREIGN KEY"',
            [$constraintName]
        )->getRowArray();
    }
}
