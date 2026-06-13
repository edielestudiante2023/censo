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
    protected $validationRules = [
        'censo_id'   => 'required|is_natural_no_zero',
        'nombre'     => 'permit_empty|max_length[191]',
        'documento'  => 'permit_empty|max_length[30]',
        'telefono'   => 'permit_empty|max_length[50]',
        'correo'     => 'permit_empty|valid_email|max_length[191]',
    ];

    public function forCenso($censoId)
    {
        return $this->where('censo_id', $censoId);
    }
}
