<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contrasena</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6;">
    <div style="font-family: Arial, sans-serif; line-height:1.6; color:#333; max-width:600px; margin:0 auto; padding:20px;">
        <div style="background:#1f2937; color:#fff; padding:26px; text-align:center; border-radius:10px 10px 0 0;">
            <h1 style="margin:0; font-size:20px;">Restablecer contrasena</h1>
        </div>
        <div style="background:#fff; padding:26px; border-radius:0 0 10px 10px;">
            <p>Hola <?= esc($nombre) ?>,</p>
            <p>Recibimos una solicitud para restablecer la contrasena de tu cuenta en <strong>Censo APP</strong>. Haz clic en el boton para crear una nueva:</p>
            <p style="text-align:center; margin:26px 0;">
                <a href="<?= esc($link) ?>" style="background:#c9a227; color:#1a1304; text-decoration:none; font-weight:700; padding:13px 26px; border-radius:10px; display:inline-block;">Crear nueva contrasena</a>
            </p>
            <p style="font-size:13px; color:#6b7280;">Si el boton no funciona, copia y pega este enlace en tu navegador:<br>
                <span style="word-break:break-all;"><?= esc($link) ?></span>
            </p>
            <p style="font-size:13px; color:#6b7280;">Este enlace caduca en <strong>1 hora</strong> y solo puede usarse una vez. Si no solicitaste este cambio, ignora este correo.</p>
        </div>
        <div style="text-align:center; margin-top:20px; color:#9ca3af; font-size:12px;">
            &copy; <?= date('Y') ?> Censo APP · Desarrollado por Enterprisesst &middot; empowered by Cycloid Talent SAS
        </div>
    </div>
</body>
</html>
