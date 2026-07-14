<?php

namespace App\Models;

use App\Models\Concerns\PrivacyEncryptedModel;
use CodeIgniter\Model;

class DpConsentimientoModel extends Model
{
    use PrivacyEncryptedModel;

    protected $table = 'dp_consentimientos';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'cliente_id', 'inmueble_id', 'documento_id', 'documento_version', 'documento_hash', 'instancia_html', 'instancia_hash',
        'tipo_titular', 'titular_nombre',
        'titular_tipo_documento', 'titular_documento', 'titular_email',
        'representante_nombre', 'calidad_otorgante', 'representante_documento', 'calidad_representacion',
        'soporte_representacion_ruta', 'soporte_representacion_hash', 'opinion_menor', 'decision', 'decision_vector_json', 'finalidades_aceptadas_json',
        'finalidades_rechazadas_json', 'firma_imagen', 'pdf_ruta', 'evidencia_hash', 'ip',
        'user_agent', 'canal', 'tipo_evidencia', 'verificacion_identidad', 'zona_horaria', 'otorgado_at', 'revocado_at',
        'titular_documento_bidx', 'titular_email_bidx',
    ];
    protected $beforeInsert = ['privacyEncryptCallback'];
    protected $beforeUpdate = ['privacyEncryptCallback'];
    protected $afterFind = ['privacyDecryptCallback'];
}
