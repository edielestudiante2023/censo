<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInmuebles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'cliente_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'torre_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'tipo'          => ['type' => 'ENUM', 'constraint' => ['casa', 'apartamento'], 'default' => 'apartamento'],
            'identificador' => ['type' => 'VARCHAR', 'constraint' => 50],
            'piso'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['cliente_id', 'torre_id', 'identificador']);
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('torre_id', 'torres', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('inmuebles', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('inmuebles', true);
    }
}
