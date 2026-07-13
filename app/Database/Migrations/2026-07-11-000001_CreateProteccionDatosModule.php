<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProteccionDatosModule extends Migration
{
    public function up(): void
    {
        $this->createProgramas();
        $this->createBases();
        $this->createFinalidades();
        $this->createTerceros();
        $this->createPlantillas();
        $this->createDocumentos();
        $this->createConsentimientos();
        $this->createExclusiones();
        $this->createSolicitudes();
        $this->createSolicitudBases();
        $this->createSolicitudTerceros();
        $this->createNotificaciones();
        $this->createNotificacionEventos();
        $this->createAiRuns();
        $this->createAuditoria();
    }

    public function down(): void
    {
        foreach ([
            'dp_auditoria', 'dp_ai_runs', 'dp_notificacion_eventos', 'dp_notificaciones',
            'dp_solicitud_terceros', 'dp_solicitud_bases', 'dp_solicitudes', 'dp_exclusiones',
            'dp_consentimientos', 'dp_documentos', 'dp_plantillas', 'dp_terceros',
            'dp_finalidades', 'dp_bases_datos', 'dp_programas',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function timestamps(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function createProgramas(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'public_token' => ['type' => 'VARCHAR', 'constraint' => 64],
            'responsable_nombre' => ['type' => 'VARCHAR', 'constraint' => 191],
            'responsable_documento' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'responsable_direccion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'responsable_ciudad' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'canal_email' => ['type' => 'VARCHAR', 'constraint' => 191],
            'canal_telefono' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'oficial_nombre' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'oficial_cargo' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'configuracion'],
            'config_json' => ['type' => 'LONGTEXT', 'null' => true],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('cliente_id');
        $this->forge->addUniqueKey('public_token');
        $this->forge->createTable('dp_programas', true);
    }

    private function createBases(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 191],
            'codigo' => ['type' => 'VARCHAR', 'constraint' => 80],
            'responsable_interno' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'ubicacion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'medio' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'mixto'],
            'tipos_titular_json' => ['type' => 'LONGTEXT', 'null' => true],
            'categorias_datos_json' => ['type' => 'LONGTEXT', 'null' => true],
            'datos_sensibles' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'datos_menores' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'origen_datos' => ['type' => 'TEXT', 'null' => true],
            'finalidad_resumen' => ['type' => 'TEXT', 'null' => true],
            'fundamento' => ['type' => 'TEXT', 'null' => true],
            'retencion_meses' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'criterio_eliminacion' => ['type' => 'TEXT', 'null' => true],
            'medidas_seguridad' => ['type' => 'TEXT', 'null' => true],
            'rnbd_aplica' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'por_evaluar'],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addKey('cliente_id');
        $this->forge->addUniqueKey(['cliente_id', 'codigo']);
        $this->forge->createTable('dp_bases_datos', true);
    }

    private function createFinalidades(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'base_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'descripcion' => ['type' => 'TEXT'],
            'es_opcional' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'requiere_consentimiento_explicito' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'base_id']);
        $this->forge->createTable('dp_finalidades', true);
    }

    private function createTerceros(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 191],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'encargado'],
            'documento' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'pais' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'Colombia'],
            'contacto_email' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'servicio' => ['type' => 'TEXT', 'null' => true],
            'bases_json' => ['type' => 'LONGTEXT', 'null' => true],
            'contrato_vigente' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'medidas_verificadas' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addKey('cliente_id');
        $this->forge->createTable('dp_terceros', true);
    }

    private function createPlantillas(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'codigo' => ['type' => 'VARCHAR', 'constraint' => 80],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 191],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'contenido_html' => ['type' => 'LONGTEXT'],
            'bloques_bloqueados_json' => ['type' => 'LONGTEXT', 'null' => true],
            'variables_json' => ['type' => 'LONGTEXT', 'null' => true],
            'es_sistema' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['cliente_id', 'codigo', 'version']);
        $this->forge->createTable('dp_plantillas', true);
    }

    private function createDocumentos(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'plantilla_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'codigo' => ['type' => 'VARCHAR', 'constraint' => 100],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'titulo' => ['type' => 'VARCHAR', 'constraint' => 255],
            'version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'borrador'],
            'contenido_html' => ['type' => 'LONGTEXT'],
            'variables_json' => ['type' => 'LONGTEXT', 'null' => true],
            'hash_sha256' => ['type' => 'CHAR', 'constraint' => 64],
            'aprobado_por' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'aprobado_at' => ['type' => 'DATETIME', 'null' => true],
            'vigente_desde' => ['type' => 'DATE', 'null' => true],
            'publicado_at' => ['type' => 'DATETIME', 'null' => true],
            'pdf_ruta' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'tipo']);
        $this->forge->addUniqueKey(['cliente_id', 'codigo', 'version']);
        $this->forge->createTable('dp_documentos', true);
    }

    private function createConsentimientos(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'documento_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'tipo_titular' => ['type' => 'VARCHAR', 'constraint' => 50],
            'titular_nombre' => ['type' => 'VARCHAR', 'constraint' => 191],
            'titular_tipo_documento' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'titular_documento' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'titular_email' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'representante_nombre' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'decision' => ['type' => 'VARCHAR', 'constraint' => 30],
            'finalidades_aceptadas_json' => ['type' => 'LONGTEXT', 'null' => true],
            'finalidades_rechazadas_json' => ['type' => 'LONGTEXT', 'null' => true],
            'firma_imagen' => ['type' => 'LONGTEXT', 'null' => true],
            'pdf_ruta' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'evidencia_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'otorgado_at' => ['type' => 'DATETIME'],
            'revocado_at' => ['type' => 'DATETIME', 'null' => true],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'titular_documento']);
        $this->forge->createTable('dp_consentimientos', true);
    }

    private function createExclusiones(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'identificador_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'email_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'alcance' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'total'],
            'finalidades_json' => ['type' => 'LONGTEXT', 'null' => true],
            'origen' => ['type' => 'VARCHAR', 'constraint' => 50],
            'motivo' => ['type' => 'TEXT', 'null' => true],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['cliente_id', 'identificador_hash']);
        $this->forge->createTable('dp_exclusiones', true);
    }

    private function createSolicitudes(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'radicado' => ['type' => 'VARCHAR', 'constraint' => 50],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 30],
            'titular_nombre' => ['type' => 'VARCHAR', 'constraint' => 191],
            'titular_documento' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'titular_email' => ['type' => 'VARCHAR', 'constraint' => 191],
            'solicitud_texto' => ['type' => 'TEXT'],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'recibida'],
            'recibida_at' => ['type' => 'DATETIME'],
            'completa_at' => ['type' => 'DATETIME', 'null' => true],
            'vence_at' => ['type' => 'DATE', 'null' => true],
            'prorroga_hasta' => ['type' => 'DATE', 'null' => true],
            'reclamo_marcado_at' => ['type' => 'DATETIME', 'null' => true],
            'resultado' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'respuesta_texto' => ['type' => 'LONGTEXT', 'null' => true],
            'fundamento_conservacion' => ['type' => 'TEXT', 'null' => true],
            'cerrada_at' => ['type' => 'DATETIME', 'null' => true],
            'responsable_usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'estado']);
        $this->forge->addUniqueKey(['cliente_id', 'radicado']);
        $this->forge->createTable('dp_solicitudes', true);
    }

    private function createSolicitudBases(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'solicitud_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'base_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'accion' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'por_evaluar'],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pendiente'],
            'detalle' => ['type' => 'TEXT', 'null' => true],
            'evidencia' => ['type' => 'TEXT', 'null' => true],
            'ejecutado_por' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'ejecutado_at' => ['type' => 'DATETIME', 'null' => true],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['solicitud_id', 'base_id']);
        $this->forge->createTable('dp_solicitud_bases', true);
    }

    private function createSolicitudTerceros(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'solicitud_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'tercero_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pendiente'],
            'notificado_at' => ['type' => 'DATETIME', 'null' => true],
            'confirmado_at' => ['type' => 'DATETIME', 'null' => true],
            'evidencia' => ['type' => 'TEXT', 'null' => true],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['solicitud_id', 'tercero_id']);
        $this->forge->createTable('dp_solicitud_terceros', true);
    }

    private function createNotificaciones(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'solicitud_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'destinatario' => ['type' => 'VARCHAR', 'constraint' => 191],
            'asunto' => ['type' => 'VARCHAR', 'constraint' => 255],
            'plantilla' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'contenido_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'proveedor' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'sendgrid'],
            'proveedor_id' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pendiente'],
            'intentos' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'ultimo_error' => ['type' => 'TEXT', 'null' => true],
            'enviado_at' => ['type' => 'DATETIME', 'null' => true],
            'entregado_at' => ['type' => 'DATETIME', 'null' => true],
        ], $this->timestamps()));
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'estado']);
        $this->forge->addKey('proveedor_id');
        $this->forge->createTable('dp_notificaciones', true);
    }

    private function createNotificacionEventos(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'notificacion_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'evento' => ['type' => 'VARCHAR', 'constraint' => 50],
            'proveedor_evento_id' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'payload_json' => ['type' => 'LONGTEXT', 'null' => true],
            'ocurrido_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('notificacion_id');
        $this->forge->createTable('dp_notificacion_eventos', true);
    }

    private function createAiRuns(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'modelo' => ['type' => 'VARCHAR', 'constraint' => 80],
            'prompt_version' => ['type' => 'VARCHAR', 'constraint' => 30],
            'entrada_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'salida_json' => ['type' => 'LONGTEXT', 'null' => true],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30],
            'error' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'created_at']);
        $this->forge->createTable('dp_ai_runs', true);
    }

    private function createAuditoria(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'actor_tipo' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'usuario'],
            'accion' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entidad' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entidad_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'antes_json' => ['type' => 'LONGTEXT', 'null' => true],
            'despues_json' => ['type' => 'LONGTEXT', 'null' => true],
            'ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'created_at']);
        $this->forge->createTable('dp_auditoria', true);
    }
}
