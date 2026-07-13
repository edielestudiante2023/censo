<?php

namespace App\Models;

use App\Models\Concerns\PrivacyEncryptedModel;
use CodeIgniter\Model;

class DpNotificacionModel extends Model
{
    use PrivacyEncryptedModel;

    protected $table = 'dp_notificaciones';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'cliente_id', 'solicitud_id', 'tipo', 'destinatario', 'asunto', 'plantilla',
        'contenido_hash', 'proveedor', 'proveedor_id', 'estado', 'intentos',
        'ultimo_error', 'enviado_at', 'entregado_at',
        'destinatario_bidx',
    ];
    protected $beforeInsert = ['privacyEncryptCallback'];
    protected $beforeUpdate = ['privacyEncryptCallback'];
    protected $afterFind = ['privacyDecryptCallback'];
}
