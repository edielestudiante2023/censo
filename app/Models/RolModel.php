<?php

namespace App\Models;

use CodeIgniter\Model;

class RolModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = ['nombre', 'descripcion'];
    protected $validationRules  = [
        'nombre'      => 'required|alpha_dash|max_length[50]',
        'descripcion' => 'permit_empty|max_length[191]',
    ];
}
