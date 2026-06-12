<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTiposMascota extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'activo'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre');
        $this->forge->createTable('tipos_mascota', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('tipos_mascota', true);
    }
}
