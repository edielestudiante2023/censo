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
    protected $validationRules = [
        'censo_id'          => 'required|is_natural_no_zero',
        'tipo_vehiculo_id'  => 'permit_empty|is_natural_no_zero',
        'marca'             => 'permit_empty|max_length[100]',
        'linea'             => 'permit_empty|max_length[100]',
        'modelo'            => 'permit_empty|max_length[20]',
        'color'             => 'permit_empty|max_length[50]',
        'placa'             => 'permit_empty|max_length[20]',
    ];

    public function forCenso($censoId)
    {
        return $this->where('censo_id', $censoId);
    }
}
