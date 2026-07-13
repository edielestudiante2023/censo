<?php

namespace App\Libraries;

final class PrivacyRequestWorkflow
{
    public const TYPES = ['consulta', 'reclamo', 'rectificacion', 'actualizacion', 'revocatoria', 'supresion'];

    public static function isComplaint(string $type): bool
    {
        return in_array($type, ['reclamo', 'rectificacion', 'actualizacion', 'revocatoria', 'supresion'], true);
    }

    public static function initialDeadline(string $type, string $completeDate): string
    {
        return PrivacyBusinessDays::add($completeDate, $type === 'consulta' ? 10 : 15);
    }

    public static function extensionDeadline(string $type, string $initialDeadline): string
    {
        return PrivacyBusinessDays::add($initialDeadline, $type === 'consulta' ? 5 : 8);
    }

    public static function canRequestCorrection(string $type): bool
    {
        return self::isComplaint($type);
    }

    public static function canExtend(array $request, string $today): bool
    {
        return ! empty($request['vence_at'])
            && empty($request['prorroga_hasta'])
            && ($request['identidad_estado'] ?? '') === 'verificada'
            && $today <= $request['vence_at'];
    }

    public static function abandonmentLimit(string $requestedAt): string
    {
        return (new \DateTimeImmutable($requestedAt))->modify('+2 months')->format('Y-m-d H:i:s');
    }
}
