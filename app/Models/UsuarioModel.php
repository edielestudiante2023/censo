<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = [
        'cliente_id', 'rol_id', 'nombre', 'email', 'password_hash',
        'telefono', 'activo', 'last_login',
    ];
    protected $validationRules  = [
        'cliente_id'     => 'permit_empty|is_natural_no_zero',
        'rol_id'         => 'required|is_natural_no_zero',
        'nombre'         => 'required|max_length[191]',
        'email'          => 'required|valid_email|max_length[191]',
        'password_hash'  => 'required|max_length[255]',
        'telefono'       => 'permit_empty|max_length[50]',
        'activo'         => 'required|in_list[0,1]',
        'last_login'     => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    /** Filtra por tenant (conjunto). */
    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }

    public function findByEmail(string $email)
    {
        return $this->where('email', $email)->first();
    }
}
