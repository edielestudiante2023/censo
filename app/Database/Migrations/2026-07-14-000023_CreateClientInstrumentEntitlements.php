<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientInstrumentEntitlements extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'instrumento' => ['type' => 'VARCHAR', 'constraint' => 40],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'deshabilitado'],
            'habilitado_desde' => ['type' => 'DATETIME', 'null' => true],
            'habilitado_hasta' => ['type' => 'DATETIME', 'null' => true],
            'motivo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'actualizado_por' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['cliente_id', 'instrumento']);
        $this->forge->addKey(['estado', 'habilitado_hasta']);
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('actualizado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('cliente_instrumentos', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'instrumento' => ['type' => 'VARCHAR', 'constraint' => 40],
            'estado_anterior' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'estado_nuevo' => ['type' => 'VARCHAR', 'constraint' => 20],
            'motivo' => ['type' => 'VARCHAR', 'constraint' => 255],
            'actor_usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'created_at']);
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('actor_usuario_id', 'usuarios', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('cliente_instrumento_eventos', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('cliente_instrumento_eventos', true);
        $this->forge->dropTable('cliente_instrumentos', true);
    }
}
