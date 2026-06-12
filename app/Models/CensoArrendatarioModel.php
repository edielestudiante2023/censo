<?php

namespace App\Models;

use CodeIgniter\Model;

class CensoArrendatarioModel extends Model
{
    protected $table         = 'censo_arrendatarios';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['censo_id', 'nombre', 'documento', 'telefono', 'correo'];

    public function forCenso($censoId)
    {
        return $this->where('censo_id', $censoId);
    }
}
