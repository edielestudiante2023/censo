<?php

namespace App\Models;

use CodeIgniter\Model;

class CensoMascotaModel extends Model
{
    protected $table            = 'censos_mascotas';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = [
        'cliente_id', 'qr_id', 'anio', 'inmueble_id', 'autorizacion_datos', 'fecha_autorizacion',
        'responsable_nombre', 'responsable_documento', 'responsable_telefono', 'responsable_correo',
        'firmante_nombre', 'firma_imagen', 'pdf_ruta', 'pdf_enviado', 'fecha_envio', 'ip', 'user_agent',
    ];
    protected $validationRules  = [
        'cliente_id'            => 'required|is_natural_no_zero',
        'qr_id'                 => 'permit_empty|is_natural_no_zero',
        'anio'                  => 'permit_empty|integer|greater_than_equal_to[2020]|less_than_equal_to[2100]',
        'inmueble_id'           => 'required|is_natural_no_zero',
        'autorizacion_datos'    => 'required|in_list[0,1]',
        'fecha_autorizacion'    => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'responsable_nombre'    => 'permit_empty|max_length[191]',
        'responsable_documento' => 'permit_empty|max_length[30]',
        'responsable_telefono'  => 'permit_empty|max_length[50]',
        'responsable_correo'    => 'permit_empty|valid_email|max_length[191]',
        'firmante_nombre'       => 'permit_empty|max_length[191]',
        'firma_imagen'          => 'permit_empty|max_length[255]',
        'pdf_ruta'              => 'permit_empty|max_length[255]',
        'pdf_enviado'           => 'permit_empty|in_list[0,1]',
        'fecha_envio'           => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'ip'                    => 'permit_empty|max_length[45]',
        'user_agent'            => 'permit_empty|max_length[255]',
    ];

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }
}
