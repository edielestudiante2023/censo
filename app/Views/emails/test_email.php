<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6;">
    <div style="font-family: Arial, sans-serif; line-height:1.6; color:#333; max-width:600px; margin:0 auto; padding:20px;">
        <div style="background:#1f2937; color:#fff; padding:26px; text-align:center; border-radius:10px 10px 0 0;">
            <h1 style="margin:0; font-size:20px;">Configuracion exitosa</h1>
        </div>
        <div style="background:#fff; padding:26px; border-radius:0 0 10px 10px;">
            <p>SendGrid esta configurado correctamente para <strong>Censo APP</strong>.</p>
            <ul>
                <li><strong>Fecha y hora:</strong> <?= esc($testDate) ?></li>
                <li><strong>Entorno:</strong> <?= ENVIRONMENT ?></li>
            </ul>
        </div>
        <div style="text-align:center; margin-top:20px; color:#9ca3af; font-size:12px;">
            &copy; <?= date('Y') ?> Censo APP
        </div>
    </div>
</body>
</html>
