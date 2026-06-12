<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTorres extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'num_pisos'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['cliente_id', 'nombre']);
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('torres', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('torres', true);
    }
}
