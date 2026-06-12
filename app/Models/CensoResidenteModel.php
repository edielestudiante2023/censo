<?php

namespace App\Models;

use CodeIgniter\Model;

class CensoResidenteModel extends Model
{
    protected $table         = 'censo_residentes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['censo_id', 'nombre', 'documento', 'parentesco_id', 'edad'];

    public function forCenso($censoId)
    {
        return $this->where('censo_id', $censoId);
    }
}
