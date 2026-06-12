<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCensoPropietarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'censo_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 191],
            'documento'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'telefono'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'correo'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('censo_id', 'censos_poblacionales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('censo_propietarios', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('censo_propietarios', true);
    }
}
