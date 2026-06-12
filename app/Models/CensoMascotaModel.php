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
        'cliente_id', 'qr_id', 'inmueble_id', 'autorizacion_datos', 'fecha_autorizacion',
        'responsable_nombre', 'responsable_documento', 'responsable_telefono', 'responsable_correo',
        'firmante_nombre', 'firma_imagen', 'pdf_ruta', 'pdf_enviado', 'fecha_envio', 'ip', 'user_agent',
    ];

    public function forCliente($clienteId)
    {
        return $this->where('cliente_id', $clienteId);
    }
}
