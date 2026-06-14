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

        $headers = ['Instrumento', 'Torre', 'Tipo inmueble', 'Inmueble', 'Piso', 'Fecha respuesta', 'Fecha autorizacion', 'Firmante', 'Contacto', 'Correo enviado'];
        $data    = [];
        foreach ($rows as $row) {
            $data[] = [
                $row['instrumento'],
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
        $filename = 'respuestas-' . $cliente['slug'] . '-' . ($filters['instrumento'] ?: 'todos') . '-' . date('Ymd-His') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($xlsx);
    }

    private function exportCsv(array $cliente)
    {
        $filters = $this->filters();
        $rows    = $this->queryRespuestas((int) $cliente['id'], $filters)->get()->getResultArray();

        $filename = 'respuestas-' . $cliente['slug'] . '-' . ($filters['instrumento'] ?: 'todos') . '-' . date('Ymd-His') . '.csv';
        $handle   = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'instrumento', 'cliente', 'torre', 'tipo_inmueble', 'inmueble', 'piso',
            'fecha_respuesta', 'fecha_autorizacion', 'firmante', 'contacto',
            'pdf_ruta', 'pdf_enviado',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['instrumento'],
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
            'torre_id' => $this->nullableIntGet('torre_id'),
            'inmueble_id' => $this->nullableIntGet('inmueble_id'),
            'desde' => $this->dateGet('desde'),
            'hasta' => $this->dateGet('hasta'),
        ];
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
