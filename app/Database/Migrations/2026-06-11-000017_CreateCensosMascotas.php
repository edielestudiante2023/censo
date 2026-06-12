<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCensosMascotas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'cliente_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'qr_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'inmueble_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'autorizacion_datos'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fecha_autorizacion'     => ['type' => 'DATETIME', 'null' => true],
            'responsable_nombre'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'responsable_documento'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'responsable_telefono'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'responsable_correo'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'firmante_nombre'        => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'firma_imagen'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pdf_ruta'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pdf_enviado'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fecha_envio'            => ['type' => 'DATETIME', 'null' => true],
            'ip'                     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('qr_id', 'qr_codes', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('inmueble_id', 'inmuebles', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('censos_mascotas', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('censos_mascotas', true);
    }
}
