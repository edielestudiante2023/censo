<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAnioToQrCodes extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('anio', 'qr_codes')) {
            $this->forge->addColumn('qr_codes', [
                'anio' => ['type' => 'INT', 'constraint' => 4, 'null' => true, 'after' => 'tipo_instrumento'],
            ]);
        }
        // Backfill: ano por la fecha de creacion
        $this->db->query('UPDATE qr_codes SET anio = YEAR(created_at) WHERE anio IS NULL AND created_at IS NOT NULL');
        $this->db->query('UPDATE qr_codes SET anio = YEAR(CURDATE()) WHERE anio IS NULL');
    }

    public function down()
    {
        if ($this->db->fieldExists('anio', 'qr_codes')) {
            $this->forge->dropColumn('qr_codes', 'anio');
        }
    }
}
