<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClienteModel;
use App\Models\InmuebleModel;
use App\Models\TorreModel;

class ClienteConfiguracionController extends BaseController
{
    private const MAX_GENERATE = 10000;

    public function show(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $torreModel    = new TorreModel();
        $inmuebleModel = new InmuebleModel();
        $db            = db_connect();

        $totales = [
            'casas'         => (int) $inmuebleModel->forCliente($clienteId)->where('tipo', 'casa')->countAllResults(),
            'apartamentos'  => (int) $inmuebleModel->forCliente($clienteId)->where('tipo', 'apartamento')->countAllResults(),
            'torres'        => (int) $torreModel->forCliente($clienteId)->countAllResults(),
        ];
        $totales['inmuebles'] = $totales['casas'] + $totales['apartamentos'];

        $inmuebles = $db->table('inmuebles i')
            ->select('i.id, i.tipo, i.identificador, i.piso, i.created_at, t.nombre AS torre_nombre')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('i.cliente_id', $clienteId)
            ->where('i.deleted_at', null)
            ->orderBy('i.tipo', 'ASC')
            ->orderBy('t.nombre', 'ASC')
            ->orderBy('i.identificador', 'ASC')
            ->limit(200)
            ->get()
            ->getResultArray();

        return view('admin/clientes/configuracion', [
            'cliente'   => $cliente,
            'torres'    => $torreModel->forCliente($clienteId)->orderBy('nombre', 'ASC')->findAll(),
            'inmuebles' => $inmuebles,
            'totales'   => $totales,
        ]);
    }

    public function updateTipo(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $tipo = (string) $this->request->getPost('tipo_conjunto');
        if (! in_array($tipo, ['casas', 'apartamentos', 'mixto'], true)) {
            return redirect()->back()->with('error', 'Selecciona un tipo de conjunto valido.');
        }

        (new ClienteModel())->update($clienteId, ['tipo_conjunto' => $tipo]);

        return redirect()->back()->with('success', 'Tipo de conjunto actualizado.');
    }

    public function createTorre(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $data = [
            'cliente_id' => $clienteId,
            'nombre'     => trim((string) $this->request->getPost('nombre')),
            'num_pisos'  => $this->nullableInt('num_pisos'),
        ];

        if (! $this->validateData($data, [
            'cliente_id' => 'required|is_natural_no_zero',
            'nombre'     => 'required|max_length[100]',
            'num_pisos'  => 'permit_empty|is_natural_no_zero|less_than_equal_to[200]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->findTorreByName($clienteId, $data['nombre']) !== null) {
            return redirect()->back()->withInput()->with('error', 'Ya existe una torre con ese nombre.');
        }

        (new TorreModel())->insert($data);

        return redirect()->back()->with('success', 'Torre creada correctamente.');
    }

    public function deleteTorre(int $clienteId, int $torreId)
    {
        $torre = $this->findTorre($clienteId, $torreId);
        if (! $torre) {
            return redirect()->back()->with('error', 'Torre no encontrada.');
        }

        $inmuebles = (new InmuebleModel())->where('torre_id', $torreId)->countAllResults();
        if ($inmuebles > 0) {
            return redirect()->back()->with('error', 'No se puede archivar una torre con inmuebles asociados.');
        }

        (new TorreModel())->delete($torreId);

        return redirect()->back()->with('success', 'Torre archivada correctamente.');
    }

    public function generateCasas(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $desde   = (int) $this->request->getPost('desde');
        $hasta   = (int) $this->request->getPost('hasta');
        $padding = (int) $this->request->getPost('padding');
        $prefijo = (string) $this->request->getPost('prefijo');
        $total   = $hasta - $desde + 1;

        if ($desde < 1 || $hasta < $desde || $padding < 0 || $padding > 10) {
            return redirect()->back()->withInput()->with('error', 'Revisa el rango de casas y el relleno numerico.');
        }

        if ($total > self::MAX_GENERATE) {
            return redirect()->back()->withInput()->with('error', 'El rango supera el maximo de ' . self::MAX_GENERATE . ' inmuebles por ejecucion.');
        }

        $inmuebleModel = new InmuebleModel();
        $created       = 0;
        $skipped       = 0;

        $db = db_connect();
        $db->transStart();

        for ($number = $desde; $number <= $hasta; $number++) {
            $identificador = $prefijo . ($padding > 0 ? str_pad((string) $number, $padding, '0', STR_PAD_LEFT) : (string) $number);

            if ($this->casaExists($clienteId, $identificador)) {
                $skipped++;
                continue;
            }

            $inmuebleModel->insert([
                'cliente_id'    => $clienteId,
                'torre_id'      => null,
                'tipo'          => 'casa',
                'identificador' => $identificador,
                'piso'          => null,
            ]);
            $created++;
        }

        $db->transComplete();

        return redirect()->back()->with('success', "Generacion de casas finalizada. Creadas: {$created}. Omitidas: {$skipped}.");
    }

    public function generateApartamentos(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $torreDesde    = (int) $this->request->getPost('torre_desde');
        $torreHasta    = (int) $this->request->getPost('torre_hasta');
        $pisos         = (int) $this->request->getPost('pisos');
        $aptosPorPiso  = (int) $this->request->getPost('aptos_por_piso');
        $aptoDesde     = (int) $this->request->getPost('apto_desde');
        $prefijoTorre  = trim((string) $this->request->getPost('prefijo_torre')) ?: 'Torre';
        $torreCount    = $torreHasta - $torreDesde + 1;
        $total         = $torreCount * $pisos * $aptosPorPiso;

        if ($torreDesde < 1 || $torreHasta < $torreDesde || $pisos < 1 || $pisos > 200 || $aptosPorPiso < 1 || $aptosPorPiso > 200 || $aptoDesde < 1) {
            return redirect()->back()->withInput()->with('error', 'Revisa torres, pisos, apartamentos por piso y apartamento inicial.');
        }

        if ($total > self::MAX_GENERATE) {
            return redirect()->back()->withInput()->with('error', 'La generacion supera el maximo de ' . self::MAX_GENERATE . ' inmuebles por ejecucion.');
        }

        $db            = db_connect();
        $inmuebleModel = new InmuebleModel();
        $created       = 0;
        $skipped       = 0;
        $torresCreated = 0;
        $digits        = max(2, strlen((string) ($aptoDesde + $aptosPorPiso - 1)));

        $db->transStart();

        for ($torreNumber = $torreDesde; $torreNumber <= $torreHasta; $torreNumber++) {
            $torreName = trim($prefijoTorre . ' ' . $torreNumber);
            $torre     = $this->findOrCreateTorre($clienteId, $torreName, $pisos);

            if ($torre['created']) {
                $torresCreated++;
            }

            for ($piso = 1; $piso <= $pisos; $piso++) {
                for ($offset = 0; $offset < $aptosPorPiso; $offset++) {
                    $aptoNumber    = $aptoDesde + $offset;
                    $identificador = $piso . str_pad((string) $aptoNumber, $digits, '0', STR_PAD_LEFT);

                    if ($this->apartamentoExists($clienteId, (int) $torre['id'], $identificador)) {
                        $skipped++;
                        continue;
                    }

                    $inmuebleModel->insert([
                        'cliente_id'    => $clienteId,
                        'torre_id'      => (int) $torre['id'],
                        'tipo'          => 'apartamento',
                        'identificador' => $identificador,
                        'piso'          => $piso,
                    ]);
                    $created++;
                }
            }
        }

        $db->transComplete();

        return redirect()->back()->with('success', "Generacion de apartamentos finalizada. Torres nuevas: {$torresCreated}. Inmuebles creados: {$created}. Omitidos: {$skipped}.");
    }

    public function deleteInmueble(int $clienteId, int $inmuebleId)
    {
        $inmueble = (new InmuebleModel())
            ->where('cliente_id', $clienteId)
            ->where('id', $inmuebleId)
            ->first();

        if (! $inmueble) {
            return redirect()->back()->with('error', 'Inmueble no encontrado.');
        }

        (new InmuebleModel())->delete($inmuebleId);

        return redirect()->back()->with('success', 'Inmueble archivado correctamente.');
    }

    private function findCliente(int $clienteId): ?array
    {
        return (new ClienteModel())->find($clienteId);
    }

    private function findTorre(int $clienteId, int $torreId): ?array
    {
        return (new TorreModel())
            ->where('cliente_id', $clienteId)
            ->where('id', $torreId)
            ->first();
    }

    private function findTorreByName(int $clienteId, string $name): ?array
    {
        return (new TorreModel())
            ->withDeleted()
            ->where('cliente_id', $clienteId)
            ->where('nombre', $name)
            ->first();
    }

    private function findOrCreateTorre(int $clienteId, string $name, int $pisos): array
    {
        $existing = $this->findTorreByName($clienteId, $name);
        if ($existing) {
            if (! empty($existing['deleted_at'])) {
                db_connect()->table('torres')->where('id', $existing['id'])->update([
                    'deleted_at' => null,
                    'num_pisos'  => $pisos,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return ['id' => (int) $existing['id'], 'created' => false];
        }

        $torreModel = new TorreModel();
        $torreModel->insert([
            'cliente_id' => $clienteId,
            'nombre'     => $name,
            'num_pisos'  => $pisos,
        ]);

        return ['id' => (int) $torreModel->getInsertID(), 'created' => true];
    }

    private function casaExists(int $clienteId, string $identificador): bool
    {
        return (new InmuebleModel())
            ->withDeleted()
            ->where('cliente_id', $clienteId)
            ->where('torre_id', null)
            ->where('identificador', $identificador)
            ->first() !== null;
    }

    private function apartamentoExists(int $clienteId, int $torreId, string $identificador): bool
    {
        return (new InmuebleModel())
            ->withDeleted()
            ->where('cliente_id', $clienteId)
            ->where('torre_id', $torreId)
            ->where('identificador', $identificador)
            ->first() !== null;
    }

    private function nullableInt(string $key): ?int
    {
        $value = trim((string) $this->request->getPost($key));

        return $value === '' ? null : (int) $value;
    }
}
