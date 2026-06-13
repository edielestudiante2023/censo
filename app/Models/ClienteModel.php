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
    protected $validationRules  = [
        'nombre_tercero'    => 'required|max_length[191]',
        'tipo_documento'    => 'required|max_length[20]',
        'documento'         => 'permit_empty|max_length[30]',
        'direccion'         => 'permit_empty|max_length[191]',
        'ciudad'            => 'permit_empty|max_length[100]',
        'telefono'          => 'permit_empty|max_length[50]',
        'persona_contacto'  => 'permit_empty|max_length[191]',
        'email'             => 'permit_empty|valid_email|max_length[191]',
        'logo'              => 'permit_empty|max_length[255]',
        'color_primario'    => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        'color_secundario'  => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        'tipo_conjunto'     => 'required|in_list[casas,apartamentos,mixto]',
        'slug'              => 'required|alpha_dash|max_length[191]',
        'texto_habeas_data' => 'permit_empty',
        'activo'            => 'required|in_list[0,1]',
    ];

    public function findBySlug(string $slug)
    {
        return $this->where('slug', $slug)->first();
    }
}
