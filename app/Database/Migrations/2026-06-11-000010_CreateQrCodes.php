<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQrCodes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'cliente_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipo_instrumento' => ['type' => 'ENUM', 'constraint' => ['poblacional', 'mascotas']],
            'token'            => ['type' => 'VARCHAR', 'constraint' => 64],
            'titulo'           => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'activo'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('qr_codes', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('qr_codes', true);
    }
}
