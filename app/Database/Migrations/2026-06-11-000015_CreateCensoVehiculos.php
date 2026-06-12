<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCensoVehiculos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'censo_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipo_vehiculo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'marca'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'linea'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'modelo'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'color'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'placa'            => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('censo_id', 'censos_poblacionales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tipo_vehiculo_id', 'tipos_vehiculo', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('censo_vehiculos', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('censo_vehiculos', true);
    }
}
