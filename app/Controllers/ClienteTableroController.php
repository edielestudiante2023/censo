<?php

namespace App\Controllers;

use App\Models\ClienteModel;

class ClienteTableroController extends BaseController
{
    public function admin(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return view('clientes/tablero', $this->tableroData($cliente, true));
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

        return view('clientes/tablero', $this->tableroData($cliente, false));
    }

    private function tableroData(array $cliente, bool $isAdmin): array
    {
        $clienteId = (int) $cliente['id'];
        $total     = $this->countInmuebles($clienteId);
        $poblacionalRespondidos = $this->countRespondidos($clienteId, 'censos_poblacionales');
        $mascotasRespondidos    = $this->countRespondidos($clienteId, 'censos_mascotas');

        return [
            'cliente' => $cliente,
            'isAdmin' => $isAdmin,
            'totales' => [
                'inmuebles' => $total,
                'poblacional_respondidos' => $poblacionalRespondidos,
                'poblacional_faltantes' => max(0, $total - $poblacionalRespondidos),
                'poblacional_porcentaje' => $this->percentage($poblacionalRespondidos, $total),
                'mascotas_respondidos' => $mascotasRespondidos,
                'mascotas_faltantes' => max(0, $total - $mascotasRespondidos),
                'mascotas_porcentaje' => $this->percentage($mascotasRespondidos, $total),
            ],
            'faltantesPoblacional' => $this->missingInmuebles($clienteId, 'censos_poblacionales'),
            'faltantesMascotas' => $this->missingInmuebles($clienteId, 'censos_mascotas'),
            'ultimosPoblacional' => $this->latestResponses($clienteId, 'censos_poblacionales'),
            'ultimosMascotas' => $this->latestResponses($clienteId, 'censos_mascotas'),
        ];
    }

    private function findCliente(int $clienteId): ?array
    {
        return (new ClienteModel())->find($clienteId);
    }

    private function countInmuebles(int $clienteId): int
    {
        return (int) db_connect()->table('inmuebles')
            ->where('cliente_id', $clienteId)
            ->where('deleted_at', null)
            ->countAllResults();
    }

    private function countRespondidos(int $clienteId, string $table): int
    {
        $row = db_connect()->table($table)
            ->select('COUNT(DISTINCT inmueble_id) AS total', false)
            ->where('cliente_id', $clienteId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    private function missingInmuebles(int $clienteId, string $table): array
    {
        return db_connect()->table('inmuebles i')
            ->select('i.id, i.tipo, i.identificador, i.piso, t.nombre AS torre_nombre')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->join($table . ' c', 'c.inmueble_id = i.id AND c.deleted_at IS NULL', 'left')
            ->where('i.cliente_id', $clienteId)
            ->where('i.deleted_at', null)
            ->where('c.id', null)
            ->orderBy('i.tipo', 'ASC')
            ->orderBy('t.nombre', 'ASC')
            ->orderBy('i.identificador', 'ASC')
            ->limit(200)
            ->get()
            ->getResultArray();
    }

    private function latestResponses(int $clienteId, string $table): array
    {
        return db_connect()->table($table . ' c')
            ->select('c.id, c.inmueble_id, c.created_at, c.fecha_autorizacion, i.tipo, i.identificador, t.nombre AS torre_nombre')
            ->join('inmuebles i', 'i.id = c.inmueble_id')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('c.cliente_id', $clienteId)
            ->where('c.deleted_at', null)
            ->orderBy('c.created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
    }

    private function percentage(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }
}
