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

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }
}
