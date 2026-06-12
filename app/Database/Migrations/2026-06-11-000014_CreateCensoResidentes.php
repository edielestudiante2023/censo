<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCensoResidentes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'censo_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'documento'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'parentesco_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'edad'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('censo_id', 'censos_poblacionales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('parentesco_id', 'parentescos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('censo_residentes', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('censo_residentes', true);
    }
}
