<?php

namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $table            = 'clientes';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = [
        'nombre_tercero', 'tipo_documento', 'documento', 'direccion', 'ciudad',
        'telefono', 'persona_contacto', 'email', 'logo', 'color_primario',
        'color_secundario', 'tipo_conjunto', 'slug', 'texto_habeas_data', 'activo',
    ];

    public function findBySlug(string $slug)
    {
        return $this->where('slug', $slug)->first();
    }
}
