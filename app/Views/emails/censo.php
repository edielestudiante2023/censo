<?php
$color = trim((string) ($cliente['color_primario'] ?? ''));
$color = preg_match('/^#?[0-9a-fA-F]{6}$/', $color) ? (str_starts_with($color, '#') ? $color : '#' . $color) : '#1f2937';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($label) ?></title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6;">
    <div style="font-family: Arial, sans-serif; line-height:1.6; color:#333; max-width:600px; margin:0 auto; padding:20px;">
        <div style="background:<?= esc($color) ?>; color:#fff; padding:26px; text-align:center; border-radius:10px 10px 0 0;">
            <h1 style="margin:0; font-size:20px;"><?= esc($cliente['nombre_tercero'] ?? 'Conjunto') ?></h1>
            <p style="margin:6px 0 0; font-size:14px; opacity:.9;"><?= esc($label) ?></p>
        </div>
        <div style="background:#fff; padding:26px; border-radius:0 0 10px 10px;">
            <p>Hola,</p>
            <p>Adjuntamos en PDF la copia del formulario de <strong><?= esc($label) ?></strong> que fue diligenciado correctamente.</p>
            <p>Este documento se genera de forma automatica como soporte del registro. Si no reconoces este envio, puedes ignorar este correo.</p>
            <p style="margin-top:18px; font-size:13px; color:#6b7280;">
                Tratamiento de datos conforme a la Ley 1581 de 2012. Para consultas sobre tus datos personales, escribe a la administracion de la copropiedad
                <?= ! empty($cliente['email']) ? '(' . esc($cliente['email']) . ')' : '' ?>.
            </p>
        </div>
        <div style="text-align:center; margin-top:20px; color:#9ca3af; font-size:12px;">
            &copy; <?= date('Y') ?> Censo APP &middot; <?= esc($cliente['nombre_tercero'] ?? '') ?>
        </div>
    </div>
</body>
</html>
