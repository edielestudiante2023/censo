<?php

namespace App\Models;

use App\Models\Concerns\PrivacyEncryptedModel;
use CodeIgniter\Model;

class DpSolicitudModel extends Model
{
    use PrivacyEncryptedModel;

    protected $table = 'dp_solicitudes';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'cliente_id', 'radicado', 'tipo', 'clasificacion_original', 'reclasificacion_motivo', 'reclasificada_at',
        'titular_nombre', 'titular_documento', 'titular_email', 'canal', 'calidad_solicitante',
        'legitimacion_tipo', 'legitimacion_evidencia', 'solicitud_texto', 'estado', 'identidad_estado',
        'identidad_verificada_at', 'subsanacion_solicitada_at', 'subsanacion_detalle',
        'subsanacion_limite_at', 'subsanada_at', 'desistida_at', 'traslado_destinatario', 'trasladada_at',
        'traslado_notificado_at', 'recibida_at', 'fecha_ingreso_real', 'fecha_recepcion_legal', 'acuse_at',
        'acuse_hash', 'completa_at', 'vence_at', 'prorroga_hasta', 'prorroga_motivo',
        'prorroga_notificada_at', 'reclamo_marcado_at', 'reclamo_motivo', 'leyenda_retirada_at',
        'resultado', 'respuesta_texto', 'respuesta_hash', 'fundamento_conservacion', 'datos_conservados',
        'conservacion_hasta', 'cerrada_at', 'vencimiento_registrado_at', 'vencimiento_causa',
        'responsable_usuario_id',
        'titular_documento_bidx', 'titular_email_bidx',
    ];
    protected $beforeInsert = ['privacyEncryptCallback'];
    protected $beforeUpdate = ['privacyEncryptCallback'];
    protected $afterFind = ['privacyDecryptCallback'];
}
