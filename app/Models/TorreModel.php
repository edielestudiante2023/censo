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

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }
}
