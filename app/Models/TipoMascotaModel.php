<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoMascotaModel extends Model
{
    protected $table         = 'tipos_mascota';
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
