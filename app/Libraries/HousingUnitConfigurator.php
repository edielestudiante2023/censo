<?php

namespace App\Libraries;

use App\Models\InmuebleModel;
use App\Models\TorreModel;

/** Configura el universo habitacional usado para medir la cobertura de autorizaciones. */
final class HousingUnitConfigurator
{
    private const MAX_GENERATE = 10000;

    public function generateHouses(int $clienteId, array $input): array
    {
        $from = (int) ($input['from'] ?? 0);
        $to = (int) ($input['to'] ?? 0);
        $padding = (int) ($input['padding'] ?? 0);
        $prefix = trim((string) ($input['prefix'] ?? 'Casa'));
        $total = $to - $from + 1;

        if ($from < 1 || $to < $from || $padding < 0 || $padding > 10 || $prefix === '') {
            throw new \InvalidArgumentException('Revise el rango, el prefijo y el relleno numerico de las casas.');
        }
        $this->assertGenerationLimit($total);

        $model = new InmuebleModel();
        $created = 0;
        $skipped = 0;
        $db = db_connect();
        $db->transException(true)->transBegin();
        try {
            for ($number = $from; $number <= $to; $number++) {
                $identifier = $prefix . ' ' . ($padding > 0 ? str_pad((string) $number, $padding, '0', STR_PAD_LEFT) : $number);
                $exists = $model->withDeleted()->where('cliente_id', $clienteId)->where('torre_id', null)
                    ->where('identificador', $identifier)->first();
                if ($exists) {
                    $this->restoreUnit((int) $exists['id']);
                    $skipped++;
                    continue;
                }
                if ($model->insert([
                    'cliente_id' => $clienteId, 'torre_id' => null, 'tipo' => 'casa',
                    'identificador' => $identifier, 'piso' => null,
                ]) === false) {
                    throw new \RuntimeException('No fue posible crear la casa ' . $identifier . '.');
                }
                $created++;
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e instanceof \RuntimeException ? $e : new \RuntimeException('No fue posible guardar la configuracion de casas.', 0, $e);
        } finally {
            $db->transException(false);
        }

        return ['created' => $created, 'skipped' => $skipped, 'towers_created' => 0];
    }

    public function generateApartments(int $clienteId, array $input): array
    {
        $towerFrom = (int) ($input['tower_from'] ?? 0);
        $towerTo = (int) ($input['tower_to'] ?? 0);
        $floors = (int) ($input['floors'] ?? 0);
        $unitsPerFloor = (int) ($input['units_per_floor'] ?? 0);
        $unitFrom = (int) ($input['unit_from'] ?? 0);
        $towerPrefix = trim((string) ($input['tower_prefix'] ?? 'Torre'));
        $total = ($towerTo - $towerFrom + 1) * $floors * $unitsPerFloor;

        if ($towerFrom < 1 || $towerTo < $towerFrom || $floors < 1 || $floors > 200
            || $unitsPerFloor < 1 || $unitsPerFloor > 200 || $unitFrom < 1 || $towerPrefix === '') {
            throw new \InvalidArgumentException('Revise torres, pisos, unidades por piso y numeracion inicial.');
        }
        $this->assertGenerationLimit($total);

        $db = db_connect();
        $model = new InmuebleModel();
        $created = 0;
        $skipped = 0;
        $towersCreated = 0;
        $digits = max(2, strlen((string) ($unitFrom + $unitsPerFloor - 1)));
        $db->transException(true)->transBegin();
        try {
            for ($towerNumber = $towerFrom; $towerNumber <= $towerTo; $towerNumber++) {
                $tower = $this->findOrCreateTower($clienteId, $towerPrefix . ' ' . $towerNumber, $floors);
                $towersCreated += $tower['created'] ? 1 : 0;
                for ($floor = 1; $floor <= $floors; $floor++) {
                    for ($offset = 0; $offset < $unitsPerFloor; $offset++) {
                        $identifier = $floor . str_pad((string) ($unitFrom + $offset), $digits, '0', STR_PAD_LEFT);
                        $exists = $model->withDeleted()->where('cliente_id', $clienteId)->where('torre_id', $tower['id'])
                            ->where('identificador', $identifier)->first();
                        if ($exists) {
                            $this->restoreUnit((int) $exists['id']);
                            $skipped++;
                            continue;
                        }
                        if ($model->insert([
                            'cliente_id' => $clienteId, 'torre_id' => $tower['id'], 'tipo' => 'apartamento',
                            'identificador' => $identifier, 'piso' => $floor,
                        ]) === false) {
                            throw new \RuntimeException('No fue posible crear el apartamento ' . $identifier . '.');
                        }
                        $created++;
                    }
                }
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e instanceof \RuntimeException ? $e : new \RuntimeException('No fue posible guardar la configuracion de apartamentos.', 0, $e);
        } finally {
            $db->transException(false);
        }

        return ['created' => $created, 'skipped' => $skipped, 'towers_created' => $towersCreated];
    }

    private function findOrCreateTower(int $clienteId, string $name, int $floors): array
    {
        $model = new TorreModel();
        $existing = $model->withDeleted()->where('cliente_id', $clienteId)->where('nombre', trim($name))->first();
        if ($existing) {
            if (! empty($existing['deleted_at'])) {
                db_connect()->table('torres')->where('id', $existing['id'])->update([
                    'deleted_at' => null, 'num_pisos' => $floors, 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
            return ['id' => (int) $existing['id'], 'created' => false];
        }
        if ($model->insert(['cliente_id' => $clienteId, 'nombre' => trim($name), 'num_pisos' => $floors]) === false) {
            throw new \RuntimeException('No fue posible crear ' . trim($name) . '.');
        }

        return ['id' => (int) $model->getInsertID(), 'created' => true];
    }

    private function restoreUnit(int $id): void
    {
        db_connect()->table('inmuebles')->where('id', $id)->where('deleted_at IS NOT NULL', null, false)->update([
            'deleted_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function assertGenerationLimit(int $total): void
    {
        if ($total < 1 || $total > self::MAX_GENERATE) {
            throw new \InvalidArgumentException('La configuracion debe generar entre 1 y ' . self::MAX_GENERATE . ' unidades por ejecucion.');
        }
    }
}
