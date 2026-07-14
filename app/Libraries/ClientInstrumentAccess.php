<?php

namespace App\Libraries;

final class ClientInstrumentAccess
{
    public const POBLACIONAL = 'censo_poblacional';
    public const MASCOTAS = 'censo_mascotas';
    public const DATOS_PERSONALES = 'tratamiento_datos';

    public const LABELS = [
        self::POBLACIONAL => 'Censo poblacional',
        self::MASCOTAS => 'Censo de mascotas',
        self::DATOS_PERSONALES => 'Tratamiento de datos personales',
    ];

    public function enabled(int $clienteId, string $instrument): bool
    {
        if ($clienteId < 1 || ! isset(self::LABELS[$instrument]) || ! db_connect()->tableExists('cliente_instrumentos')) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $row = db_connect()->table('cliente_instrumentos')
            ->where('cliente_id', $clienteId)
            ->where('instrumento', $instrument)
            ->where('estado', 'habilitado')
            ->groupStart()->where('habilitado_desde', null)->orWhere('habilitado_desde <=', $now)->groupEnd()
            ->groupStart()->where('habilitado_hasta', null)->orWhere('habilitado_hasta >=', $now)->groupEnd()
            ->get()->getRowArray();

        return $row !== null;
    }

    public function enabledMap(int $clienteId): array
    {
        $result = [];
        foreach (array_keys(self::LABELS) as $instrument) {
            $result[$instrument] = $this->enabled($clienteId, $instrument);
        }

        return $result;
    }

    public function rows(int $clienteId): array
    {
        $stored = db_connect()->tableExists('cliente_instrumentos')
            ? array_column(db_connect()->table('cliente_instrumentos')->where('cliente_id', $clienteId)->get()->getResultArray(), null, 'instrumento')
            : [];
        $rows = [];
        foreach (self::LABELS as $instrument => $label) {
            $rows[$instrument] = ($stored[$instrument] ?? []) + [
                'instrumento' => $instrument,
                'label' => $label,
                'estado' => 'deshabilitado',
                'habilitado_desde' => null,
                'habilitado_hasta' => null,
                'motivo' => null,
            ];
            $rows[$instrument]['label'] = $label;
            $rows[$instrument]['vigente'] = $this->enabled($clienteId, $instrument);
        }

        return $rows;
    }

    public function set(int $clienteId, string $instrument, bool $enabled, ?int $actorId, string $reason, ?string $until = null): void
    {
        if (! isset(self::LABELS[$instrument])) {
            throw new \InvalidArgumentException('Instrumento no soportado.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Debe registrar el motivo contractual u operativo del cambio.');
        }
        $db = db_connect();
        $existing = $db->table('cliente_instrumentos')->where('cliente_id', $clienteId)->where('instrumento', $instrument)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');
        $state = $enabled ? 'habilitado' : 'deshabilitado';
        $data = [
            'cliente_id' => $clienteId,
            'instrumento' => $instrument,
            'estado' => $state,
            'habilitado_desde' => $enabled ? $now : null,
            'habilitado_hasta' => $enabled && $until ? $until . ' 23:59:59' : null,
            'motivo' => $reason,
            'actualizado_por' => $actorId ?: null,
            'updated_at' => $now,
        ];

        $db->transException(true)->transBegin();
        try {
            if ($existing) {
                $db->table('cliente_instrumentos')->where('id', $existing['id'])->update($data);
            } else {
                $data['created_at'] = $now;
                $db->table('cliente_instrumentos')->insert($data);
            }
            if (! $existing || $existing['estado'] !== $state || $existing['habilitado_hasta'] !== $data['habilitado_hasta'] || $existing['motivo'] !== $reason) {
                $db->table('cliente_instrumento_eventos')->insert([
                    'cliente_id' => $clienteId,
                    'instrumento' => $instrument,
                    'estado_anterior' => $existing['estado'] ?? null,
                    'estado_nuevo' => $state,
                    'motivo' => $reason,
                    'actor_usuario_id' => $actorId ?: null,
                    'created_at' => $now,
                ]);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        } finally {
            $db->transException(false);
        }
    }
}
