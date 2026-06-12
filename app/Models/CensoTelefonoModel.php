<?php

namespace App\Models;

use CodeIgniter\Model;

class CensoTelefonoModel extends Model
{
    protected $table         = 'censo_telefonos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['censo_id', 'numero', 'orden'];

    public function forCenso($censoId)
    {
        return $this->where('censo_id', $censoId);
    }
}
