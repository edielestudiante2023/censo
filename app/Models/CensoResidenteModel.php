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
    protected $allowedFields = ['censo_id', 'nombre', 'documento', 'sexo', 'parentesco_id', 'edad'];
    protected $validationRules = [
        'censo_id'       => 'required|is_natural_no_zero',
        'nombre'         => 'permit_empty|max_length[191]',
        'documento'      => 'permit_empty|max_length[30]',
        'sexo'           => 'permit_empty|in_list[M,F,Otro]',
        'parentesco_id'  => 'permit_empty|is_natural_no_zero',
        'edad'           => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[120]',
    ];

    public function forCenso($censoId)
    {
        return $this->where('censo_id', $censoId);
    }
}
