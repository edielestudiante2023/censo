<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMascotas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'censo_mascota_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tipo_mascota_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'edad'                => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'raza_color'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'vacunacion_completa' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'esterilizada'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'foto_ruta'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'foto_carne_ruta'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'foto_poliza_ruta'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('censo_mascota_id', 'censos_mascotas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tipo_mascota_id', 'tipos_mascota', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('mascotas', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('mascotas', true);
    }
}
