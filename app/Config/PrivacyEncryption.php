<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * M-1: fuente unica de verdad del cifrado en reposo del modulo de proteccion de datos.
 *
 * Cada tabla declara:
 *  - encrypt: columnas de texto cifradas con AEAD (PrivacyCipher).
 *  - blind:   columna_origen => ['col' => columna_indice, 'type' => documento|email|texto]
 *             (indice ciego HMAC para busquedas por igualdad; el origen va cifrado).
 *
 * NO se listan aqui: ids, claves foraneas, *_hash ya existentes, codigo_hash, tokens
 * aleatorios, flags, estados, fechas de proceso. Los maestros dp_documentos/dp_aviso_*
 * (contenido_html) se dejan en claro: son plantillas institucionales, no PII de titulares,
 * y su hash_sha256 es su evidencia de integridad.
 */
class PrivacyEncryption extends BaseConfig
{
    /**
     * Bandera temporal de transicion: permite que los lookups por indice ciego
     * caigan a busqueda por texto plano mientras existan filas sin migrar.
     * Debe apagarse (false) una vez completada y verificada la migracion.
     */
    public bool $blindIndexFallback = false;

    /**
     * @var array<string, array{encrypt: list<string>, blind?: array<string, array{col: string, type: string}>}>
     */
    public array $tables = [
        'dp_solicitudes' => [
            'encrypt' => [
                'titular_nombre', 'titular_documento', 'titular_email', 'solicitud_texto',
                'reclamo_motivo', 'subsanacion_detalle', 'traslado_destinatario', 'respuesta_texto',
                'fundamento_conservacion', 'datos_conservados', 'vencimiento_causa',
                'legitimacion_evidencia', 'reclasificacion_motivo',
            ],
            'blind' => [
                'titular_documento' => ['col' => 'titular_documento_bidx', 'type' => 'documento'],
                'titular_email' => ['col' => 'titular_email_bidx', 'type' => 'email'],
            ],
        ],
        'dp_consentimientos' => [
            'encrypt' => [
                'instancia_html', 'titular_nombre', 'titular_documento', 'titular_email',
                'representante_nombre', 'representante_documento', 'opinion_menor',
                'decision_vector_json', 'finalidades_aceptadas_json', 'finalidades_rechazadas_json',
                'firma_imagen', 'ip', 'user_agent',
            ],
            'blind' => [
                'titular_documento' => ['col' => 'titular_documento_bidx', 'type' => 'documento'],
                'titular_email' => ['col' => 'titular_email_bidx', 'type' => 'email'],
            ],
        ],
        'dp_consentimiento_verificaciones' => [
            'encrypt' => ['email', 'datos_json'],
            'blind' => ['email' => ['col' => 'email_bidx', 'type' => 'email']],
        ],
        'dp_consentimiento_eventos' => [
            'encrypt' => ['alcance_json', 'ip', 'user_agent'],
        ],
        'dp_solicitud_eventos' => [
            'encrypt' => ['detalle_json'],
        ],
        'dp_incidente_eventos' => [
            'encrypt' => ['detalle_json'],
        ],
        'dp_compromiso_eventos' => [
            'encrypt' => ['detalle_json'],
        ],
        'dp_acuerdo_encargado_eventos' => [
            'encrypt' => ['detalle_json'],
        ],
        'dp_compromisos_confidencialidad' => [
            'encrypt' => [
                'instancia_html', 'firmante_nombre', 'numero_documento', 'autorizador_nombre',
                'alcance_total_justificacion', 'firma_imagen', 'ip', 'user_agent', 'cierre_motivo', 'devolucion_evidencia',
            ],
            'blind' => ['numero_documento' => ['col' => 'numero_documento_bidx', 'type' => 'documento']],
        ],
        'dp_acuerdos_encargado' => [
            'encrypt' => [
                'instancia_html', 'variables_json', 'responsable_firmante', 'responsable_firma',
                'encargado_firma', 'responsable_ip', 'encargado_ip', 'encargado_user_agent', 'terminacion_motivo',
            ],
        ],
        'dp_terceros' => [
            'encrypt' => [
                'nombre', 'documento', 'contacto_email', 'representante_nombre', 'representante_documento',
                'representante_email', 'clasificacion_justificacion', 'clasificacion_json',
                'contrato_principal_ref', 'contrato_principal_objeto', 'servicio', 'titulares_json',
                'debida_diligencia_evidencia', 'seguro_evidencia', 'contrato_evidencia', 'representacion_evidencia',
            ],
        ],
        'dp_subencargados' => [
            'encrypt' => ['nombre', 'documento', 'servicio', 'datos_json', 'contrato_evidencia'],
        ],
        'dp_notificaciones' => [
            'encrypt' => ['destinatario', 'asunto', 'ultimo_error'],
            'blind' => ['destinatario' => ['col' => 'destinatario_bidx', 'type' => 'email']],
        ],
        'dp_notificacion_eventos' => [
            'encrypt' => ['payload_json'],
        ],
        'dp_auditoria' => [
            'encrypt' => ['antes_json', 'despues_json', 'ip', 'user_agent'],
        ],
        'dp_ai_runs' => [
            'encrypt' => ['salida_json', 'error'],
        ],
        'dp_solicitud_bases' => [
            'encrypt' => ['detalle', 'evidencia', 'valor_nuevo', 'fuente_correccion'],
        ],
        'dp_solicitud_terceros' => [
            'encrypt' => ['respuesta_detalle', 'evidencia'],
        ],
        'dp_incidentes_privacidad' => [
            'encrypt' => [
                'detalle', 'fuente', 'investigacion', 'categorias_afectadas', 'decision_motivo',
                'sic_evidencia', 'titulares_comunicacion_motivo', 'lecciones',
            ],
        ],
        'dp_encargado_instrucciones' => [
            'encrypt' => ['contenido', 'objecion'],
        ],
        'dp_encargado_certificaciones' => [
            'encrypt' => ['detalle_json', 'evidencia'],
        ],
        'dp_controles_seguridad' => [
            'encrypt' => ['detalle', 'evidencia'],
        ],
        'dp_asignaciones_seguridad' => [
            'encrypt' => ['responsable', 'acto_designacion'],
        ],
        'dp_programas' => [
            'encrypt' => ['oficial_nombre', 'canal_telefono', 'config_json'],
        ],
        'dp_bases_datos' => [
            'encrypt' => ['responsable_interno'],
        ],
        'usuario_mfa_desafios' => [
            'encrypt' => ['ip'],
        ],
    ];

    /**
     * Campos cuyo hash probatorio se calcula sobre el texto plano descifrado.
     * Se verifican durante la migracion (antes/despues) y al leer.
     *
     * @var array<string, array{plain: string, hash: string}>
     */
    public array $hashInvariants = [
        'dp_consentimientos' => ['plain' => 'instancia_html', 'hash' => 'instancia_hash'],
        'dp_compromisos_confidencialidad' => ['plain' => 'instancia_html', 'hash' => 'instancia_hash'],
        'dp_acuerdos_encargado' => ['plain' => 'instancia_html', 'hash' => 'instancia_hash'],
    ];

    /**
     * @return list<string>
     */
    public function encryptFields(string $table): array
    {
        return $this->tables[$table]['encrypt'] ?? [];
    }

    /**
     * @return array<string, array{col: string, type: string}>
     */
    public function blindFields(string $table): array
    {
        return $this->tables[$table]['blind'] ?? [];
    }
}
