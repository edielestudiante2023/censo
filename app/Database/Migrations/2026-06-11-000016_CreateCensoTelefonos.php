<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCensoTelefonos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'censo_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'numero'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'orden'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('censo_id', 'censos_poblacionales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('censo_telefonos', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('censo_telefonos', true);
    }
}
