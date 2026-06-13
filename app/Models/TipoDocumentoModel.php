<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoDocumentoModel extends Model
{
    protected $table         = 'tipos_documento';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['nombre', 'activo'];
    protected $validationRules = [
        'nombre' => 'required|max_length[100]',
        'activo' => 'required|in_list[0,1]',
    ];
}
