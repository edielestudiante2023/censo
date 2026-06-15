<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAnioToCensos extends Migration
{
    public function up()
    {
        foreach (['censos_poblacionales', 'censos_mascotas'] as $table) {
            if (! $this->db->fieldExists('anio', $table)) {
                $this->forge->addColumn($table, [
                    'anio' => ['type' => 'INT', 'constraint' => 4, 'null' => true, 'after' => 'qr_id'],
                ]);
            }
            $this->db->query("UPDATE {$table} SET anio = YEAR(created_at) WHERE anio IS NULL AND created_at IS NOT NULL");
            $this->db->query("UPDATE {$table} SET anio = YEAR(CURDATE()) WHERE anio IS NULL");
        }
    }

    public function down()
    {
        foreach (['censos_poblacionales', 'censos_mascotas'] as $table) {
            if ($this->db->fieldExists('anio', $table)) {
                $this->forge->dropColumn($table, 'anio');
            }
        }
    }
}
