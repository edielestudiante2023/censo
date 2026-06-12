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
