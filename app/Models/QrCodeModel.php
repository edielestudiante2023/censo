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
    protected $validationRules  = [
        'cliente_id'       => 'required|is_natural_no_zero',
        'tipo_instrumento' => 'required|in_list[poblacional,mascotas]',
        'token'            => 'required|alpha_dash|max_length[64]',
        'titulo'           => 'permit_empty|max_length[191]',
        'activo'           => 'required|in_list[0,1]',
    ];

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }

    public function findByToken(string $token)
    {
        return $this->where('token', $token)->where('activo', 1)->first();
    }
}
