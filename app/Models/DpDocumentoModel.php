<?php

namespace App\Models;

use CodeIgniter\Model;

class DpDocumentoModel extends Model
{
    protected $table = 'dp_documentos';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'cliente_id', 'plantilla_id', 'codigo', 'tipo', 'titulo', 'version', 'estado',
        'contenido_html', 'variables_json', 'hash_sha256', 'aprobado_por', 'aprobado_at',
        'vigente_desde', 'publicado_at', 'pdf_ruta',
    ];
}
