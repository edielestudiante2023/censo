<?php

namespace App\Models;

use CodeIgniter\Model;

class ParentescoModel extends Model
{
    protected $table         = 'parentescos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['nombre', 'activo'];
}
