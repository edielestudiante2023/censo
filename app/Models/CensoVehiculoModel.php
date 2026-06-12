<?php

namespace App\Models;

use CodeIgniter\Model;

class CensoVehiculoModel extends Model
{
    protected $table         = 'censo_vehiculos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['censo_id', 'tipo_vehiculo_id', 'marca', 'linea', 'modelo', 'color', 'placa'];

    public function forCenso($censoId)
    {
        return $this->where('censo_id', $censoId);
    }
}
