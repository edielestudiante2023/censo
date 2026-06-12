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
        'censo_mascota_id', 'nombre', 'tipo_mascota_id', 'edad', 'raza_color',
        'vacunacion_completa', 'esterilizada', 'foto_ruta', 'foto_carne_ruta', 'foto_poliza_ruta',
    ];

    public function forCenso($censoMascotaId)
    {
        return $this->where('censo_mascota_id', $censoMascotaId);
    }
}
