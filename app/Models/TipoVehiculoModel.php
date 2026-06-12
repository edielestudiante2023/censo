<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoVehiculoModel extends Model
{
    protected $table         = 'tipos_vehiculo';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['nombre', 'activo'];
}
