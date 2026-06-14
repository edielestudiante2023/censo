<?php

namespace App\Models;

use CodeIgniter\Model;

class MascotaModel extends Model
{
    protected $table         = 'mascotas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = [
        'censo_mascota_id', 'censo_poblacional_id', 'nombre', 'tipo_mascota_id', 'edad', 'raza_color',
        'vacunacion_completa', 'esterilizada', 'foto_ruta', 'foto_carne_ruta', 'foto_poliza_ruta',
    ];
    protected $validationRules = [
        'censo_mascota_id'    => 'permit_empty|is_natural_no_zero',
        'censo_poblacional_id' => 'permit_empty|is_natural_no_zero',
        'nombre'              => 'permit_empty|max_length[100]',
        'tipo_mascota_id'     => 'permit_empty|is_natural_no_zero',
        'edad'                => 'permit_empty|max_length[30]',
        'raza_color'          => 'permit_empty|max_length[150]',
        'vacunacion_completa' => 'permit_empty|in_list[0,1]',
        'esterilizada'        => 'permit_empty|in_list[0,1]',
        'foto_ruta'           => 'permit_empty|max_length[255]',
        'foto_carne_ruta'     => 'permit_empty|max_length[255]',
        'foto_poliza_ruta'    => 'permit_empty|max_length[255]',
    ];

    public function forCenso($censoMascotaId)
    {
        return $this->where('censo_mascota_id', $censoMascotaId);
    }

    public function forCensoPoblacional($censoPoblacionalId)
    {
        return $this->where('censo_poblacional_id', $censoPoblacionalId);
    }
}
