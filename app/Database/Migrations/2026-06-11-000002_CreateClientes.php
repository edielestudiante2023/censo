<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre_tercero'    => ['type' => 'VARCHAR', 'constraint' => 191],
            'tipo_documento'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'NIT'],
            'documento'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'direccion'         => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'ciudad'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'telefono'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'persona_contacto'  => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'logo'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'color_primario'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'color_secundario'  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'tipo_conjunto'     => ['type' => 'ENUM', 'constraint' => ['casas', 'apartamentos', 'mixto'], 'default' => 'apartamentos'],
            'slug'              => ['type' => 'VARCHAR', 'constraint' => 191],
            'texto_habeas_data' => ['type' => 'TEXT', 'null' => true],
            'activo'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('clientes', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('clientes', true);
    }
}
