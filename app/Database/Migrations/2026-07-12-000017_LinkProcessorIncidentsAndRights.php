<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LinkProcessorIncidentsAndRights extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('dp_incidentes_privacidad', [
            'tercero_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'cliente_id'],
            'acuerdo_encargado_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'tercero_id'],
            'notificado_encargado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'conocimiento_at'],
        ]);
        $this->forge->addColumn('dp_solicitudes', [
            'tercero_origen_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'cliente_id'],
            'acuerdo_encargado_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'tercero_origen_id'],
            'recibida_encargado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'fecha_ingreso_real'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('dp_incidentes_privacidad', ['tercero_id', 'acuerdo_encargado_id', 'notificado_encargado_at']);
        $this->forge->dropColumn('dp_solicitudes', ['tercero_origen_id', 'acuerdo_encargado_id', 'recibida_encargado_at']);
    }
}
