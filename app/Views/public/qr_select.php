<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($qr['titulo'] ?: 'Censo') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #f3f4f6; color: #111827; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; padding: 20px; }
        .wrap { max-width: 680px; margin: 0 auto; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 12px 36px rgba(0,0,0,.12); overflow: hidden; }
        .hero { background: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>; color: #fff; padding: 28px; text-align: center; }
        .logo { width: 76px; height: 76px; object-fit: cover; border-radius: 16px; background: #fff; display: block; margin: 0 auto 14px; }
        .fallback { width: 76px; height: 76px; border-radius: 16px; margin: 0 auto 14px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.18); font-weight: 800; font-size: 1.8rem; }
        h1 { margin: 0; font-size: 1.45rem; }
        p { color: #6b7280; line-height: 1.45; }
        .hero p { color: rgba(255,255,255,.9); margin-bottom: 0; }
        .body { padding: 24px; }
        label { display: block; font-size: .84rem; font-weight: 800; color: #374151; margin-bottom: 6px; }
        select { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 12px; font-size: 1rem; background: #fff; }
        .habeas { max-height: 220px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; background: #f9fafb; color: #374151; font-size: .88rem; white-space: pre-wrap; line-height: 1.45; margin: 16px 0; }
        .check { display: flex; gap: 10px; align-items: flex-start; }
        .check input { margin-top: 4px; }
        .btn { width: 100%; border: 0; border-radius: 10px; padding: 13px; margin-top: 18px; background: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>; color: #fff; font-weight: 800; font-size: 1rem; cursor: pointer; }
        .alert { padding: 12px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 14px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .empty { padding: 18px; background: #f9fafb; border-radius: 12px; text-align: center; color: #6b7280; }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <header class="hero">
                <?php if (! empty($cliente['logo'])): ?>
                    <img class="logo" src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_tercero']) ?>">
                <?php else: ?>
                    <div class="fallback"><?= esc(substr($cliente['nombre_tercero'], 0, 1)) ?></div>
                <?php endif; ?>
                <h1><?= esc($cliente['nombre_tercero']) ?></h1>
                <p><?= esc($qr['titulo'] ?: ($qr['tipo_instrumento'] === 'poblacional' ? 'Censo poblacional' : 'Censo de mascotas')) ?></p>
            </header>
            <div class="body">
                <?php if (session('error')): ?>
                    <div class="alert"><?= esc(session('error')) ?></div>
                <?php endif; ?>

                <?php if (empty($inmuebles)): ?>
                    <div class="empty">Este conjunto aun no tiene inmuebles configurados.</div>
                <?php else: ?>
                    <form method="post" action="<?= base_url('q/' . $token . '/form') ?>">
                        <?= csrf_field() ?>
                        <label for="inmueble_id">Selecciona tu inmueble</label>
                        <select id="inmueble_id" name="inmueble_id" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($inmuebles as $inmueble): ?>
                                <?php $label = trim(($inmueble['torre_nombre'] ? $inmueble['torre_nombre'] . ' - ' : '') . $inmueble['identificador']); ?>
                                <option value="<?= esc($inmueble['id']) ?>"><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <div class="habeas"><?= esc($habeasData) ?></div>

                        <label class="check">
                            <input type="checkbox" name="autorizacion_datos" value="1" required>
                            <span>Acepto la autorizacion para el tratamiento de datos personales.</span>
                        </label>

                        <button class="btn" type="submit">Continuar</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
