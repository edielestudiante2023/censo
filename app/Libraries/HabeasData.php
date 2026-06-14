<?php

namespace App\Libraries;

class HabeasData
{
    public const STANDARD = 'Autorización para el Tratamiento de Datos Personales. En cumplimiento de la Ley 1581 de 2012, el Decreto 1377 de 2013 y demás normas concordantes, autorizo de manera previa, expresa e informada a {NOMBRE_CONJUNTO}, identificado con NIT {NIT}, en calidad de Responsable del Tratamiento, para recolectar, almacenar, usar, actualizar y suprimir los datos personales aquí suministrados. La finalidad es la gestión administrativa de la copropiedad: actualización del censo de residentes, comunicación con propietarios y residentes, control de acceso y parqueaderos, atención de emergencias y convivencia, y el cumplimiento de las obligaciones propias de la propiedad horizontal. Declaro que la información es veraz y que, como Titular, conozco mi derecho a conocer, actualizar, rectificar y suprimir mis datos y a revocar esta autorización, escribiendo a {CORREO_ADMIN}. El suministro de datos de terceros (otros residentes) se realiza bajo mi responsabilidad, manifestando contar con su autorización. Esta autorización se entiende otorgada al enviar el presente formulario.';

    private const LEGACY_DEFAULTS = [
        'Autorizo el tratamiento de mis datos a {NOMBRE_CONJUNTO} (NIT {NIT}) conforme a la Ley 1581 de 2012.',
    ];

    public static function standard(): string
    {
        return self::STANDARD;
    }

    public static function customOrStandard(?string $custom): string
    {
        $custom = trim((string) $custom);

        if ($custom === '' || in_array($custom, self::LEGACY_DEFAULTS, true)) {
            return self::STANDARD;
        }

        return $custom;
    }

    public static function resolve(array $cliente): string
    {
        return strtr(self::customOrStandard($cliente['texto_habeas_data'] ?? null), [
            '{NOMBRE_CONJUNTO}' => (string) ($cliente['nombre_tercero'] ?? ''),
            '{NIT}'             => (string) ($cliente['documento'] ?? ''),
            '{CORREO_ADMIN}'    => (string) ($cliente['email'] ?? ''),
        ]);
    }
}
