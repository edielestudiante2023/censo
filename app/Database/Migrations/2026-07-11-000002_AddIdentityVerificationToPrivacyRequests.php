<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdentityVerificationToPrivacyRequests extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('dp_solicitudes', [
            'identidad_estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pendiente', 'after' => 'estado'],
            'identidad_verificada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'identidad_estado'],
            'subsanacion_solicitada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'identidad_verificada_at'],
            'desistida_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'subsanacion_solicitada_at'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('dp_solicitudes', [
            'identidad_estado', 'identidad_verificada_at', 'subsanacion_solicitada_at', 'desistida_at',
        ]);
    }
}
