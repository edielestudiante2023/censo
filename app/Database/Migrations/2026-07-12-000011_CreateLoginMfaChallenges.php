<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoginMfaChallenges extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true], 'codigo_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'intentos' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0], 'expira_at' => ['type' => 'DATETIME'],
            'verificado_at' => ['type' => 'DATETIME', 'null' => true], 'consumido_at' => ['type' => 'DATETIME', 'null' => true],
            'ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true], 'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['usuario_id', 'expira_at']);
        $this->forge->createTable('usuario_mfa_desafios', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('usuario_mfa_desafios', true);
    }
}
