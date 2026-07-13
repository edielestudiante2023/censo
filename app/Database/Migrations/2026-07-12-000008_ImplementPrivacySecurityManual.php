<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ImplementPrivacySecurityManual extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('dp_bases_datos', [
            'datos_biometricos' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'datos_sensibles'],
            'soportes_ubicacion' => ['type' => 'TEXT', 'null' => true, 'after' => 'ubicacion'],
            'revisado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'rnbd_aplica'],
        ]);
        $this->forge->addColumn('dp_terceros', [
            'contrato_fecha' => ['type' => 'DATE', 'null' => true, 'after' => 'contrato_vigente'],
            'contrato_vence' => ['type' => 'DATE', 'null' => true, 'after' => 'contrato_fecha'],
            'contrato_evidencia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'contrato_vence'],
            'evaluado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'medidas_verificadas'],
            'subencargado_autorizado' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'evaluado_at'],
        ]);
        $this->forge->addColumn('dp_incidentes_privacidad', [
            'folio' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'cliente_id'],
            'severidad' => ['type' => 'VARCHAR', 'constraint' => 5, 'default' => 'S4', 'after' => 'tipo'],
            'fuente' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'severidad'],
            'conocimiento_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'detectado_at'],
            'sic_vence_at' => ['type' => 'DATE', 'null' => true, 'after' => 'conocimiento_at'],
            'clasificado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'sic_vence_at'],
            'contencion_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'clasificado_at'],
            'investigacion' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'contencion_at'],
            'categorias_afectadas' => ['type' => 'TEXT', 'null' => true, 'after' => 'investigacion'],
            'titulares_estimados' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'categorias_afectadas'],
            'decision_reporte' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pendiente', 'after' => 'titulares_estimados'],
            'decision_motivo' => ['type' => 'TEXT', 'null' => true, 'after' => 'decision_reporte'],
            'sic_reportado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'decision_motivo'],
            'sic_evidencia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'sic_reportado_at'],
            'titulares_comunicados_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'sic_evidencia'],
            'titulares_comunicacion_motivo' => ['type' => 'TEXT', 'null' => true, 'after' => 'titulares_comunicados_at'],
            'recuperado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'titulares_comunicacion_motivo'],
            'lecciones' => ['type' => 'TEXT', 'null' => true, 'after' => 'recuperado_at'],
            'cerrado_por' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'cerrado_at'],
        ]);
        $this->createAssignments();
        $this->createControls();
        $this->createUserCompliance();
        $this->createIncidentEvents();
        if ($this->db->DBDriver === 'MySQLi') {
            foreach (['dp_incidente_eventos_bu' => 'UPDATE', 'dp_incidente_eventos_bd' => 'DELETE',
                'dp_controles_seguridad_bu' => 'UPDATE', 'dp_controles_seguridad_bd' => 'DELETE'] as $name => $event) {
                $table = str_starts_with($name, 'dp_incidente') ? 'dp_incidente_eventos' : 'dp_controles_seguridad';
                $this->db->query("CREATE TRIGGER {$name} BEFORE {$event} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El registro de seguridad es append-only'");
            }
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            foreach (['dp_incidente_eventos_bu', 'dp_incidente_eventos_bd', 'dp_controles_seguridad_bu', 'dp_controles_seguridad_bd'] as $trigger) {
                $this->db->query('DROP TRIGGER IF EXISTS ' . $trigger);
            }
        }
        foreach (['dp_incidente_eventos', 'dp_usuario_privacidad', 'dp_controles_seguridad', 'dp_asignaciones_seguridad'] as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->forge->dropColumn('dp_incidentes_privacidad', ['folio', 'severidad', 'fuente', 'conocimiento_at', 'sic_vence_at', 'clasificado_at', 'contencion_at', 'investigacion', 'categorias_afectadas', 'titulares_estimados', 'decision_reporte', 'decision_motivo', 'sic_reportado_at', 'sic_evidencia', 'titulares_comunicados_at', 'titulares_comunicacion_motivo', 'recuperado_at', 'lecciones', 'cerrado_por']);
        $this->forge->dropColumn('dp_terceros', ['contrato_fecha', 'contrato_vence', 'contrato_evidencia', 'evaluado_at', 'subencargado_autorizado']);
        $this->forge->dropColumn('dp_bases_datos', ['datos_biometricos', 'soportes_ubicacion', 'revisado_at']);
    }

    private function createAssignments(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'rol' => ['type' => 'VARCHAR', 'constraint' => 40], 'responsable' => ['type' => 'VARCHAR', 'constraint' => 191],
            'acto_designacion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], 'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey(['cliente_id', 'rol']);
        $this->forge->createTable('dp_asignaciones_seguridad', true);
    }

    private function createControls(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 50], 'resultado' => ['type' => 'VARCHAR', 'constraint' => 30],
            'detalle' => ['type' => 'LONGTEXT'], 'evidencia' => ['type' => 'VARCHAR', 'constraint' => 255],
            'evidencia_hash' => ['type' => 'CHAR', 'constraint' => 64], 'ejecutado_at' => ['type' => 'DATETIME'],
            'vence_at' => ['type' => 'DATE', 'null' => true], 'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['cliente_id', 'tipo', 'ejecutado_at']);
        $this->forge->createTable('dp_controles_seguridad', true);
    }

    private function createUserCompliance(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true], 'confidencialidad_at' => ['type' => 'DATETIME', 'null' => true],
            'confidencialidad_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true], 'induccion_at' => ['type' => 'DATETIME', 'null' => true],
            'induccion_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true], 'recertificado_at' => ['type' => 'DATETIME', 'null' => true],
            'suspendido_at' => ['type' => 'DATETIME', 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey(['cliente_id', 'usuario_id']);
        $this->forge->createTable('dp_usuario_privacidad', true);
    }

    private function createIncidentEvents(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'incidente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 50], 'detalle_json' => ['type' => 'LONGTEXT'],
            'evento_hash' => ['type' => 'CHAR', 'constraint' => 64], 'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'ocurrido_at' => ['type' => 'DATETIME'], 'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['incidente_id', 'ocurrido_at']);
        $this->forge->createTable('dp_incidente_eventos', true);
    }
}
