<?php

namespace App\Models;

use CodeIgniter\Model;

class QrCodeModel extends Model
{
    protected $table            = 'qr_codes';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = ['cliente_id', 'tipo_instrumento', 'token', 'titulo', 'activo'];

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }

    public function findByToken(string $token)
    {
        return $this->where('token', $token)->where('activo', 1)->first();
    }
}
