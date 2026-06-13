<?php

namespace App\Controllers;

use App\Models\ClienteModel;

class InteligenciaController extends BaseController
{
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
        $clienteId = session()->get('cliente_id');
        if (! $clienteId) {
            return redirect()->to('/dashboard')->with('error', 'Tu usuario no tiene un cliente asignado.');
        }
        $cliente = (new ClienteModel())->find((int) $clienteId);
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

    private function build(array $cliente, bool $isAdmin): array
    {
        $cid      = (int) $cliente['id'];
        $f        = $this->filters();
        $basePath = $isAdmin ? 'admin/clientes/' . $cid . '/inteligencia' : 'inteligencia';

        return [
            'cliente'  => $cliente,
            'isAdmin'  => $isAdmin,
            'basePath' => $basePath,
            'filters'  => $f,
            'torres'   => $this->torres($cid),
            'parentescos' => db_connect()->table('parentescos')->select('id, nombre')->where('activo', 1)->orderBy('nombre')->get()->getResultArray(),
            'kpis'     => $this->kpis($cid, $f),
            'charts'   => $this->charts($cid, $f),
            'chips'    => $this->chips($f),
        ];
    }

    private function filters(): array
    {
        $g = fn ($k) => trim((string) $this->request->getGet($k));

        $tipo = $g('tipo');
        $sexo = $g('sexo');
        $edad = $g('edad');

        return [
            'torre_id'      => $g('torre_id') !== '' ? (int) $g('torre_id') : null,
            'tipo'          => in_array($tipo, ['casa', 'apartamento'], true) ? $tipo : null,
            'sexo'          => in_array($sexo, ['M', 'F', 'Otro'], true) ? $sexo : null,
            'edad'          => array_key_exists($edad, $this->ageBuckets) ? $edad : null,
            'parentesco_id' => $g('parentesco_id') !== '' ? (int) $g('parentesco_id') : null,
        ];
    }

    /** Base: residentes -> censo poblacional -> inmueble -> torre, con filtros (excepto los excluidos). */
    private function baseResidentes(int $cid, array $f, array $exclude = [])
    {
        $b = db_connect()->table('censo_residentes cr')
            ->join('censos_poblacionales cp', 'cp.id = cr.censo_id')
            ->join('inmuebles i', 'i.id = cp.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('cp.cliente_id', $cid)
            ->where('cp.deleted_at', null);

        if (! in_array('torre_id', $exclude, true) && $f['torre_id'] !== null) {
            $b->where('i.torre_id', $f['torre_id']);
        }
        if (! in_array('tipo', $exclude, true) && $f['tipo'] !== null) {
            $b->where('i.tipo', $f['tipo']);
        }
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

    private function kpis(int $cid, array $f): array
    {
        $db = db_connect();

        $personas = (clone $this->baseResidentes($cid, $f))->countAllResults();
        $hogares  = (int) $this->baseResidentes($cid, $f)
            ->select('COUNT(DISTINCT cr.censo_id) c', false)->get()->getRow('c');

        $totalInmuebles = $db->table('inmuebles')->where('cliente_id', $cid)->where('deleted_at', null)->countAllResults();
        $respondidos    = (int) $db->table('censos_poblacionales')->select('COUNT(DISTINCT inmueble_id) c', false)
            ->where('cliente_id', $cid)->where('deleted_at', null)->get()->getRow('c');
        $cobertura = $totalInmuebles > 0 ? round($respondidos / $totalInmuebles * 100, 1) : 0.0;

        $mascotas = $db->table('mascotas m')->join('censos_mascotas cm', 'cm.id = m.censo_mascota_id')
            ->where('cm.cliente_id', $cid)->where('cm.deleted_at', null)->countAllResults();
        $vehiculos = $db->table('censo_vehiculos cv')->join('censos_poblacionales cp', 'cp.id = cv.censo_id')
            ->where('cp.cliente_id', $cid)->where('cp.deleted_at', null)->countAllResults();

        return [
            'personas'  => $personas,
            'hogares'   => $hogares,
            'cobertura' => $cobertura,
            'respondidos' => $respondidos,
            'inmuebles' => $totalInmuebles,
            'mascotas'  => $mascotas,
            'vehiculos' => $vehiculos,
        ];
    }

    private function charts(int $cid, array $f): array
    {
        $charts = [];

        // 1) Sexo
        $rows = $this->baseResidentes($cid, $f, ['sexo'])
            ->select("CASE WHEN cr.sexo IS NULL THEN 'Sin dato' ELSE cr.sexo END AS k", false)
            ->select('COUNT(*) c', false)->groupBy('k')->get()->getResultArray();
        $map = ['M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro', 'Sin dato' => 'Sin dato'];
        $charts[] = $this->pack('sexo', 'Distribucion por sexo', 'doughnut', $rows,
            fn ($k) => $map[$k] ?? $k, fn ($k) => $k === 'Sin dato' ? null : $k);

        // 2) Torre
        $rows = $this->baseResidentes($cid, $f, ['torre_id'])
            ->select("COALESCE(t.id, 0) AS k", false)
            ->select("COALESCE(t.nombre, 'Sin torre') AS lbl", false)
            ->select('COUNT(*) c', false)->groupBy('k')->groupBy('lbl')->orderBy('lbl')->get()->getResultArray();
        $charts[] = $this->packKV('torre_id', 'Personas por torre', 'bar', $rows,
            fn ($r) => $r['lbl'], fn ($r) => (int) $r['k'] === 0 ? null : $r['k']);

        // 3) Rango de edad
        $caseEdad = "CASE WHEN cr.edad IS NULL THEN 'Sin dato'"
            . " WHEN cr.edad <= 12 THEN '0-12'"
            . " WHEN cr.edad <= 17 THEN '13-17'"
            . " WHEN cr.edad <= 29 THEN '18-29'"
            . " WHEN cr.edad <= 44 THEN '30-44'"
            . " WHEN cr.edad <= 59 THEN '45-59'"
            . " ELSE '60+' END";
        $rows = $this->baseResidentes($cid, $f, ['edad'])
            ->select("$caseEdad AS k", false)->select('COUNT(*) c', false)->groupBy('k')->get()->getResultArray();
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
        $charts[] = $this->pack('edad', 'Personas por rango de edad', 'bar', $sorted,
            fn ($k) => $k, fn ($k) => $k === 'Sin dato' ? null : $k);

        // 4) Parentesco
        $rows = $this->baseResidentes($cid, $f, ['parentesco_id'])
            ->select("COALESCE(p.id, 0) AS k", false)
            ->select("COALESCE(p.nombre, 'Sin dato') AS lbl", false)
            ->join('parentescos p', 'p.id = cr.parentesco_id', 'left')
            ->select('COUNT(*) c', false)->groupBy('k')->groupBy('lbl')->orderBy('c', 'DESC')->get()->getResultArray();
        $charts[] = $this->packKV('parentesco_id', 'Personas por parentesco', 'bar', $rows,
            fn ($r) => $r['lbl'], fn ($r) => (int) $r['k'] === 0 ? null : $r['k']);

        // 5) Tipo de inmueble
        $rows = $this->baseResidentes($cid, $f, ['tipo'])
            ->select('i.tipo AS k', false)->select('COUNT(*) c', false)->groupBy('k')->get()->getResultArray();
        $charts[] = $this->pack('tipo', 'Personas por tipo de inmueble', 'doughnut', $rows,
            fn ($k) => ucfirst((string) $k), fn ($k) => $k);

        // 6) Mascotas por tipo (informativo; filtro torre/tipo)
        $mb = db_connect()->table('mascotas m')
            ->join('censos_mascotas cm', 'cm.id = m.censo_mascota_id')
            ->join('inmuebles i', 'i.id = cm.inmueble_id')
            ->join('tipos_mascota tm', 'tm.id = m.tipo_mascota_id', 'left')
            ->where('cm.cliente_id', $cid)->where('cm.deleted_at', null);
        if ($f['torre_id'] !== null) {
            $mb->where('i.torre_id', $f['torre_id']);
        }
        if ($f['tipo'] !== null) {
            $mb->where('i.tipo', $f['tipo']);
        }
        $rows = $mb->select("COALESCE(tm.nombre, 'Sin dato') AS k", false)->select('COUNT(*) c', false)
            ->groupBy('k')->orderBy('c', 'DESC')->get()->getResultArray();
        $charts[] = $this->pack('', 'Mascotas por tipo', 'doughnut', $rows, fn ($k) => $k, fn ($k) => null);

        return $charts;
    }

    /** Empaqueta filas {k, c} en config de gráfico. */
    private function pack(string $filterKey, string $title, string $type, array $rows, callable $label, callable $value): array
    {
        $labels = [];
        $data   = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = $label($r['k']);
            $data[]   = (int) $r['c'];
            $values[] = $value($r['k']);
        }

        return ['key' => $filterKey, 'title' => $title, 'type' => $type, 'labels' => $labels, 'data' => $data, 'values' => $values];
    }

    /** Igual que pack pero el callback recibe la fila completa (para k/lbl separados). */
    private function packKV(string $filterKey, string $title, string $type, array $rows, callable $label, callable $value): array
    {
        $labels = [];
        $data   = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = $label($r);
            $data[]   = (int) $r['c'];
            $values[] = $value($r);
        }

        return ['key' => $filterKey, 'title' => $title, 'type' => $type, 'labels' => $labels, 'data' => $data, 'values' => $values];
    }

    private function torres(int $cid): array
    {
        return db_connect()->table('torres')->select('id, nombre')
            ->where('cliente_id', $cid)->where('deleted_at', null)->orderBy('nombre')->get()->getResultArray();
    }

    private function chips(array $f): array
    {
        $chips = [];
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
            $chips[] = ['key' => 'torre_id', 'label' => 'Torre #' . $f['torre_id']];
        }
        if ($f['parentesco_id'] !== null) {
            $chips[] = ['key' => 'parentesco_id', 'label' => 'Parentesco #' . $f['parentesco_id']];
        }

        return $chips;
    }
}
