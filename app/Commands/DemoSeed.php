<?php

namespace App\Commands;

use App\Libraries\HabeasData;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Datos DEMO de muestra (cliente + censos) para ver el sistema poblado.
 * Uso: php spark demo:seed        -> crea el cliente demo con datos
 *      php spark demo:seed clean  -> borra el cliente demo (cascade)
 */
class DemoSeed extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'demo:seed';
    protected $description = 'Crea/borra un cliente demo con censos de muestra.';
    private string $slug   = 'demo-muestra';

    public function run(array $params)
    {
        $db = db_connect();

        if (($params[0] ?? '') === 'clean') {
            $db->table('clientes')->where('slug', $this->slug)->delete();
            CLI::write('Cliente demo de muestra eliminado.', 'yellow');

            return;
        }

        $db->table('clientes')->where('slug', $this->slug)->delete();
        $now = date('Y-m-d H:i:s');
        $anio = (int) date('Y');

        $db->table('clientes')->insert([
            'nombre_tercero' => 'Conjunto Residencial Demo', 'tipo_documento' => 'NIT', 'documento' => '901456789-0',
            'direccion' => 'Calle 123 #45-67', 'ciudad' => 'Bogota', 'telefono' => '6011234567',
            'persona_contacto' => 'Administracion Demo', 'email' => 'edison.cuervo@cycloidtalent.com',
            'tipo_conjunto' => 'apartamentos', 'slug' => $this->slug, 'color_primario' => '#0d6efd',
            'texto_habeas_data' => HabeasData::standard(),
            'activo' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $cid = (int) $db->insertID();

        // Torres + inmuebles
        $inmuebles = [];
        foreach (['Torre 1', 'Torre 2', 'Torre 3'] as $tn) {
            $db->table('torres')->insert(['cliente_id' => $cid, 'nombre' => $tn, 'num_pisos' => 3, 'created_at' => $now, 'updated_at' => $now]);
            $tid = (int) $db->insertID();
            foreach (['101', '102', '201', '202'] as $ap) {
                $db->table('inmuebles')->insert(['cliente_id' => $cid, 'torre_id' => $tid, 'tipo' => 'apartamento', 'identificador' => $ap, 'piso' => (int) $ap[0], 'created_at' => $now, 'updated_at' => $now]);
                $inmuebles[] = (int) $db->insertID();
            }
        }

        $par = array_column($db->table('parentescos')->select('id')->orderBy('id')->get()->getResultArray(), 'id');
        $tv  = array_column($db->table('tipos_vehiculo')->select('id')->orderBy('id')->get()->getResultArray(), 'id');
        $tm  = array_column($db->table('tipos_mascota')->select('id')->orderBy('id')->get()->getResultArray(), 'id');

        // 10 censos poblacionales (de 12 inmuebles -> cobertura ~83%)
        $hogares = [
            [['M', 38, 0], ['F', 36, 1], ['M', 10, 3], ['F', 7, 3]],
            [['F', 29, 0], ['M', 31, 1]],
            [['M', 65, 0], ['F', 63, 1], ['M', 28, 3]],
            [['F', 42, 0], ['M', 45, 1], ['F', 16, 3], ['M', 13, 3], ['F', 80, 4]],
            [['M', 24, 0]],
            [['F', 51, 0], ['M', 54, 1], ['Otro', 19, 3]],
            [['M', 33, 0], ['F', 30, 1], ['M', 2, 3]],
            [['F', 70, 0], ['M', 72, 1]],
            [['M', 47, 0], ['F', 44, 1], ['F', 21, 3], ['M', 18, 3]],
            [['F', 27, 0], ['M', 27, 1], ['M', 1, 3]],
        ];

        foreach ($hogares as $idx => $residentes) {
            $inmuebleId = $inmuebles[$idx];
            $tieneParq  = $idx % 2;
            $db->table('censos_poblacionales')->insert([
                'cliente_id' => $cid, 'anio' => $anio, 'inmueble_id' => $inmuebleId, 'autorizacion_datos' => 1, 'fecha_autorizacion' => $now,
                'vive_en_copropiedad' => 1, 'quien_vive' => 'Los propietarios', 'tiene_parqueadero' => $tieneParq,
                'correo_contacto' => 'demo' . $idx . '@correo.com', 'firmante_nombre' => 'Propietario ' . ($idx + 1),
                'ip' => '127.0.0.1', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $censoId = (int) $db->insertID();

            foreach ($residentes as $rIdx => [$sexo, $edad, $pIdx]) {
                $db->table('censo_residentes')->insert([
                    'censo_id' => $censoId, 'nombre' => 'Residente ' . ($idx + 1) . '-' . ($rIdx + 1),
                    'documento' => '10' . $idx . $rIdx . '0000', 'sexo' => $sexo,
                    'parentesco_id' => $par[$pIdx] ?? null, 'edad' => $edad, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $db->table('censo_propietarios')->insert(['censo_id' => $censoId, 'nombre' => 'Propietario ' . ($idx + 1), 'documento' => '11' . $idx . '00000', 'telefono' => '30012345' . $idx, 'correo' => 'demo' . $idx . '@correo.com', 'created_at' => $now, 'updated_at' => $now]);

            if ($tieneParq) {
                $db->table('censo_vehiculos')->insert(['censo_id' => $censoId, 'tipo_vehiculo_id' => $tv[$idx % count($tv)] ?? null, 'marca' => 'Marca' . $idx, 'linea' => 'Linea', 'modelo' => (string) (2015 + $idx), 'color' => 'Gris', 'placa' => 'ABC' . (100 + $idx), 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // 4 censos de mascotas
        foreach ([0, 2, 4, 6] as $j => $inmIdx) {
            $db->table('censos_mascotas')->insert([
                'cliente_id' => $cid, 'anio' => $anio, 'inmueble_id' => $inmuebles[$inmIdx], 'autorizacion_datos' => 1, 'fecha_autorizacion' => $now,
                'responsable_nombre' => 'Responsable ' . ($j + 1), 'responsable_documento' => '20' . $j . '00000',
                'responsable_correo' => 'mascota' . $j . '@correo.com', 'firmante_nombre' => 'Responsable ' . ($j + 1),
                'ip' => '127.0.0.1', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $cmId = (int) $db->insertID();
            $n    = $j % 2 === 0 ? 2 : 1;
            for ($k = 0; $k < $n; $k++) {
                $db->table('mascotas')->insert(['censo_mascota_id' => $cmId, 'nombre' => 'Mascota ' . $j . '-' . $k, 'tipo_mascota_id' => $tm[($j + $k) % count($tm)] ?? null, 'edad' => (1 + $k) . ' anios', 'raza_color' => 'Criollo', 'vacunacion_completa' => 1, 'esterilizada' => $k % 2, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // QR para ambos instrumentos
        foreach (['poblacional', 'mascotas'] as $tipo) {
            $db->table('qr_codes')->insert(['cliente_id' => $cid, 'tipo_instrumento' => $tipo, 'anio' => $anio, 'token' => bin2hex(random_bytes(16)), 'titulo' => 'Demo ' . $tipo . ' ' . $anio, 'activo' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        CLI::write('Cliente demo creado: id=' . $cid . ' (slug ' . $this->slug . ')', 'green');
        CLI::write('Hogares: ' . count($hogares) . ' / inmuebles: ' . count($inmuebles));
    }
}
