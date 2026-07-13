<?php

namespace App\Libraries;

use App\Models\DpBaseDatosModel;
use App\Models\DpProgramaModel;

final class PrivacyProgramService
{
    private const DEFAULT_BASES = [
        ['Residentes', 'residentes', ['Residentes', 'Arrendatarios'], ['Identificacion', 'Contacto', 'Inmueble', 'Convivencia'], 'Administracion de la copropiedad, comunicaciones, seguridad y convivencia.'],
        ['Propietarios', 'propietarios', ['Propietarios'], ['Identificacion', 'Contacto', 'Inmueble', 'Informacion financiera'], 'Gestion de propiedad horizontal, asambleas, cartera y comunicaciones.'],
        ['Proveedores y contratistas', 'proveedores', ['Proveedores', 'Contratistas'], ['Identificacion', 'Contacto', 'Contractual', 'Financiera'], 'Seleccion, contratacion, pagos, control de acceso y cumplimiento.'],
        ['Empleados', 'empleados', ['Empleados', 'Aspirantes'], ['Identificacion', 'Contacto', 'Laboral', 'Financiera', 'Salud'], 'Gestion laboral, nomina, seguridad social, seleccion y obligaciones legales.'],
        ['Visitantes', 'visitantes', ['Visitantes'], ['Identificacion', 'Contacto', 'Imagen', 'Registro de acceso'], 'Control de acceso, seguridad de personas y bienes y atencion de incidentes.'],
    ];

    public function initialize(array $cliente): array
    {
        $db = db_connect();
        $db->transStart();

        $programModel = new DpProgramaModel();
        $program = $programModel->where('cliente_id', $cliente['id'])->first();
        if (! $program) {
            $id = $programModel->insert([
                'cliente_id' => $cliente['id'],
                'public_token' => bin2hex(random_bytes(24)),
                'responsable_nombre' => $cliente['nombre_tercero'],
                'responsable_documento' => $cliente['documento'] ?? null,
                'responsable_direccion' => $cliente['direccion'] ?? null,
                'responsable_ciudad' => $cliente['ciudad'] ?? null,
                'canal_email' => $cliente['email'] ?: 'privacidad@por-configurar.local',
                'canal_telefono' => $cliente['telefono'] ?? null,
                'oficial_nombre' => $cliente['persona_contacto'] ?? 'Administracion',
                'oficial_cargo' => 'Responsable de proteccion de datos',
                'estado' => 'configuracion',
                'config_json' => json_encode(['backup_rotation_days' => 90], JSON_UNESCAPED_UNICODE),
            ], true);
            $program = $programModel->find($id);
        }

        $baseModel = new DpBaseDatosModel();
        if ($baseModel->where('cliente_id', $cliente['id'])->countAllResults() === 0) {
            foreach (self::DEFAULT_BASES as [$name, $code, $holders, $categories, $summary]) {
                $baseId = $baseModel->insert([
                    'cliente_id' => $cliente['id'],
                    'nombre' => $name,
                    'codigo' => $code,
                    'medio' => 'mixto',
                    'tipos_titular_json' => json_encode($holders, JSON_UNESCAPED_UNICODE),
                    'categorias_datos_json' => json_encode($categories, JSON_UNESCAPED_UNICODE),
                    'datos_sensibles' => in_array('Salud', $categories, true) ? 1 : 0,
                    'origen_datos' => 'Titular, representante autorizado y fuentes permitidas por la ley.',
                    'finalidad_resumen' => $summary,
                    'criterio_eliminacion' => 'Al finalizar la finalidad y vencer las obligaciones aplicables.',
                    'medidas_seguridad' => 'Acceso por rol, confidencialidad, respaldo, trazabilidad y eliminacion controlada.',
                    'rnbd_aplica' => 'por_evaluar',
                    'activo' => 1,
                ], true);
                $db->table('dp_finalidades')->insert([
                    'cliente_id' => $cliente['id'], 'base_id' => $baseId,
                    'descripcion' => $summary, 'es_opcional' => 0,
                    'requiere_consentimiento_explicito' => in_array('Salud', $categories, true) ? 1 : 0,
                    'activo' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            throw new \RuntimeException('No fue posible inicializar el programa de datos personales.');
        }

        $bases = $baseModel->where('cliente_id', $cliente['id'])->where('activo', 1)->findAll();
        $finalidades = $db->table('dp_finalidades')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray();
        (new PrivacyDocumentService())->generateSet($cliente, $program, $bases, $finalidades, true);
        PrivacyAudit::record((int) $cliente['id'], 'inicializar', 'programa', (int) $program['id'], null, ['bases' => count($bases)]);

        return $program;
    }
}
