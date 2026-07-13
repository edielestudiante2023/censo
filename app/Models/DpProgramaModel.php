<?php

namespace App\Models;

use App\Models\Concerns\PrivacyEncryptedModel;
use CodeIgniter\Model;

class DpProgramaModel extends Model
{
    use PrivacyEncryptedModel;

    protected $table = 'dp_programas';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'cliente_id', 'public_token', 'responsable_nombre', 'responsable_documento',
        'responsable_direccion', 'responsable_ciudad', 'canal_email', 'canal_telefono',
        'oficial_nombre', 'oficial_cargo', 'estado', 'config_json',
    ];
    protected $beforeInsert = ['privacyEncryptCallback'];
    protected $beforeUpdate = ['privacyEncryptCallback'];
    protected $afterFind = ['privacyDecryptCallback'];
}
