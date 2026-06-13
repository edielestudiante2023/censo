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
    protected $validationRules = [
        'censo_id'  => 'required|is_natural_no_zero',
        'numero'    => 'required|max_length[50]',
        'orden'     => 'permit_empty|integer|greater_than_equal_to[1]',
    ];

    public function forCenso($censoId)
    {
        return $this->where('censo_id', $censoId);
    }
}
