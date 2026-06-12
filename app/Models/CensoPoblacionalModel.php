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
        'cliente_id', 'qr_id', 'inmueble_id', 'autorizacion_datos', 'fecha_autorizacion',
        'vive_en_copropiedad', 'direccion_notificacion', 'quien_vive', 'administrado_por',
        'inmobiliaria_nombre', 'inmobiliaria_telefono', 'inmobiliaria_correo', 'correo_contacto',
        'discapacidad_descripcion', 'tiene_parqueadero', 'observaciones', 'firmante_nombre',
        'firma_imagen', 'pdf_ruta', 'pdf_enviado', 'fecha_envio', 'ip', 'user_agent',
    ];

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }
}
