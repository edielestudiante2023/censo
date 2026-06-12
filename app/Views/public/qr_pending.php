<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($qr['titulo'] ?: 'Censo') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 22px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .card { width: 100%; max-width: 460px; background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 10px 35px rgba(0,0,0,.12); text-align: center; }
        .logo { width: 76px; height: 76px; border-radius: 16px; object-fit: cover; margin: 0 auto 16px; display: block; background: #e5e7eb; }
        .fallback { width: 76px; height: 76px; border-radius: 16px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; background: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>; color: #fff; font-size: 1.8rem; font-weight: 800; }
        h1 { margin: 0; font-size: 1.35rem; }
        p { color: #6b7280; line-height: 1.45; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: .78rem; font-weight: 800; background: #eef2ff; color: #3730a3; text-transform: uppercase; }
    </style>
</head>
<body>
    <main class="card">
        <?php if (! empty($cliente['logo'])): ?>
            <img class="logo" src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_tercero']) ?>">
        <?php else: ?>
            <div class="fallback"><?= esc(substr($cliente['nombre_tercero'], 0, 1)) ?></div>
        <?php endif; ?>
        <span class="badge"><?= esc($qr['tipo_instrumento']) ?></span>
        <h1><?= esc($cliente['nombre_tercero']) ?></h1>
        <p><?= esc($qr['titulo'] ?: 'Formulario de censo') ?></p>
        <p>El formulario publico para este QR se implementa en el Hito 8. El token ya quedo activo y listo para enlazarse.</p>
    </main>
</body>
</html>
