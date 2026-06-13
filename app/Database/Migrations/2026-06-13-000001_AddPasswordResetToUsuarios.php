<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordResetToUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addColumn('usuarios', [
            'reset_token'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'password_hash'],
            'reset_expires' => ['type' => 'DATETIME', 'null' => true, 'after' => 'reset_token'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('usuarios', ['reset_token', 'reset_expires']);
    }
}
