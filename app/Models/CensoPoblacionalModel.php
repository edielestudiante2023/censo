<?php

namespace App\Models;

use CodeIgniter\Model;

class CensoPoblacionalModel extends Model
{
    protected $table            = 'censos_poblacionales';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = [
        'cliente_id', 'qr_id', 'anio', 'inmueble_id', 'autorizacion_datos', 'fecha_autorizacion',
        'vive_en_copropiedad', 'direccion_notificacion', 'quien_vive', 'administrado_por',
        'inmobiliaria_nombre', 'inmobiliaria_telefono', 'inmobiliaria_correo', 'correo_contacto',
        'discapacidad_descripcion', 'tiene_mascotas', 'tiene_parqueadero', 'observaciones', 'firmante_nombre',
        'firma_imagen', 'pdf_ruta', 'pdf_enviado', 'fecha_envio', 'ip', 'user_agent',
    ];
    protected $validationRules  = [
        'cliente_id'             => 'required|is_natural_no_zero',
        'qr_id'                  => 'permit_empty|is_natural_no_zero',
        'anio'                   => 'permit_empty|integer|greater_than_equal_to[2020]|less_than_equal_to[2100]',
        'inmueble_id'            => 'required|is_natural_no_zero',
        'autorizacion_datos'     => 'required|in_list[0,1]',
        'fecha_autorizacion'     => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'vive_en_copropiedad'    => 'permit_empty|in_list[0,1]',
        'direccion_notificacion' => 'permit_empty|max_length[255]',
        'quien_vive'             => 'permit_empty|max_length[100]',
        'administrado_por'       => 'permit_empty|in_list[inmobiliaria,persona_natural]',
        'inmobiliaria_nombre'    => 'permit_empty|max_length[191]',
        'inmobiliaria_telefono'  => 'permit_empty|max_length[50]',
        'inmobiliaria_correo'    => 'permit_empty|valid_email|max_length[191]',
        'correo_contacto'        => 'permit_empty|valid_email|max_length[191]',
        'discapacidad_descripcion' => 'permit_empty',
        'tiene_mascotas'         => 'permit_empty|in_list[0,1]',
        'tiene_parqueadero'      => 'permit_empty|in_list[0,1]',
        'observaciones'          => 'permit_empty',
        'firmante_nombre'        => 'permit_empty|max_length[191]',
        'firma_imagen'           => 'permit_empty|max_length[255]',
        'pdf_ruta'               => 'permit_empty|max_length[255]',
        'pdf_enviado'            => 'permit_empty|in_list[0,1]',
        'fecha_envio'            => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'ip'                     => 'permit_empty|max_length[45]',
        'user_agent'             => 'permit_empty|max_length[255]',
    ];

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }
}
