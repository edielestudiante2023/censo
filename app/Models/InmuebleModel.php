<?php

namespace App\Models;

use CodeIgniter\Model;

class InmuebleModel extends Model
{
    protected $table            = 'inmuebles';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = ['cliente_id', 'torre_id', 'tipo', 'identificador', 'piso'];
    protected $validationRules  = [
        'cliente_id'     => 'required|is_natural_no_zero',
        'torre_id'       => 'permit_empty|is_natural_no_zero',
        'tipo'           => 'required|in_list[casa,apartamento]',
        'identificador'  => 'required|max_length[50]',
        'piso'           => 'permit_empty|integer',
    ];

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }
}
