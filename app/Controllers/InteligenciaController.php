<?php

namespace App\Controllers;

use App\Models\ClienteModel;

class InteligenciaController extends BaseController
{
    private const FILTER_KEYS = [
        'anio', 'torre_id', 'tipo', 'sexo', 'edad', 'parentesco_id',
        'fecha_desde', 'fecha_hasta', 'tiene_mascotas', 'tipo_mascota_id',
        'tiene_vehiculos', 'tipo_vehiculo_id', 'tiene_parqueadero', 'tiene_discapacidad',
    ];

    /** Buckets de edad: clave => [min, max|null]. */
    private array $ageBuckets = [
        '0-12'  => [0, 12],
        '13-17' => [13, 17],
        '18-29' => [18, 29],
        '30-44' => [30, 44],
        '45-59' => [45, 59],
        '60+'   => [60, null],
    ];

    public function mine()
    {
        $cliente = $this->currentCliente();
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Cliente no encontrado.');
        }

        return view('inteligencia/index', $this->build($cliente, false));
    }

    public function admin(int $clienteId)
    {
        $cliente = (new ClienteModel())->find($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return view('inteligencia/index', $this->build($cliente, true));
    }

    public function exportMine()
    {
        $cliente = $this->currentCliente();
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportCsv($cliente);
    }

    public function exportAdmin(int $clienteId)
    {
        $cliente = (new ClienteModel())->find($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportCsv($cliente);
    }

    public function excelMine()
    {
        $cliente = $this->currentCliente();
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportExcel($cliente);
    }

    public function excelAdmin(int $clienteId)
    {
        $cliente = (new ClienteModel())->find($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportExcel($cliente);
    }

    private function exportExcel(array $cliente)
    {
        $cid    = (int) $cliente['id'];
        $f      = $this->filters();
        $kpis   = $this->kpis($cid, $f);
        $charts = $this->charts($cid, $f);

        $resumen = [
            ['Estadisticas', $cliente['nombre_tercero']],
            ['Generado', date('Y-m-d H:i')],
            ['Ano', $f['anio'] ?: 'Todos'],
            [],
            ['KPI', 'Valor'],
            ['Personas', $kpis['personas']],
            ['Hogares respondidos', $kpis['hogares']],
            ['Cobertura %', $kpis['cobertura']],
            ['Inmuebles respondidos', $kpis['respondidos'] . ' / ' . $kpis['inmuebles']],
            ['Mascotas', $kpis['mascotas']],
            ['Vehiculos', $kpis['vehiculos']],
            ['Parqueaderos', $kpis['parqueaderos'] ?? ''],
            ['Condicion especial', $kpis['discapacidad'] ?? ''],
            [],
        ];
        foreach ($charts as $ch) {
            $resumen[] = [$ch['title']];
            $resumen[] = ['Categoria', 'Cantidad'];
            foreach ($ch['labels'] as $i => $lbl) {
                $resumen[] = [$lbl, $ch['data'][$i] ?? 0];
            }
            $resumen[] = [];
        }

        $personas = $this->baseResidentes($cid, $f)
            ->join('parentescos p', 'p.id = cr.parentesco_id', 'left')
            ->select("COALESCE(t.nombre,'Sin torre') AS torre, i.identificador AS inmueble, i.tipo, cr.nombre, cr.documento, cr.sexo, cr.edad, COALESCE(p.nombre,'') AS parentesco", false)
            ->orderBy('t.nombre')->orderBy('i.identificador')->get()->getResultArray();

        $sexoMap  = ['M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro'];
        $pHeaders = ['Torre', 'Inmueble', 'Tipo', 'Nombre', 'Documento', 'Sexo', 'Edad', 'Parentesco'];
        $pRows    = [];
        foreach ($personas as $p) {
            $pRows[] = [
                $p['torre'], $p['inmueble'], ucfirst((string) $p['tipo']), $p['nombre'], $p['documento'],
                $sexoMap[$p['sexo']] ?? 'Sin dato', $p['edad'], $p['parentesco'],
            ];
        }

        $xlsx = \App\Libraries\Excel::build([
            ['name' => 'Resumen', 'headers' => [], 'rows' => $resumen],
            ['name' => 'Personas', 'headers' => $pHeaders, 'rows' => $pRows],
        ]);

        $filename = 'estadisticas-' . $cliente['slug'] . '-' . ($f['anio'] ?: 'todos-los-anos') . '-' . date('Ymd-His') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($xlsx);
    }

    private function currentCliente(): ?array
    {
        $clienteId = session()->get('cliente_id');
        if (! $clienteId) {
            return null;
        }

        return (new ClienteModel())->find((int) $clienteId);
    }

    private function build(array $cliente, bool $isAdmin): array
    {
        $cid      = (int) $cliente['id'];
        $f        = $this->filters();
        $basePath = $isAdmin ? 'admin/clientes/' . $cid . '/inteligencia' : 'inteligencia';

        return [
            'cliente'  => $cliente,
            'isAdmin'  => $isAdmin,
            'basePath' => $basePath,
            'exportPath' => $basePath . '/exportar',
            'filters'  => $f,
            'anios'     => $this->anios($cid),
            'torres'   => $this->torres($cid),
            'parentescos' => db_connect()->table('parentescos')->select('id, nombre')->where('activo', 1)->orderBy('nombre')->get()->getResultArray(),
            'tiposMascota' => $this->tiposMascota(),
            'tiposVehiculo' => $this->tiposVehiculo(),
            'kpis'     => $this->kpis($cid, $f),
            'charts'   => $this->charts($cid, $f),
            'summary'  => $this->summary($cid, $f),
            'chips'    => $this->chips($cid, $f),
        ];
    }

    private function filters(): array
    {
        $g = fn ($k) => trim((string) $this->request->getGet($k));

        $tipo              = $g('tipo');
        $sexo              = $g('sexo');
        $edad              = $g('edad');
        $tieneMascotas     = $g('tiene_mascotas');
        $tieneVehiculos    = $g('tiene_vehiculos');
        $tieneParqueadero  = $g('tiene_parqueadero');
        $tieneDiscapacidad = $g('tiene_discapacidad');

        return [
            'anio'          => $g('anio') !== '' ? (int) $g('anio') : null,
            'torre_id'      => $g('torre_id') !== '' ? (int) $g('torre_id') : null,
            'tipo'          => in_array($tipo, ['casa', 'apartamento'], true) ? $tipo : null,
            'sexo'          => in_array($sexo, ['M', 'F', 'Otro'], true) ? $sexo : null,
            'edad'          => array_key_exists($edad, $this->ageBuckets) ? $edad : null,
            'parentesco_id' => $g('parentesco_id') !== '' ? (int) $g('parentesco_id') : null,
            'fecha_desde'   => $this->validDate($g('fecha_desde')),
            'fecha_hasta'   => $this->validDate($g('fecha_hasta')),
            'tiene_mascotas' => in_array($tieneMascotas, ['0', '1'], true) ? $tieneMascotas : null,
            'tipo_mascota_id' => $g('tipo_mascota_id') !== '' ? (int) $g('tipo_mascota_id') : null,
            'tiene_vehiculos' => in_array($tieneVehiculos, ['0', '1'], true) ? $tieneVehiculos : null,
            'tipo_vehiculo_id' => $g('tipo_vehiculo_id') !== '' ? (int) $g('tipo_vehiculo_id') : null,
            'tiene_parqueadero' => in_array($tieneParqueadero, ['0', '1'], true) ? $tieneParqueadero : null,
            'tiene_discapacidad' => in_array($tieneDiscapacidad, ['0', '1'], true) ? $tieneDiscapacidad : null,
        ];
    }

    private function validDate(string $date): ?string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return checkdate($m, $d, $y) ? $date : null;
    }

    private function baseResidentes(int $cid, array $f, array $exclude = [])
    {
        $b = db_connect()->table('censo_residentes cr')
            ->join('censos_poblacionales cp', 'cp.id = cr.censo_id')
            ->join('inmuebles i', 'i.id = cp.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('cp.cliente_id', $cid)
            ->where('cp.deleted_at', null);

        $this->applyCensoFilters($b, $f, $exclude);

        if (! in_array('sexo', $exclude, true) && $f['sexo'] !== null) {
            $b->where('cr.sexo', $f['sexo']);
        }
        if (! in_array('parentesco_id', $exclude, true) && $f['parentesco_id'] !== null) {
            $b->where('cr.parentesco_id', $f['parentesco_id']);
        }
        if (! in_array('edad', $exclude, true) && $f['edad'] !== null) {
            [$min, $max] = $this->ageBuckets[$f['edad']];
            $b->where('cr.edad >=', $min);
            if ($max !== null) {
                $b->where('cr.edad <=', $max);
            }
        }

        return $b;
    }

    private function baseCensos(int $cid, array $f, array $exclude = [])
    {
        $b = db_connect()->table('censos_poblacionales cp')
            ->join('inmuebles i', 'i.id = cp.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('cp.cliente_id', $cid)
            ->where('cp.deleted_at', null);

        $this->applyCensoFilters($b, $f, $exclude);

        return $b;
    }

    private function applyCensoFilters($b, array $f, array $exclude = []): void
    {
        if (! in_array('anio', $exclude, true) && $f['anio'] !== null) {
            $b->where('cp.anio', $f['anio']);
        }
        if (! in_array('torre_id', $exclude, true) && $f['torre_id'] !== null) {
            $b->where('i.torre_id', $f['torre_id']);
        }
        if (! in_array('tipo', $exclude, true) && $f['tipo'] !== null) {
            $b->where('i.tipo', $f['tipo']);
        }
        if (! in_array('fecha_desde', $exclude, true) && $f['fecha_desde'] !== null) {
            $b->where('cp.created_at >=', $f['fecha_desde'] . ' 00:00:00');
        }
        if (! in_array('fecha_hasta', $exclude, true) && $f['fecha_hasta'] !== null) {
            $b->where('cp.created_at <=', $f['fecha_hasta'] . ' 23:59:59');
        }
        if (! in_array('tiene_parqueadero', $exclude, true) && $f['tiene_parqueadero'] !== null) {
            $b->where('cp.tiene_parqueadero', (int) $f['tiene_parqueadero']);
        }
        if (! in_array('tiene_discapacidad', $exclude, true) && $f['tiene_discapacidad'] !== null) {
            if ($f['tiene_discapacidad'] === '1') {
                $b->where("NULLIF(TRIM(cp.discapacidad_descripcion), '') IS NOT NULL", null, false);
            } else {
                $b->groupStart()
                    ->where('cp.discapacidad_descripcion', null)
                    ->orWhere("TRIM(cp.discapacidad_descripcion) = ''", null, false)
                    ->groupEnd();
            }
        }
        if (! in_array('tiene_mascotas', $exclude, true) && $f['tiene_mascotas'] !== null) {
            if ($f['tiene_mascotas'] === '1') {
                $b->groupStart()
                    ->where('cp.tiene_mascotas', 1)
                    ->orWhere('EXISTS (SELECT 1 FROM mascotas mp WHERE mp.censo_poblacional_id = cp.id)', null, false)
                    ->orWhere('EXISTS (' . $this->mascotaIndependienteExistsSql($f, null) . ')', null, false)
                    ->groupEnd();
            } else {
                $b->groupStart()
                    ->where('cp.tiene_mascotas !=', 1)
                    ->orWhere('cp.tiene_mascotas', null)
                    ->groupEnd()
                    ->where('NOT EXISTS (SELECT 1 FROM mascotas mp WHERE mp.censo_poblacional_id = cp.id)', null, false)
                    ->where('NOT EXISTS (' . $this->mascotaIndependienteExistsSql($f, null) . ')', null, false);
            }
        }
        if (! in_array('tipo_mascota_id', $exclude, true) && $f['tipo_mascota_id'] !== null) {
            $tipoMascotaId = (int) $f['tipo_mascota_id'];
            $b->where(
                '(EXISTS (SELECT 1 FROM mascotas mp WHERE mp.censo_poblacional_id = cp.id AND mp.tipo_mascota_id = ' . $tipoMascotaId . ')'
                . ' OR EXISTS (' . $this->mascotaIndependienteExistsSql($f, $tipoMascotaId) . '))',
                null,
                false
            );
        }
        if (! in_array('tiene_vehiculos', $exclude, true) && $f['tiene_vehiculos'] !== null) {
            $exists = 'EXISTS (SELECT 1 FROM censo_vehiculos cvf WHERE cvf.censo_id = cp.id)';
            $b->where(($f['tiene_vehiculos'] === '1' ? '' : 'NOT ') . $exists, null, false);
        }
        if (! in_array('tipo_vehiculo_id', $exclude, true) && $f['tipo_vehiculo_id'] !== null) {
            $b->where(
                'EXISTS (SELECT 1 FROM censo_vehiculos cvf WHERE cvf.censo_id = cp.id AND cvf.tipo_vehiculo_id = ' . (int) $f['tipo_vehiculo_id'] . ')',
                null,
                false
            );
        }
    }

    private function mascotaIndependienteExistsSql(array $f, ?int $tipoMascotaId): string
    {
        $sql = 'SELECT 1 FROM censos_mascotas cmf'
            . ' JOIN mascotas mif ON mif.censo_mascota_id = cmf.id'
            . ' WHERE cmf.inmueble_id = cp.inmueble_id'
            . ' AND cmf.cliente_id = cp.cliente_id'
            . ' AND cmf.deleted_at IS NULL';

        if ($f['anio'] !== null) {
            $sql .= ' AND cmf.anio = ' . (int) $f['anio'];
        }
        if ($f['fecha_desde'] !== null) {
            $sql .= ' AND cmf.created_at >= ' . db_connect()->escape($f['fecha_desde'] . ' 00:00:00');
        }
        if ($f['fecha_hasta'] !== null) {
            $sql .= ' AND cmf.created_at <= ' . db_connect()->escape($f['fecha_hasta'] . ' 23:59:59');
        }
        if ($tipoMascotaId !== null) {
            $sql .= ' AND mif.tipo_mascota_id = ' . $tipoMascotaId;
        }

        return $sql;
    }

    private function kpis(int $cid, array $f): array
    {
        $db = db_connect();

        $personas = (clone $this->baseResidentes($cid, $f))->countAllResults();
        $hogares  = (int) $this->baseResidentes($cid, $f)
            ->select('COUNT(DISTINCT cr.censo_id) c', false)->get()->getRow('c');

        $totalInmuebles = $db->table('inmuebles')->where('cliente_id', $cid)->where('deleted_at', null)->countAllResults();
        $respondidos    = (int) $this->baseCensos($cid, $f)
            ->select('COUNT(DISTINCT cp.inmueble_id) c', false)->get()->getRow('c');
        $cobertura = $totalInmuebles > 0 ? round($respondidos / $totalInmuebles * 100, 1) : 0.0;

        $mascotasPoblacional = (clone $this->baseMascotasPoblacional($cid, $f))->countAllResults();
        $mascotasIndependiente = (clone $this->baseMascotasIndependiente($cid, $f))->countAllResults();
        $vehiculos = (clone $this->baseVehiculos($cid, $f))->countAllResults();
        $parqueaderos = (int) $this->baseCensos($cid, $f)
            ->where('cp.tiene_parqueadero', 1)->select('COUNT(DISTINCT cp.id) c', false)->get()->getRow('c');
        $discapacidad = (int) $this->baseCensos($cid, $f)
            ->where("NULLIF(TRIM(cp.discapacidad_descripcion), '') IS NOT NULL", null, false)
            ->select('COUNT(DISTINCT cp.id) c', false)->get()->getRow('c');

        return [
            'personas'  => $personas,
            'hogares'   => $hogares,
            'cobertura' => $cobertura,
            'respondidos' => $respondidos,
            'inmuebles' => $totalInmuebles,
            'mascotas'  => $mascotasPoblacional + $mascotasIndependiente,
            'mascotas_poblacional' => $mascotasPoblacional,
            'mascotas_independiente' => $mascotasIndependiente,
            'vehiculos' => $vehiculos,
            'parqueaderos' => $parqueaderos,
            'discapacidad' => $discapacidad,
        ];
    }

    private function charts(int $cid, array $f): array
    {
        $charts = [];

        $rows = $this->baseResidentes($cid, $f)
            ->select("CASE WHEN cr.sexo IS NULL THEN 'Sin dato' ELSE cr.sexo END AS k", false)
            ->select('COUNT(*) c', false)->groupBy('k')->get()->getResultArray();
        $map = ['M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro', 'Sin dato' => 'Sin dato'];
        $charts[] = $this->pack('sexo', 'Distribucion por sexo', 'doughnut', $rows,
            fn ($k) => $map[$k] ?? $k, fn ($k) => $k === 'Sin dato' ? null : $k);

        $rows = $this->baseResidentes($cid, $f)
            ->select("COALESCE(t.id, 0) AS k", false)
            ->select("COALESCE(t.nombre, 'Sin torre') AS lbl", false)
            ->select('COUNT(*) c', false)->groupBy('k')->groupBy('lbl')->orderBy('lbl')->get()->getResultArray();
        $charts[] = $this->packKV('torre_id', 'Personas por torre', 'bar', $rows,
            fn ($r) => $r['lbl'], fn ($r) => (int) $r['k'] === 0 ? null : $r['k']);

        $caseEdad = "CASE WHEN cr.edad IS NULL THEN 'Sin dato'"
            . " WHEN cr.edad <= 12 THEN '0-12'"
            . " WHEN cr.edad <= 17 THEN '13-17'"
            . " WHEN cr.edad <= 29 THEN '18-29'"
            . " WHEN cr.edad <= 44 THEN '30-44'"
            . " WHEN cr.edad <= 59 THEN '45-59'"
            . " ELSE '60+' END";
        $rows = $this->baseResidentes($cid, $f)
            ->select("$caseEdad AS k", false)->select('COUNT(*) c', false)->groupBy('k')->get()->getResultArray();
        $charts[] = $this->pack('edad', 'Personas por rango de edad', 'bar', $this->sortAgeRows($rows),
            fn ($k) => $k, fn ($k) => $k === 'Sin dato' ? null : $k);

        $rows = $this->baseResidentes($cid, $f)
            ->select("COALESCE(p.id, 0) AS k", false)
            ->select("COALESCE(p.nombre, 'Sin dato') AS lbl", false)
            ->join('parentescos p', 'p.id = cr.parentesco_id', 'left')
            ->select('COUNT(*) c', false)->groupBy('k')->groupBy('lbl')->orderBy('c', 'DESC')->get()->getResultArray();
        $charts[] = $this->packKV('parentesco_id', 'Personas por parentesco', 'bar', $rows,
            fn ($r) => $r['lbl'], fn ($r) => (int) $r['k'] === 0 ? null : $r['k']);

        $rows = $this->baseResidentes($cid, $f)
            ->select('i.tipo AS k', false)->select('COUNT(*) c', false)->groupBy('k')->get()->getResultArray();
        $charts[] = $this->pack('tipo', 'Personas por tipo de inmueble', 'doughnut', $rows,
            fn ($k) => ucfirst((string) $k), fn ($k) => $k);

        $charts[] = $this->packKV('torre_id', 'Cobertura por torre', 'bar', $this->coverageByTower($cid, $f),
            fn ($r) => $r['k'], fn ($r) => $r['v']);
        $charts[] = $this->pack('tiene_mascotas', 'Hogares con mascotas', 'doughnut', $this->houseBoolRows($cid, $f, 'mascotas', ['tiene_mascotas']),
            fn ($k) => $k, fn ($k) => $k === 'Si' ? 1 : 0);
        $charts[] = $this->pack('tiene_parqueadero', 'Hogares con parqueadero', 'doughnut', $this->houseBoolRows($cid, $f, 'parqueadero', ['tiene_parqueadero']),
            fn ($k) => $k, fn ($k) => $k === 'Si' ? 1 : 0);
        $charts[] = $this->pack('tiene_discapacidad', 'Hogares con condicion especial', 'doughnut', $this->houseBoolRows($cid, $f, 'discapacidad', ['tiene_discapacidad']),
            fn ($k) => $k, fn ($k) => $k === 'Si' ? 1 : 0);
        $charts[] = $this->pack('tiene_vehiculos', 'Hogares con vehiculos', 'doughnut', $this->houseBoolRows($cid, $f, 'vehiculos', ['tiene_vehiculos']),
            fn ($k) => $k, fn ($k) => $k === 'Si' ? 1 : 0);

        $rows = $this->baseVehiculos($cid, $f, ['tipo_vehiculo_id'])
            ->select('COALESCE(tv.id, 0) AS k', false)
            ->select("COALESCE(tv.nombre, 'Sin dato') AS lbl", false)
            ->select('COUNT(*) c', false)
            ->join('tipos_vehiculo tv', 'tv.id = cv.tipo_vehiculo_id', 'left')
            ->groupBy('k')->groupBy('lbl')->orderBy('c', 'DESC')->get()->getResultArray();
        $charts[] = $this->packKV('tipo_vehiculo_id', 'Vehiculos por tipo', 'bar', $rows,
            fn ($r) => $r['lbl'], fn ($r) => (int) $r['k'] === 0 ? null : $r['k']);

        $charts[] = $this->packKV('tipo_mascota_id', 'Mascotas por tipo', 'doughnut', $this->mascotasPorTipo($cid, $f, ['tipo_mascota_id']),
            fn ($r) => $r['lbl'], fn ($r) => (int) $r['k'] === 0 ? null : $r['k']);

        return $charts;
    }

    private function baseVehiculos(int $cid, array $f, array $exclude = [])
    {
        $b = db_connect()->table('censo_vehiculos cv')
            ->join('censos_poblacionales cp', 'cp.id = cv.censo_id')
            ->join('inmuebles i', 'i.id = cp.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('cp.cliente_id', $cid)
            ->where('cp.deleted_at', null);

        $this->applyCensoFilters($b, $f, $exclude);

        if (! in_array('tipo_vehiculo_id', $exclude, true) && $f['tipo_vehiculo_id'] !== null) {
            $b->where('cv.tipo_vehiculo_id', $f['tipo_vehiculo_id']);
        }
        if (! in_array('tiene_vehiculos', $exclude, true) && $f['tiene_vehiculos'] === '0') {
            $b->where('1 = 0', null, false);
        }

        return $b;
    }

    private function baseMascotasPoblacional(int $cid, array $f, array $exclude = [])
    {
        $b = db_connect()->table('mascotas m')
            ->join('censos_poblacionales cp', 'cp.id = m.censo_poblacional_id')
            ->join('inmuebles i', 'i.id = cp.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('cp.cliente_id', $cid)
            ->where('cp.deleted_at', null);

        $this->applyCensoFilters($b, $f, $exclude);

        if (! in_array('tipo_mascota_id', $exclude, true) && $f['tipo_mascota_id'] !== null) {
            $b->where('m.tipo_mascota_id', $f['tipo_mascota_id']);
        }

        return $b;
    }

    private function poblacionalForIndependentMascotaSql(array $f, string $extraCondition): string
    {
        $sql = 'SELECT 1 FROM censos_poblacionales cp2'
            . ' WHERE cp2.inmueble_id = cm.inmueble_id'
            . ' AND cp2.cliente_id = cm.cliente_id'
            . ' AND cp2.deleted_at IS NULL'
            . ' AND ' . $extraCondition;

        if ($f['anio'] !== null) {
            $sql .= ' AND cp2.anio = ' . (int) $f['anio'];
        }
        if ($f['fecha_desde'] !== null) {
            $sql .= ' AND cp2.created_at >= ' . db_connect()->escape($f['fecha_desde'] . ' 00:00:00');
        }
        if ($f['fecha_hasta'] !== null) {
            $sql .= ' AND cp2.created_at <= ' . db_connect()->escape($f['fecha_hasta'] . ' 23:59:59');
        }

        return $sql;
    }

    private function baseMascotasIndependiente(int $cid, array $f, array $exclude = [])
    {
        $b = db_connect()->table('mascotas m')
            ->join('censos_mascotas cm', 'cm.id = m.censo_mascota_id')
            ->join('inmuebles i', 'i.id = cm.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('cm.cliente_id', $cid)
            ->where('cm.deleted_at', null);

        if (! in_array('torre_id', $exclude, true) && $f['torre_id'] !== null) {
            $b->where('i.torre_id', $f['torre_id']);
        }
        if (! in_array('anio', $exclude, true) && $f['anio'] !== null) {
            $b->where('cm.anio', $f['anio']);
        }
        if (! in_array('tipo', $exclude, true) && $f['tipo'] !== null) {
            $b->where('i.tipo', $f['tipo']);
        }
        if (! in_array('fecha_desde', $exclude, true) && $f['fecha_desde'] !== null) {
            $b->where('cm.created_at >=', $f['fecha_desde'] . ' 00:00:00');
        }
        if (! in_array('fecha_hasta', $exclude, true) && $f['fecha_hasta'] !== null) {
            $b->where('cm.created_at <=', $f['fecha_hasta'] . ' 23:59:59');
        }
        if (! in_array('tiene_mascotas', $exclude, true) && $f['tiene_mascotas'] === '0') {
            $b->where('1 = 0', null, false);
        }
        if (! in_array('tipo_mascota_id', $exclude, true) && $f['tipo_mascota_id'] !== null) {
            $b->where('m.tipo_mascota_id', $f['tipo_mascota_id']);
        }
        if (! in_array('tiene_parqueadero', $exclude, true) && $f['tiene_parqueadero'] !== null) {
            $b->where('EXISTS (' . $this->poblacionalForIndependentMascotaSql($f, 'cp2.tiene_parqueadero = ' . (int) $f['tiene_parqueadero']) . ')', null, false);
        }
        if (! in_array('tiene_discapacidad', $exclude, true) && $f['tiene_discapacidad'] !== null) {
            $extra = $f['tiene_discapacidad'] === '1'
                ? "NULLIF(TRIM(cp2.discapacidad_descripcion), '') IS NOT NULL"
                : "(cp2.discapacidad_descripcion IS NULL OR TRIM(cp2.discapacidad_descripcion) = '')";
            $b->where('EXISTS (' . $this->poblacionalForIndependentMascotaSql($f, $extra) . ')', null, false);
        }
        if (! in_array('tiene_vehiculos', $exclude, true) && $f['tiene_vehiculos'] !== null) {
            $exists = 'EXISTS (SELECT 1 FROM censo_vehiculos cv2 WHERE cv2.censo_id = cp2.id)';
            $b->where('EXISTS (' . $this->poblacionalForIndependentMascotaSql($f, ($f['tiene_vehiculos'] === '1' ? '' : 'NOT ') . $exists) . ')', null, false);
        }
        if (! in_array('tipo_vehiculo_id', $exclude, true) && $f['tipo_vehiculo_id'] !== null) {
            $b->where(
                'EXISTS (' . $this->poblacionalForIndependentMascotaSql(
                    $f,
                    'EXISTS (SELECT 1 FROM censo_vehiculos cv2 WHERE cv2.censo_id = cp2.id AND cv2.tipo_vehiculo_id = ' . (int) $f['tipo_vehiculo_id'] . ')'
                ) . ')',
                null,
                false
            );
        }

        return $b;
    }

    private function sortAgeRows(array $rows): array
    {
        $order = array_merge(array_keys($this->ageBuckets), ['Sin dato']);
        $byKey = [];
        foreach ($rows as $r) {
            $byKey[$r['k']] = (int) $r['c'];
        }

        $sorted = [];
        foreach ($order as $k) {
            if (isset($byKey[$k])) {
                $sorted[] = ['k' => $k, 'c' => $byKey[$k]];
            }
        }

        return $sorted;
    }

    private function coverageByTower(int $cid, array $f, array $exclude = []): array
    {
        $join = 'cp.inmueble_id = i.id AND cp.deleted_at IS NULL';
        if ($f['anio'] !== null) {
            $join .= ' AND cp.anio = ' . (int) $f['anio'];
        }
        if ($f['fecha_desde'] !== null) {
            $join .= " AND cp.created_at >= " . db_connect()->escape($f['fecha_desde'] . ' 00:00:00');
        }
        if ($f['fecha_hasta'] !== null) {
            $join .= " AND cp.created_at <= " . db_connect()->escape($f['fecha_hasta'] . ' 23:59:59');
        }

        $query = db_connect()->table('inmuebles i')
            ->select("COALESCE(t.nombre, 'Sin torre') AS k", false)
            ->select('COALESCE(t.id, 0) AS v', false)
            ->select('COUNT(DISTINCT i.id) total', false)
            ->select('COUNT(DISTINCT cp.inmueble_id) respondidos', false)
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->join('censos_poblacionales cp', $join, 'left')
            ->where('i.cliente_id', $cid)
            ->where('i.deleted_at', null);

        if (! in_array('torre_id', $exclude, true) && $f['torre_id'] !== null) {
            $query->where('i.torre_id', $f['torre_id']);
        }
        if ($f['tipo'] !== null) {
            $query->where('i.tipo', $f['tipo']);
        }

        $rows = $query->groupBy('k')->groupBy('v')->orderBy('k')->get()->getResultArray();

        return array_map(static function (array $row): array {
            $total = (int) $row['total'];
            $pct = $total > 0 ? round(((int) $row['respondidos'] / $total) * 100, 1) : 0;

            return ['k' => $row['k'], 'v' => (int) $row['v'] === 0 ? null : (int) $row['v'], 'c' => $pct];
        }, $rows);
    }

    private function houseBoolRows(int $cid, array $f, string $kind, array $exclude = []): array
    {
        $rows = $this->baseCensos($cid, $f, $exclude)->select('cp.id, cp.tiene_mascotas, cp.tiene_parqueadero, cp.discapacidad_descripcion')->get()->getResultArray();
        $yes = 0;
        $no = 0;
        $pets = [];
        if ($kind === 'mascotas' && $rows !== []) {
            $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
            $petRows = db_connect()->table('mascotas')->select('DISTINCT censo_poblacional_id id', false)
                ->whereIn('censo_poblacional_id', $ids)->get()->getResultArray();
            $pets = array_fill_keys(array_map(static fn ($r) => (int) $r['id'], $petRows), true);
        }
        $vehicles = [];
        if ($kind === 'vehiculos' && $rows !== []) {
            $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
            $vehicleRows = db_connect()->table('censo_vehiculos')->select('DISTINCT censo_id id', false)
                ->whereIn('censo_id', $ids)->get()->getResultArray();
            $vehicles = array_fill_keys(array_map(static fn ($r) => (int) $r['id'], $vehicleRows), true);
        }

        foreach ($rows as $row) {
            $has = match ($kind) {
                'mascotas' => (int) ($row['tiene_mascotas'] ?? 0) === 1 || isset($pets[(int) $row['id']]),
                'parqueadero' => (int) ($row['tiene_parqueadero'] ?? 0) === 1,
                'discapacidad' => trim((string) ($row['discapacidad_descripcion'] ?? '')) !== '',
                'vehiculos' => isset($vehicles[(int) $row['id']]),
                default => false,
            };
            $has ? $yes++ : $no++;
        }

        return [['k' => 'Si', 'c' => $yes], ['k' => 'No', 'c' => $no]];
    }

    private function mascotasPorTipo(int $cid, array $f, array $exclude = []): array
    {
        $totals = [];
        foreach ([$this->baseMascotasPoblacional($cid, $f, $exclude), $this->baseMascotasIndependiente($cid, $f, $exclude)] as $query) {
            $rows = $query->select('COALESCE(tm.id, 0) AS k', false)
                ->select("COALESCE(tm.nombre, 'Sin dato') AS lbl", false)
                ->select('COUNT(*) c', false)
                ->join('tipos_mascota tm', 'tm.id = m.tipo_mascota_id', 'left')
                ->groupBy('k')->groupBy('lbl')
                ->get()
                ->getResultArray();
            foreach ($rows as $row) {
                $key = (int) $row['k'];
                if (! isset($totals[$key])) {
                    $totals[$key] = ['k' => $key, 'lbl' => $row['lbl'], 'c' => 0];
                }
                $totals[$key]['c'] += (int) $row['c'];
            }
        }

        usort($totals, static fn ($a, $b) => $b['c'] <=> $a['c']);

        return array_values($totals);
    }

    private function summary(int $cid, array $f): array
    {
        return [
            'torres' => $this->coverageByTower($cid, $f),
            'vehiculos' => $this->baseVehiculos($cid, $f)
                ->select('COALESCE(tv.id, 0) AS id', false)
                ->select("COALESCE(tv.nombre, 'Sin dato') AS label", false)
                ->select('COUNT(*) total', false)
                ->join('tipos_vehiculo tv', 'tv.id = cv.tipo_vehiculo_id', 'left')
                ->groupBy('id')->groupBy('label')->orderBy('total', 'DESC')->get()->getResultArray(),
            'mascotas' => $this->mascotasPorTipo($cid, $f),
        ];
    }

    private function exportCsv(array $cliente)
    {
        $f = $this->filters();
        $data = $this->build($cliente, str_starts_with((string) current_url(), base_url('admin/')));
        $filename = 'estadisticas-' . $cliente['slug'] . '-' . ($f['anio'] ?: 'todos-los-anos') . '-' . date('Ymd-His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['seccion', 'metrica', 'valor']);
        foreach ($data['kpis'] as $key => $value) {
            fputcsv($handle, ['kpi', $key, $value]);
        }
        foreach ($data['charts'] as $chart) {
            foreach ($chart['labels'] as $i => $label) {
                fputcsv($handle, [$chart['title'], $label, $chart['data'][$i] ?? 0]);
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function pack(string $filterKey, string $title, string $type, array $rows, callable $label, callable $value): array
    {
        $labels = [];
        $data   = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = $label($r['k']);
            $data[]   = (float) $r['c'];
            $values[] = $value($r['k']);
        }

        return ['key' => $filterKey, 'title' => $title, 'type' => $type, 'labels' => $labels, 'data' => $data, 'values' => $values];
    }

    private function packKV(string $filterKey, string $title, string $type, array $rows, callable $label, callable $value): array
    {
        $labels = [];
        $data   = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = $label($r);
            $data[]   = (float) $r['c'];
            $values[] = $value($r);
        }

        return ['key' => $filterKey, 'title' => $title, 'type' => $type, 'labels' => $labels, 'data' => $data, 'values' => $values];
    }

    private function torres(int $cid): array
    {
        return db_connect()->table('torres')->select('id, nombre')
            ->where('cliente_id', $cid)->where('deleted_at', null)->orderBy('nombre')->get()->getResultArray();
    }

    private function tiposMascota(): array
    {
        return db_connect()->table('tipos_mascota')->select('id, nombre')
            ->where('activo', 1)->orderBy('nombre')->get()->getResultArray();
    }

    private function tiposVehiculo(): array
    {
        return db_connect()->table('tipos_vehiculo')->select('id, nombre')
            ->where('activo', 1)->orderBy('nombre')->get()->getResultArray();
    }

    private function anios(int $cid): array
    {
        $rows = db_connect()->query(
            'SELECT anio FROM censos_poblacionales WHERE cliente_id = ? AND deleted_at IS NULL AND anio IS NOT NULL'
            . ' UNION SELECT anio FROM censos_mascotas WHERE cliente_id = ? AND deleted_at IS NULL AND anio IS NOT NULL'
            . ' ORDER BY anio DESC',
            [$cid, $cid]
        )->getResultArray();

        return array_map(static fn ($r) => (int) $r['anio'], $rows);
    }

    private function chips(int $cid, array $f): array
    {
        $chips = [];
        $torres = array_column($this->torres($cid), 'nombre', 'id');
        $parentescos = array_column(db_connect()->table('parentescos')->select('id, nombre')->where('activo', 1)->get()->getResultArray(), 'nombre', 'id');
        $tiposMascota = array_column($this->tiposMascota(), 'nombre', 'id');
        $tiposVehiculo = array_column($this->tiposVehiculo(), 'nombre', 'id');

        if ($f['anio'] !== null) {
            $chips[] = ['key' => 'anio', 'label' => 'Ano: ' . $f['anio']];
        }
        if ($f['tipo'] !== null) {
            $chips[] = ['key' => 'tipo', 'label' => 'Tipo: ' . ucfirst($f['tipo'])];
        }
        if ($f['sexo'] !== null) {
            $map = ['M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro'];
            $chips[] = ['key' => 'sexo', 'label' => 'Sexo: ' . ($map[$f['sexo']] ?? $f['sexo'])];
        }
        if ($f['edad'] !== null) {
            $chips[] = ['key' => 'edad', 'label' => 'Edad: ' . $f['edad']];
        }
        if ($f['torre_id'] !== null) {
            $chips[] = ['key' => 'torre_id', 'label' => 'Torre: ' . ($torres[$f['torre_id']] ?? ('#' . $f['torre_id']))];
        }
        if ($f['parentesco_id'] !== null) {
            $chips[] = ['key' => 'parentesco_id', 'label' => 'Parentesco: ' . ($parentescos[$f['parentesco_id']] ?? ('#' . $f['parentesco_id']))];
        }
        if ($f['fecha_desde'] !== null) {
            $chips[] = ['key' => 'fecha_desde', 'label' => 'Desde: ' . $f['fecha_desde']];
        }
        if ($f['fecha_hasta'] !== null) {
            $chips[] = ['key' => 'fecha_hasta', 'label' => 'Hasta: ' . $f['fecha_hasta']];
        }
        foreach (['tiene_mascotas' => 'Mascotas', 'tiene_parqueadero' => 'Parqueadero', 'tiene_discapacidad' => 'Condicion especial'] as $key => $label) {
            if ($f[$key] !== null) {
                $chips[] = ['key' => $key, 'label' => $label . ': ' . ($f[$key] === '1' ? 'Si' : 'No')];
            }
        }
        if ($f['tipo_mascota_id'] !== null) {
            $chips[] = ['key' => 'tipo_mascota_id', 'label' => 'Tipo mascota: ' . ($tiposMascota[$f['tipo_mascota_id']] ?? ('#' . $f['tipo_mascota_id']))];
        }
        if ($f['tiene_vehiculos'] !== null) {
            $chips[] = ['key' => 'tiene_vehiculos', 'label' => 'Vehiculos: ' . ($f['tiene_vehiculos'] === '1' ? 'Si' : 'No')];
        }
        if ($f['tipo_vehiculo_id'] !== null) {
            $chips[] = ['key' => 'tipo_vehiculo_id', 'label' => 'Tipo vehiculo: ' . ($tiposVehiculo[$f['tipo_vehiculo_id']] ?? ('#' . $f['tipo_vehiculo_id']))];
        }

        return $chips;
    }
}
