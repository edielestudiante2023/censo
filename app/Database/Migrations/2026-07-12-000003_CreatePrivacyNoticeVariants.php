<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePrivacyNoticeVariants extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'documento_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 40],
            'version_maestra' => ['type' => 'INT', 'unsigned' => true],
            'version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'titulo' => ['type' => 'VARCHAR', 'constraint' => 191],
            'contenido_html' => ['type' => 'LONGTEXT'],
            'hash_sha256' => ['type' => 'CHAR', 'constraint' => 64],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'borrador'],
            'vigente_desde' => ['type' => 'DATE', 'null' => true],
            'publicado_at' => ['type' => 'DATETIME', 'null' => true],
            'retirado_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'estado']);
        $this->forge->addUniqueKey(['documento_id', 'tipo']);
        $this->forge->createTable('dp_aviso_variantes', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'variante_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'canal' => ['type' => 'VARCHAR', 'constraint' => 40],
            'ubicacion' => ['type' => 'VARCHAR', 'constraint' => 255],
            'evidencia_ruta' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'evidencia_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'publicado_at' => ['type' => 'DATETIME'],
            'retirado_at' => ['type' => 'DATETIME', 'null' => true],
            'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'canal']);
        $this->forge->addKey('variante_id');
        $this->forge->createTable('dp_aviso_publicaciones', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('dp_aviso_publicaciones', true);
        $this->forge->dropTable('dp_aviso_variantes', true);
    }
}
