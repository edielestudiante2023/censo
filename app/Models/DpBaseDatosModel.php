<?php

namespace App\Models;

use App\Models\Concerns\PrivacyEncryptedModel;
use CodeIgniter\Model;

class DpBaseDatosModel extends Model
{
    use PrivacyEncryptedModel;

    protected $table = 'dp_bases_datos';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'cliente_id', 'nombre', 'codigo', 'responsable_interno', 'ubicacion', 'medio',
        'tipos_titular_json', 'categorias_datos_json', 'datos_sensibles', 'datos_biometricos', 'datos_menores',
        'soportes_ubicacion', 'revisado_at',
        'origen_datos', 'finalidad_resumen', 'fundamento', 'retencion_meses',
        'criterio_eliminacion', 'medidas_seguridad', 'rnbd_aplica', 'activo',
    ];
    protected $beforeInsert = ['privacyEncryptCallback'];
    protected $beforeUpdate = ['privacyEncryptCallback'];
    protected $afterFind = ['privacyDecryptCallback'];
}
