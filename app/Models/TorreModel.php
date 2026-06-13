<?php

namespace App\Models;

use CodeIgniter\Model;

class TorreModel extends Model
{
    protected $table            = 'torres';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = ['cliente_id', 'nombre', 'num_pisos'];
    protected $validationRules  = [
        'cliente_id' => 'required|is_natural_no_zero',
        'nombre'     => 'required|max_length[100]',
        'num_pisos'  => 'permit_empty|integer',
    ];

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }
}
