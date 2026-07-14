<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/** Calcula cobertura operativa por unidad sin convertir una firma en autorizacion colectiva. */
final class PrivacyHousingCoverage
{
    public function __construct(private ?BaseConnection $db = null)
    {
    }

    public function summarize(int $clienteId, ?int $authorizationId): array
    {
        $authorizationId = $authorizationId !== null && $authorizationId > 0 ? $authorizationId : null;
        $currentCondition = $authorizationId === null ? '0' : 'c.documento_id = ' . $authorizationId;

        $rows = ($this->db ?? db_connect())->table('inmuebles i')
            ->select("i.id, i.tipo, i.identificador, i.piso, i.torre_id, t.nombre AS torre_nombre,
                COUNT(c.id) AS total_decisiones,
                SUM(CASE WHEN {$currentCondition} THEN 1 ELSE 0 END) AS decisiones_vigentes,
                SUM(CASE WHEN {$currentCondition} AND c.decision = 'autorizado' THEN 1 ELSE 0 END) AS autorizadas,
                SUM(CASE WHEN {$currentCondition} AND c.decision = 'parcial' THEN 1 ELSE 0 END) AS parciales,
                SUM(CASE WHEN {$currentCondition} AND c.decision = 'negado' THEN 1 ELSE 0 END) AS negadas,
                MAX(c.otorgado_at) AS ultima_decision_at,
                MAX(CASE WHEN {$currentCondition} THEN c.otorgado_at ELSE NULL END) AS ultima_vigente_at", false)
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->join('dp_consentimientos c', 'c.inmueble_id = i.id AND c.cliente_id = i.cliente_id', 'left')
            ->where('i.cliente_id', $clienteId)
            ->where('i.deleted_at', null)
            ->groupBy(['i.id', 'i.tipo', 'i.identificador', 'i.piso', 'i.torre_id', 't.nombre'])
            ->orderBy('i.tipo', 'ASC')
            ->orderBy('t.nombre', 'ASC')
            ->orderBy('i.identificador', 'ASC')
            ->get()
            ->getResultArray();

        $managed = 0;
        $outdated = 0;
        $pending = 0;
        foreach ($rows as &$row) {
            $row['status'] = self::status($row, $authorizationId !== null);
            $row['label'] = self::label($row);
            if ($row['status'] === 'gestionada') {
                $managed++;
            } elseif ($row['status'] === 'desactualizada') {
                $outdated++;
            } else {
                $pending++;
            }
        }
        unset($row);

        $total = count($rows);

        return [
            'authorization_id' => $authorizationId,
            'total' => $total,
            'gestionadas' => $managed,
            'desactualizadas' => $outdated,
            'pendientes' => $pending,
            'porcentaje' => $total > 0 ? (int) round(($managed / $total) * 100) : 0,
            'faltantes' => array_values(array_filter($rows, static fn (array $row): bool => $row['status'] !== 'gestionada')),
            'unidades' => $rows,
        ];
    }

    public static function status(array $row, bool $hasCurrentAuthorization): string
    {
        if ($hasCurrentAuthorization && (int) ($row['decisiones_vigentes'] ?? 0) > 0) {
            return 'gestionada';
        }
        if ((int) ($row['total_decisiones'] ?? 0) > 0) {
            return 'desactualizada';
        }

        return 'pendiente';
    }

    public static function label(array $row): string
    {
        $tower = trim((string) ($row['torre_nombre'] ?? ''));
        $identifier = trim((string) ($row['identificador'] ?? ''));

        return $tower !== '' ? $tower . ' - ' . $identifier : $identifier;
    }
}
