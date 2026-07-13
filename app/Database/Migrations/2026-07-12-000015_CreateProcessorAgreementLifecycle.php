<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProcessorAgreementLifecycle extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('dp_terceros', [
            'clasificacion_rol' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'pendiente', 'after' => 'tipo'],
            'clasificacion_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'clasificacion_rol'],
            'clasificacion_justificacion' => ['type' => 'TEXT', 'null' => true, 'after' => 'clasificacion_json'],
            'clasificado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'clasificacion_justificacion'],
            'representante_nombre' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'contacto_email'],
            'representante_documento' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'representante_nombre'],
            'representante_email' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'representante_documento'],
            'representacion_evidencia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'representante_email'],
            'facultades_verificadas_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'representacion_evidencia'],
            'contrato_principal_ref' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'servicio'],
            'contrato_principal_objeto' => ['type' => 'TEXT', 'null' => true, 'after' => 'contrato_principal_ref'],
            'titulares_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'bases_json'],
            'categorias_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'titulares_json'],
            'finalidades_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'categorias_json'],
            'operaciones_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'finalidades_json'],
            'sistemas_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'operaciones_json'],
            'paises_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'sistemas_json'],
            'datos_sensibles' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'paises_json'],
            'datos_biometricos' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'datos_sensibles'],
            'datos_menores' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'datos_biometricos'],
            'usa_videovigilancia' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'datos_menores'],
            'nivel_riesgo' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'basico', 'after' => 'usa_videovigilancia'],
            'medidas_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'nivel_riesgo'],
            'debida_diligencia_evidencia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'medidas_json'],
            'canal_incidentes_probado_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'debida_diligencia_evidencia'],
            'plazo_incidente_dias' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 3, 'after' => 'canal_incidentes_probado_at'],
            'logs_retencion_meses' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 12, 'after' => 'plazo_incidente_dias'],
            'backup_rotacion_dias' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 90, 'after' => 'logs_retencion_meses'],
            'rto_horas' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 24, 'after' => 'backup_rotacion_dias'],
            'rpo_horas' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 24, 'after' => 'rto_horas'],
            'seguro_evidencia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'rpo_horas'],
            'habilitado_datos' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'subencargado_autorizado'],
        ]);
        $this->createSubprocessors();
        $this->createAgreements();
        $this->createEvents();
        $this->createInstructions();
        $this->createCertificates();
        $this->createTriggers();
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            foreach (['dp_acuerdos_encargado_bu', 'dp_acuerdos_encargado_bd', 'dp_acuerdo_encargado_eventos_bu', 'dp_acuerdo_encargado_eventos_bd'] as $trigger) {
                $this->db->query('DROP TRIGGER IF EXISTS ' . $trigger);
            }
        }
        foreach (['dp_encargado_certificaciones', 'dp_encargado_instrucciones', 'dp_acuerdo_encargado_eventos', 'dp_acuerdos_encargado', 'dp_subencargados'] as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->forge->dropColumn('dp_terceros', ['clasificacion_rol', 'clasificacion_json', 'clasificacion_justificacion', 'clasificado_at',
            'representante_nombre', 'representante_documento', 'representante_email', 'representacion_evidencia', 'facultades_verificadas_at',
            'contrato_principal_ref', 'contrato_principal_objeto', 'titulares_json', 'categorias_json', 'finalidades_json', 'operaciones_json',
            'sistemas_json', 'paises_json', 'datos_sensibles', 'datos_biometricos', 'datos_menores', 'usa_videovigilancia', 'nivel_riesgo',
            'medidas_json', 'debida_diligencia_evidencia', 'canal_incidentes_probado_at', 'plazo_incidente_dias', 'logs_retencion_meses',
            'backup_rotacion_dias', 'rto_horas', 'rpo_horas', 'seguro_evidencia', 'habilitado_datos']);
    }

    private function createSubprocessors(): void
    {
        $this->forge->addField(['id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true], 'tercero_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 191], 'documento' => ['type' => 'VARCHAR', 'constraint' => 80],
            'pais' => ['type' => 'VARCHAR', 'constraint' => 100], 'servicio' => ['type' => 'TEXT'], 'datos_json' => ['type' => 'LONGTEXT'],
            'contrato_evidencia' => ['type' => 'VARCHAR', 'constraint' => 255], 'aprobado_por' => ['type' => 'BIGINT', 'unsigned' => true],
            'aprobado_at' => ['type' => 'DATETIME'], 'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true]]);
        $this->forge->addKey('id', true); $this->forge->addKey(['cliente_id', 'tercero_id', 'activo']); $this->forge->createTable('dp_subencargados', true);
    }

    private function createAgreements(): void
    {
        $this->forge->addField(['id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true], 'tercero_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'documento_id' => ['type' => 'BIGINT', 'unsigned' => true], 'documento_version' => ['type' => 'INT', 'unsigned' => true],
            'documento_hash' => ['type' => 'CHAR', 'constraint' => 64], 'version_instancia' => ['type' => 'INT', 'unsigned' => true],
            'token' => ['type' => 'CHAR', 'constraint' => 64], 'variables_json' => ['type' => 'LONGTEXT'],
            'instancia_html' => ['type' => 'LONGTEXT'], 'instancia_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'vigencia_desde' => ['type' => 'DATE'], 'vigencia_hasta' => ['type' => 'DATE'],
            'responsable_firmante' => ['type' => 'VARCHAR', 'constraint' => 191], 'responsable_firma' => ['type' => 'LONGTEXT'],
            'responsable_firma_hash' => ['type' => 'CHAR', 'constraint' => 64], 'responsable_firmado_at' => ['type' => 'DATETIME'],
            'responsable_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true], 'vista_at' => ['type' => 'DATETIME', 'null' => true],
            'codigo_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true], 'codigo_expira_at' => ['type' => 'DATETIME', 'null' => true],
            'codigo_intentos' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'encargado_firma' => ['type' => 'LONGTEXT', 'null' => true], 'encargado_firma_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'encargado_firmado_at' => ['type' => 'DATETIME', 'null' => true], 'encargado_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'encargado_user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'copia_enviada_at' => ['type' => 'DATETIME', 'null' => true], 'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pendiente_firma'],
            'terminado_at' => ['type' => 'DATETIME', 'null' => true], 'terminacion_motivo' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true]]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('token'); $this->forge->addKey(['cliente_id', 'tercero_id', 'estado']);
        $this->forge->createTable('dp_acuerdos_encargado', true);
    }

    private function createEvents(): void
    {
        $this->forge->addField(['id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'acuerdo_id' => ['type' => 'BIGINT', 'unsigned' => true], 'tipo' => ['type' => 'VARCHAR', 'constraint' => 60],
            'detalle_json' => ['type' => 'LONGTEXT'], 'evento_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true], 'ocurrido_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true]]);
        $this->forge->addKey('id', true); $this->forge->addKey(['acuerdo_id', 'ocurrido_at']); $this->forge->createTable('dp_acuerdo_encargado_eventos', true);
    }

    private function createInstructions(): void
    {
        $this->forge->addField(['id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'acuerdo_id' => ['type' => 'BIGINT', 'unsigned' => true], 'emisor_usuario_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'contenido' => ['type' => 'LONGTEXT'], 'contenido_hash' => ['type' => 'CHAR', 'constraint' => 64], 'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'emitida'],
            'acuse_at' => ['type' => 'DATETIME', 'null' => true], 'objecion' => ['type' => 'TEXT', 'null' => true], 'objetada_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true]]);
        $this->forge->addKey('id', true); $this->forge->addKey(['cliente_id', 'acuerdo_id']); $this->forge->createTable('dp_encargado_instrucciones', true);
    }

    private function createCertificates(): void
    {
        $this->forge->addField(['id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'acuerdo_id' => ['type' => 'BIGINT', 'unsigned' => true], 'detalle_json' => ['type' => 'LONGTEXT'], 'evidencia' => ['type' => 'VARCHAR', 'constraint' => 255],
            'evidencia_hash' => ['type' => 'CHAR', 'constraint' => 64], 'verificado_por' => ['type' => 'BIGINT', 'unsigned' => true],
            'verificado_at' => ['type' => 'DATETIME'], 'created_at' => ['type' => 'DATETIME', 'null' => true]]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('acuerdo_id'); $this->forge->createTable('dp_encargado_certificaciones', true);
    }

    private function createTriggers(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') { return; }
        foreach (['dp_acuerdo_encargado_eventos_bu' => 'UPDATE', 'dp_acuerdo_encargado_eventos_bd' => 'DELETE'] as $name => $event) {
            $this->db->query("CREATE TRIGGER {$name} BEFORE {$event} ON dp_acuerdo_encargado_eventos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La evidencia contractual es append-only'");
        }
        $this->db->query("CREATE TRIGGER dp_acuerdos_encargado_bd BEFORE DELETE ON dp_acuerdos_encargado FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La instancia contractual no puede eliminarse'");
        $this->db->query(<<<'SQL'
CREATE TRIGGER dp_acuerdos_encargado_bu BEFORE UPDATE ON dp_acuerdos_encargado FOR EACH ROW
BEGIN
 IF OLD.encargado_firmado_at IS NOT NULL AND NOT (OLD.cliente_id <=> NEW.cliente_id AND OLD.tercero_id <=> NEW.tercero_id AND OLD.documento_id <=> NEW.documento_id AND OLD.documento_hash <=> NEW.documento_hash AND OLD.variables_json <=> NEW.variables_json AND OLD.instancia_html <=> NEW.instancia_html AND OLD.instancia_hash <=> NEW.instancia_hash AND OLD.vigencia_desde <=> NEW.vigencia_desde AND OLD.vigencia_hasta <=> NEW.vigencia_hasta AND OLD.responsable_firma <=> NEW.responsable_firma AND OLD.encargado_firma <=> NEW.encargado_firma AND OLD.encargado_firmado_at <=> NEW.encargado_firmado_at) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La instancia contractual firmada es inmutable';
 END IF;
END
SQL);
    }
}
