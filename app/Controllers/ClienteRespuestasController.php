<?php

namespace App\Controllers;

use App\Models\ClienteModel;

class ClienteRespuestasController extends BaseController
{
    private const INSTRUMENTOS = ['poblacional', 'mascotas'];

    public function admin(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return view('clientes/respuestas', $this->respuestasData($cliente, true));
    }

    public function mine()
    {
        $clienteId = session()->get('cliente_id');
        if (! $clienteId) {
            return redirect()->to('/dashboard')->with('error', 'Tu usuario no tiene un cliente asignado.');
        }

        $cliente = $this->findCliente((int) $clienteId);
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Cliente no encontrado.');
        }

        return view('clientes/respuestas', $this->respuestasData($cliente, false));
    }

    public function exportAdmin(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportCsv($cliente);
    }

    public function exportMine()
    {
        $clienteId = session()->get('cliente_id');
        if (! $clienteId) {
            return redirect()->to('/dashboard')->with('error', 'Tu usuario no tiene un cliente asignado.');
        }

        $cliente = $this->findCliente((int) $clienteId);
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportCsv($cliente);
    }

    public function pdfMine(string $instrumento, int $censoId)
    {
        $clienteId = session()->get('cliente_id');
        if (! $clienteId) {
            return redirect()->to('/dashboard')->with('error', 'Tu usuario no tiene un cliente asignado.');
        }

        return $this->servePdf((int) $clienteId, $instrumento, $censoId, '/respuestas');
    }

    public function pdfAdmin(int $clienteId, string $instrumento, int $censoId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return $this->servePdf($clienteId, $instrumento, $censoId, '/admin/clientes/' . $clienteId . '/respuestas');
    }

    private function servePdf(int $clienteId, string $instrumento, int $censoId, string $back)
    {
        if (! in_array($instrumento, self::INSTRUMENTOS, true)) {
            return redirect()->to($back)->with('error', 'Instrumento invalido.');
        }

        $table = $instrumento === 'poblacional' ? 'censos_poblacionales' : 'censos_mascotas';
        $censo = db_connect()->table($table)
            ->where('id', $censoId)
            ->where('cliente_id', $clienteId)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        if (! $censo) {
            return redirect()->to($back)->with('error', 'Registro no encontrado.');
        }

        $path = $censo['pdf_ruta'] ?? null;
        if (! $path || ! is_file(WRITEPATH . $path)) {
            $path = (new \App\Libraries\CensoPdf())->generate($instrumento, $censoId);
        }

        if (! $path || ! is_file(WRITEPATH . $path)) {
            return redirect()->to($back)->with('error', 'No fue posible generar el PDF.');
        }

        return $this->response->download(WRITEPATH . $path, null)
            ->setFileName('censo-' . $instrumento . '-' . $censoId . '.pdf');
    }

    private function respuestasData(array $cliente, bool $isAdmin): array
    {
        $filters = $this->filters();

        return [
            'cliente' => $cliente,
            'isAdmin' => $isAdmin,
            'filters' => $filters,
            'respuestas' => $this->queryRespuestas((int) $cliente['id'], $filters)->limit(300)->get()->getResultArray(),
            'anios' => $this->anios((int) $cliente['id']),
            'torres' => $this->torres((int) $cliente['id']),
            'inmuebles' => $this->inmuebles((int) $cliente['id']),
        ];
    }

    public function excelAdmin(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportExcel($cliente);
    }

    public function excelMine()
    {
        $clienteId = session()->get('cliente_id');
        if (! $clienteId) {
            return redirect()->to('/dashboard')->with('error', 'Tu usuario no tiene un cliente asignado.');
        }
        $cliente = $this->findCliente((int) $clienteId);
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportExcel($cliente);
    }

    private function exportExcel(array $cliente)
    {
        $filters = $this->filters();
        $rows    = $this->queryRespuestas((int) $cliente['id'], $filters)->get()->getResultArray();

        $headers = ['Instrumento', 'Ano', 'Torre', 'Tipo inmueble', 'Inmueble', 'Piso', 'Fecha respuesta', 'Fecha autorizacion', 'Firmante', 'Contacto', 'Correo enviado'];
        $data    = [];
        foreach ($rows as $row) {
            $data[] = [
                $row['instrumento'],
                $row['anio'],
                $row['torre_nombre'] ?: 'N/A',
                $row['tipo_inmueble'],
                $row['identificador'],
                $row['piso'] ?: 'N/A',
                $row['created_at'],
                $row['fecha_autorizacion'],
                $row['firmante_nombre'],
                $row['contacto'],
                (int) $row['pdf_enviado'] === 1 ? 'si' : 'no',
            ];
        }

        $xlsx     = \App\Libraries\Excel::build([['name' => 'Respuestas', 'headers' => $headers, 'rows' => $data]]);
        $filename = 'respuestas-' . $cliente['slug'] . '-' . ($filters['instrumento'] ?: 'todos') . '-' . ($filters['anio'] ?: 'todos-los-anos') . '-' . date('Ymd-His') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($xlsx);
    }

    public function completoAdmin(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportCompleto($cliente);
    }

    public function completoMine()
    {
        $clienteId = session()->get('cliente_id');
        if (! $clienteId) {
            return redirect()->to('/dashboard')->with('error', 'Tu usuario no tiene un cliente asignado.');
        }
        $cliente = $this->findCliente((int) $clienteId);
        if (! $cliente) {
            return redirect()->to('/dashboard')->with('error', 'Cliente no encontrado.');
        }

        return $this->exportCompleto($cliente);
    }

    /** Matriz completa del censo (una fila por hogar, n-items en columnas). Respeta filtros. */
    private function exportCompleto(array $cliente)
    {
        $cid     = (int) $cliente['id'];
        $f       = $this->filters();
        $sheets  = [];

        if ($f['instrumento'] === '' || $f['instrumento'] === 'poblacional') {
            $m = $this->poblacionalMatrix($cid, $f);
            $sheets[] = ['name' => 'Poblacional', 'headers' => $m['headers'], 'rows' => $m['rows']];
        }
        if ($f['instrumento'] === '' || $f['instrumento'] === 'mascotas') {
            $m = $this->mascotasMatrix($cid, $f);
            $sheets[] = ['name' => 'Mascotas', 'headers' => $m['headers'], 'rows' => $m['rows']];
        }

        $xlsx     = \App\Libraries\Excel::build($sheets);
        $filename = 'censo-completo-' . $cliente['slug'] . '-' . ($f['instrumento'] ?: 'todos') . '-' . ($f['anio'] ?: 'todos-los-anos') . '-' . date('Ymd-His') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($xlsx);
    }

    private function poblacionalMatrix(int $cid, array $f): array
    {
        $db = db_connect();
        $q  = $db->table('censos_poblacionales cp')
            ->select("cp.*, i.tipo AS i_tipo, i.identificador AS i_ident, i.piso AS i_piso, COALESCE(t.nombre,'') AS torre", false)
            ->join('inmuebles i', 'i.id = cp.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('cp.cliente_id', $cid)->where('cp.deleted_at', null);
        $this->applyMatrixFilters($q, $f, 'cp');
        $censos = $q->orderBy('cp.created_at', 'DESC')->get()->getResultArray();

        if ($censos === []) {
            return ['headers' => ['Sin respuestas con los filtros seleccionados'], 'rows' => []];
        }

        $ids  = array_column($censos, 'id');
        $prop = $this->groupByKey($db->table('censo_propietarios')->whereIn('censo_id', $ids)->orderBy('id')->get()->getResultArray(), 'censo_id');
        $arr  = $this->groupByKey($db->table('censo_arrendatarios')->whereIn('censo_id', $ids)->orderBy('id')->get()->getResultArray(), 'censo_id');
        $res  = $this->groupByKey($db->table('censo_residentes cr')->select("cr.*, COALESCE(p.nombre,'') AS parentesco", false)->join('parentescos p', 'p.id = cr.parentesco_id', 'left')->whereIn('cr.censo_id', $ids)->orderBy('cr.id')->get()->getResultArray(), 'censo_id');
        $veh  = $this->groupByKey($db->table('censo_vehiculos cv')->select("cv.*, COALESCE(tv.nombre,'') AS tipo_vehiculo", false)->join('tipos_vehiculo tv', 'tv.id = cv.tipo_vehiculo_id', 'left')->whereIn('cv.censo_id', $ids)->orderBy('cv.id')->get()->getResultArray(), 'censo_id');
        $tel  = $this->groupByKey($db->table('censo_telefonos')->whereIn('censo_id', $ids)->orderBy('orden')->get()->getResultArray(), 'censo_id');

        $maxP = $this->maxPer($prop);
        $maxA = $this->maxPer($arr);
        $maxR = $this->maxPer($res);
        $maxV = $this->maxPer($veh);
        $maxT = $this->maxPer($tel);

        $h = ['Ano', 'Fecha respuesta', 'Torre', 'Tipo inmueble', 'Inmueble', 'Piso', 'Autorizo datos', 'Fecha autorizacion', 'Vive en copropiedad', 'Direccion notificacion', 'Quien vive', 'Administrado por', 'Inmobiliaria', 'Inmobiliaria telefono', 'Inmobiliaria correo', 'Correo contacto', 'Tiene parqueadero', 'Discapacidad', 'Observaciones', 'Firmante'];
        for ($i = 1; $i <= $maxP; $i++) { array_push($h, "Propietario $i Nombre", "Propietario $i Documento", "Propietario $i Telefono", "Propietario $i Correo"); }
        for ($i = 1; $i <= $maxA; $i++) { array_push($h, "Arrendatario $i Nombre", "Arrendatario $i Documento", "Arrendatario $i Telefono", "Arrendatario $i Correo"); }
        for ($i = 1; $i <= $maxR; $i++) { array_push($h, "Residente $i Nombre", "Residente $i Documento", "Residente $i Sexo", "Residente $i Parentesco", "Residente $i Edad"); }
        for ($i = 1; $i <= $maxV; $i++) { array_push($h, "Vehiculo $i Tipo", "Vehiculo $i Marca", "Vehiculo $i Linea", "Vehiculo $i Modelo", "Vehiculo $i Color", "Vehiculo $i Placa"); }
        for ($i = 1; $i <= $maxT; $i++) { array_push($h, "Telefono contacto $i"); }

        $bool = static fn ($v) => $v === null || $v === '' ? '' : ((int) $v === 1 ? 'Si' : 'No');
        $sx   = static fn ($v) => $v === 'M' ? 'Masculino' : ($v === 'F' ? 'Femenino' : ($v === 'Otro' ? 'Otro' : ''));

        $rows = [];
        foreach ($censos as $c) {
            $id  = $c['id'];
            $row = [
                $c['anio'], $c['created_at'], $c['torre'], ucfirst((string) $c['i_tipo']), $c['i_ident'], $c['i_piso'],
                $bool($c['autorizacion_datos']), $c['fecha_autorizacion'], $bool($c['vive_en_copropiedad']),
                $c['direccion_notificacion'], $c['quien_vive'], $c['administrado_por'], $c['inmobiliaria_nombre'],
                $c['inmobiliaria_telefono'], $c['inmobiliaria_correo'], $c['correo_contacto'], $bool($c['tiene_parqueadero']),
                $c['discapacidad_descripcion'], $c['observaciones'], $c['firmante_nombre'],
            ];
            for ($i = 0; $i < $maxP; $i++) { $x = $prop[$id][$i] ?? null; array_push($row, $x['nombre'] ?? '', $x['documento'] ?? '', $x['telefono'] ?? '', $x['correo'] ?? ''); }
            for ($i = 0; $i < $maxA; $i++) { $x = $arr[$id][$i] ?? null; array_push($row, $x['nombre'] ?? '', $x['documento'] ?? '', $x['telefono'] ?? '', $x['correo'] ?? ''); }
            for ($i = 0; $i < $maxR; $i++) { $x = $res[$id][$i] ?? null; array_push($row, $x['nombre'] ?? '', $x['documento'] ?? '', $x ? $sx($x['sexo']) : '', $x['parentesco'] ?? '', $x['edad'] ?? ''); }
            for ($i = 0; $i < $maxV; $i++) { $x = $veh[$id][$i] ?? null; array_push($row, $x['tipo_vehiculo'] ?? '', $x['marca'] ?? '', $x['linea'] ?? '', $x['modelo'] ?? '', $x['color'] ?? '', $x['placa'] ?? ''); }
            for ($i = 0; $i < $maxT; $i++) { $x = $tel[$id][$i] ?? null; array_push($row, $x['numero'] ?? ''); }
            $rows[] = $row;
        }

        return ['headers' => $h, 'rows' => $rows];
    }

    private function mascotasMatrix(int $cid, array $f): array
    {
        $db = db_connect();
        $q  = $db->table('censos_mascotas cm')
            ->select("cm.*, i.tipo AS i_tipo, i.identificador AS i_ident, i.piso AS i_piso, COALESCE(t.nombre,'') AS torre", false)
            ->join('inmuebles i', 'i.id = cm.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('cm.cliente_id', $cid)->where('cm.deleted_at', null);
        $this->applyMatrixFilters($q, $f, 'cm');
        $censos = $q->orderBy('cm.created_at', 'DESC')->get()->getResultArray();

        if ($censos === []) {
            return ['headers' => ['Sin respuestas con los filtros seleccionados'], 'rows' => []];
        }

        $ids  = array_column($censos, 'id');
        $masc = $this->groupByKey($db->table('mascotas m')->select("m.*, COALESCE(tm.nombre,'') AS tipo_mascota", false)->join('tipos_mascota tm', 'tm.id = m.tipo_mascota_id', 'left')->whereIn('m.censo_mascota_id', $ids)->orderBy('m.id')->get()->getResultArray(), 'censo_mascota_id');
        $maxM = $this->maxPer($masc);

        $bool = static fn ($v) => $v === null || $v === '' ? '' : ((int) $v === 1 ? 'Si' : 'No');

        $h = ['Ano', 'Fecha respuesta', 'Torre', 'Tipo inmueble', 'Inmueble', 'Piso', 'Autorizo datos', 'Fecha autorizacion', 'Responsable Nombre', 'Responsable Documento', 'Responsable Telefono', 'Responsable Correo', 'Firmante'];
        for ($i = 1; $i <= $maxM; $i++) { array_push($h, "Mascota $i Nombre", "Mascota $i Tipo", "Mascota $i Edad", "Mascota $i Raza/Color", "Mascota $i Vacunada", "Mascota $i Esterilizada", "Mascota $i Foto", "Mascota $i Carne", "Mascota $i Poliza"); }

        $rows = [];
        foreach ($censos as $c) {
            $id  = $c['id'];
            $row = [
                $c['anio'], $c['created_at'], $c['torre'], ucfirst((string) $c['i_tipo']), $c['i_ident'], $c['i_piso'],
                $bool($c['autorizacion_datos']), $c['fecha_autorizacion'], $c['responsable_nombre'], $c['responsable_documento'],
                $c['responsable_telefono'], $c['responsable_correo'], $c['firmante_nombre'],
            ];
            for ($i = 0; $i < $maxM; $i++) {
                $x = $masc[$id][$i] ?? null;
                array_push($row, $x['nombre'] ?? '', $x['tipo_mascota'] ?? '', $x['edad'] ?? '', $x['raza_color'] ?? '', $x ? $bool($x['vacunacion_completa']) : '', $x ? $bool($x['esterilizada']) : '', $x['foto_ruta'] ?? '', $x['foto_carne_ruta'] ?? '', $x['foto_poliza_ruta'] ?? '');
            }
            $rows[] = $row;
        }

        return ['headers' => $h, 'rows' => $rows];
    }

    private function applyMatrixFilters($q, array $f, string $alias): void
    {
        if ($f['anio'] !== null) {
            $q->where($alias . '.anio', $f['anio']);
        }
        if ($f['torre_id'] !== null) {
            $q->where('i.torre_id', $f['torre_id']);
        }
        if ($f['inmueble_id'] !== null) {
            $q->where($alias . '.inmueble_id', $f['inmueble_id']);
        }
        if ($f['desde'] !== null) {
            $q->where($alias . '.created_at >=', $f['desde'] . ' 00:00:00');
        }
        if ($f['hasta'] !== null) {
            $q->where($alias . '.created_at <=', $f['hasta'] . ' 23:59:59');
        }
    }

    private function groupByKey(array $rows, string $key): array
    {
        $g = [];
        foreach ($rows as $r) {
            $g[$r[$key]][] = $r;
        }

        return $g;
    }

    private function maxPer(array $grouped): int
    {
        $m = 0;
        foreach ($grouped as $arr) {
            $m = max($m, count($arr));
        }

        return $m;
    }

    private function exportCsv(array $cliente)
    {
        $filters = $this->filters();
        $rows    = $this->queryRespuestas((int) $cliente['id'], $filters)->get()->getResultArray();

        $filename = 'respuestas-' . $cliente['slug'] . '-' . ($filters['instrumento'] ?: 'todos') . '-' . ($filters['anio'] ?: 'todos-los-anos') . '-' . date('Ymd-His') . '.csv';
        $handle   = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'instrumento', 'ano', 'cliente', 'torre', 'tipo_inmueble', 'inmueble', 'piso',
            'fecha_respuesta', 'fecha_autorizacion', 'firmante', 'contacto',
            'pdf_ruta', 'pdf_enviado',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['instrumento'],
                $row['anio'],
                $cliente['nombre_tercero'],
                $row['torre_nombre'] ?: 'N/A',
                $row['tipo_inmueble'],
                $row['identificador'],
                $row['piso'] ?: 'N/A',
                $row['created_at'],
                $row['fecha_autorizacion'],
                $row['firmante_nombre'],
                $row['contacto'],
                $row['pdf_ruta'],
                (int) $row['pdf_enviado'] === 1 ? 'si' : 'no',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function queryRespuestas(int $clienteId, array $filters)
    {
        $instrumento = $filters['instrumento'];

        if ($instrumento === 'poblacional') {
            $query = $this->instrumentQuery($clienteId, 'censos_poblacionales', 'poblacional', 'correo_contacto');
        } elseif ($instrumento === 'mascotas') {
            $query = $this->instrumentQuery($clienteId, 'censos_mascotas', 'mascotas', 'responsable_correo');
        } else {
            $poblacional = $this->instrumentQuery($clienteId, 'censos_poblacionales', 'poblacional', 'correo_contacto', false);
            $mascotas    = $this->instrumentQuery($clienteId, 'censos_mascotas', 'mascotas', 'responsable_correo', false);
            $unionSql    = '(' . $poblacional->getCompiledSelect() . ') UNION ALL (' . $mascotas->getCompiledSelect() . ')';
            $query       = db_connect()->table('(' . $unionSql . ') r');
        }

        if ($filters['anio'] !== null) {
            $query->where('anio', $filters['anio']);
        }

        if ($filters['torre_id'] !== null) {
            $query->where('torre_id', $filters['torre_id']);
        }

        if ($filters['inmueble_id'] !== null) {
            $query->where('inmueble_id', $filters['inmueble_id']);
        }

        if ($filters['desde'] !== null) {
            $query->where('created_at >=', $filters['desde'] . ' 00:00:00');
        }

        if ($filters['hasta'] !== null) {
            $query->where('created_at <=', $filters['hasta'] . ' 23:59:59');
        }

        return $query->orderBy('created_at', 'DESC');
    }

    private function instrumentQuery(int $clienteId, string $table, string $instrumento, string $contactField, bool $applyBaseOrder = true)
    {
        $query = db_connect()->table($table . ' c')
            ->select("
                '{$instrumento}' AS instrumento,
                c.id,
                c.anio,
                c.inmueble_id,
                i.torre_id,
                t.nombre AS torre_nombre,
                i.tipo AS tipo_inmueble,
                i.identificador,
                i.piso,
                c.created_at,
                c.fecha_autorizacion,
                c.firmante_nombre,
                c.{$contactField} AS contacto,
                c.pdf_ruta,
                c.pdf_enviado
            ", false)
            ->join('inmuebles i', 'i.id = c.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('c.cliente_id', $clienteId)
            ->where('c.deleted_at', null);

        if ($applyBaseOrder) {
            $query->orderBy('c.created_at', 'DESC');
        }

        return $query;
    }

    private function filters(): array
    {
        $instrumento = (string) $this->request->getGet('instrumento');
        if (! in_array($instrumento, self::INSTRUMENTOS, true)) {
            $instrumento = '';
        }

        return [
            'instrumento' => $instrumento,
            'anio' => $this->nullableIntGet('anio'),
            'torre_id' => $this->nullableIntGet('torre_id'),
            'inmueble_id' => $this->nullableIntGet('inmueble_id'),
            'desde' => $this->dateGet('desde'),
            'hasta' => $this->dateGet('hasta'),
        ];
    }

    private function anios(int $clienteId): array
    {
        $rows = db_connect()->query(
            'SELECT anio FROM censos_poblacionales WHERE cliente_id = ? AND deleted_at IS NULL AND anio IS NOT NULL'
            . ' UNION SELECT anio FROM censos_mascotas WHERE cliente_id = ? AND deleted_at IS NULL AND anio IS NOT NULL'
            . ' ORDER BY anio DESC',
            [$clienteId, $clienteId]
        )->getResultArray();

        return array_map(static fn ($r) => (int) $r['anio'], $rows);
    }

    private function torres(int $clienteId): array
    {
        return db_connect()->table('torres')
            ->select('id, nombre')
            ->where('cliente_id', $clienteId)
            ->where('deleted_at', null)
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function inmuebles(int $clienteId): array
    {
        return db_connect()->table('inmuebles i')
            ->select('i.id, i.tipo, i.identificador, t.nombre AS torre_nombre')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('i.cliente_id', $clienteId)
            ->where('i.deleted_at', null)
            ->orderBy('i.tipo', 'ASC')
            ->orderBy('t.nombre', 'ASC')
            ->orderBy('i.identificador', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function findCliente(int $clienteId): ?array
    {
        return (new ClienteModel())->find($clienteId);
    }

    private function nullableIntGet(string $key): ?int
    {
        $value = trim((string) $this->request->getGet($key));

        return $value === '' ? null : (int) $value;
    }

    private function dateGet(string $key): ?string
    {
        $value = trim((string) $this->request->getGet($key));
        if ($value === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
