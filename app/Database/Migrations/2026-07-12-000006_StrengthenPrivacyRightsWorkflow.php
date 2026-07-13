<?php

namespace App\Database\Migrations;

use App\Libraries\PrivacyBusinessDays;
use CodeIgniter\Database\Migration;

class StrengthenPrivacyRightsWorkflow extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('dp_solicitudes', [
            'clasificacion_original' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'tipo'],
            'reclasificacion_motivo' => ['type' => 'TEXT', 'null' => true, 'after' => 'clasificacion_original'],
            'reclasificada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'reclasificacion_motivo'],
            'canal' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'aplicativo', 'after' => 'titular_email'],
            'calidad_solicitante' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'titular', 'after' => 'canal'],
            'legitimacion_tipo' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'calidad_solicitante'],
            'legitimacion_evidencia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'legitimacion_tipo'],
            'fecha_ingreso_real' => ['type' => 'DATETIME', 'null' => true, 'after' => 'recibida_at'],
            'fecha_recepcion_legal' => ['type' => 'DATE', 'null' => true, 'after' => 'fecha_ingreso_real'],
            'acuse_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'fecha_recepcion_legal'],
            'acuse_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'acuse_at'],
            'subsanacion_detalle' => ['type' => 'TEXT', 'null' => true, 'after' => 'subsanacion_solicitada_at'],
            'subsanacion_limite_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'subsanacion_detalle'],
            'subsanada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'subsanacion_limite_at'],
            'traslado_destinatario' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'desistida_at'],
            'trasladada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'traslado_destinatario'],
            'traslado_notificado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'trasladada_at'],
            'reclamo_motivo' => ['type' => 'TEXT', 'null' => true, 'after' => 'reclamo_marcado_at'],
            'leyenda_retirada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'reclamo_motivo'],
            'prorroga_motivo' => ['type' => 'TEXT', 'null' => true, 'after' => 'prorroga_hasta'],
            'prorroga_notificada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'prorroga_motivo'],
            'respuesta_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'respuesta_texto'],
            'datos_conservados' => ['type' => 'TEXT', 'null' => true, 'after' => 'fundamento_conservacion'],
            'conservacion_hasta' => ['type' => 'DATE', 'null' => true, 'after' => 'datos_conservados'],
            'vencimiento_registrado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'cerrada_at'],
            'vencimiento_causa' => ['type' => 'TEXT', 'null' => true, 'after' => 'vencimiento_registrado_at'],
        ]);
        $this->forge->addColumn('dp_solicitud_bases', [
            'leyenda' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'estado'],
            'leyenda_desde' => ['type' => 'DATETIME', 'null' => true, 'after' => 'leyenda'],
            'leyenda_retirada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'leyenda_desde'],
            'valor_anterior_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'evidencia'],
            'valor_nuevo' => ['type' => 'TEXT', 'null' => true, 'after' => 'valor_anterior_hash'],
            'fuente_correccion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'valor_nuevo'],
            'bloqueado_hasta' => ['type' => 'DATE', 'null' => true, 'after' => 'fuente_correccion'],
            'supresion_programada_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'bloqueado_hasta'],
        ]);
        $this->forge->addColumn('dp_solicitud_terceros', [
            'accion' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'por_evaluar', 'after' => 'tercero_id'],
            'plazo_confirmacion' => ['type' => 'DATE', 'null' => true, 'after' => 'notificado_at'],
            'respuesta_detalle' => ['type' => 'TEXT', 'null' => true, 'after' => 'confirmado_at'],
        ]);
        $this->createEvents();
        $this->createHolidayCalendar();
        $this->createRestorations();
        $this->createIncidents();
        if ($this->db->DBDriver === 'MySQLi') {
            foreach (['dp_solicitud_eventos_bu' => 'UPDATE', 'dp_solicitud_eventos_bd' => 'DELETE'] as $name => $event) {
                $this->db->query("CREATE TRIGGER {$name} BEFORE {$event} ON dp_solicitud_eventos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El expediente de solicitudes es append-only'");
            }
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            foreach (['dp_solicitud_eventos_bu', 'dp_solicitud_eventos_bd'] as $trigger) {
                $this->db->query('DROP TRIGGER IF EXISTS ' . $trigger);
            }
        }
        foreach (['dp_incidentes_privacidad', 'dp_restauraciones_privacidad', 'dp_calendario_festivos', 'dp_solicitud_eventos'] as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->forge->dropColumn('dp_solicitud_terceros', ['accion', 'plazo_confirmacion', 'respuesta_detalle']);
        $this->forge->dropColumn('dp_solicitud_bases', ['leyenda', 'leyenda_desde', 'leyenda_retirada_at', 'valor_anterior_hash', 'valor_nuevo', 'fuente_correccion', 'bloqueado_hasta', 'supresion_programada_at']);
        $this->forge->dropColumn('dp_solicitudes', ['clasificacion_original', 'reclasificacion_motivo', 'reclasificada_at', 'canal', 'calidad_solicitante', 'legitimacion_tipo', 'legitimacion_evidencia', 'fecha_ingreso_real', 'fecha_recepcion_legal', 'acuse_at', 'acuse_hash', 'subsanacion_detalle', 'subsanacion_limite_at', 'subsanada_at', 'traslado_destinatario', 'trasladada_at', 'traslado_notificado_at', 'reclamo_motivo', 'leyenda_retirada_at', 'prorroga_motivo', 'prorroga_notificada_at', 'respuesta_hash', 'datos_conservados', 'conservacion_hasta', 'vencimiento_registrado_at', 'vencimiento_causa']);
    }

    private function createEvents(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'solicitud_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'detalle_json' => ['type' => 'LONGTEXT'],
            'evento_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'ocurrido_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['solicitud_id', 'ocurrido_at']);
        $this->forge->createTable('dp_solicitud_eventos', true);
    }

    private function createHolidayCalendar(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'fecha' => ['type' => 'DATE'], 'nombre' => ['type' => 'VARCHAR', 'constraint' => 191],
            'fuente' => ['type' => 'VARCHAR', 'constraint' => 191], 'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('fecha');
        $this->forge->createTable('dp_calendario_festivos', true);
        foreach (range((int) date('Y') - 1, (int) date('Y') + 10) as $year) {
            foreach (PrivacyBusinessDays::holidaysForYear($year) as $date) {
                $this->db->table('dp_calendario_festivos')->ignore(true)->insert([
                    'fecha' => $date, 'nombre' => 'Festivo nacional colombiano',
                    'fuente' => 'Ley 51 de 1983', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function createRestorations(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true], 'respaldo' => ['type' => 'VARCHAR', 'constraint' => 255],
            'lista_version_hash' => ['type' => 'CHAR', 'constraint' => 64], 'registros_filtrados' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'verificacion_estado' => ['type' => 'VARCHAR', 'constraint' => 30], 'detalle' => ['type' => 'TEXT', 'null' => true],
            'ejecutado_por' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true], 'ejecutado_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'ejecutado_at']);
        $this->forge->createTable('dp_restauraciones_privacidad', true);
    }

    private function createIncidents(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true], 'tipo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'identificador_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true], 'detalle' => ['type' => 'TEXT'],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'abierto'], 'detectado_at' => ['type' => 'DATETIME'],
            'cerrado_at' => ['type' => 'DATETIME', 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'estado']);
        $this->forge->createTable('dp_incidentes_privacidad', true);
    }
}
