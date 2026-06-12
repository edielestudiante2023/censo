<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pieza QR - <?= esc($cliente['nombre_tercero']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #e5e7eb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #111827; padding: 24px; }
        .toolbar { max-width: 760px; margin: 0 auto 16px; display: flex; justify-content: flex-end; gap: 10px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 9px; padding: 10px 14px; font-weight: 700; font-size: .88rem; cursor: pointer; text-decoration: none; background: #1f2937; color: #fff; }
        .piece { width: 760px; min-height: 1000px; margin: 0 auto; background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 70px rgba(0,0,0,.2); }
        .hero { background: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>; color: #fff; padding: 42px 46px 34px; text-align: center; }
        .logo { width: 92px; height: 92px; border-radius: 18px; object-fit: cover; background: #fff; margin: 0 auto 18px; display: block; }
        .fallback-logo { width: 92px; height: 92px; border-radius: 18px; background: rgba(255,255,255,.18); margin: 0 auto 18px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 2rem; }
        h1 { margin: 0; font-size: 2.35rem; line-height: 1.08; }
        .subtitle { margin: 14px 0 0; font-size: 1.08rem; opacity: .92; }
        .content { padding: 42px 52px 34px; text-align: center; }
        .qr { width: 470px; height: 470px; margin: 0 auto; border: 10px solid <?= esc($cliente['color_secundario'] ?: '#0f766e') ?>; border-radius: 22px; background: #fff; padding: 18px; }
        .qr svg { width: 100%; height: 100%; display: block; }
        .scan { margin: 28px 0 8px; font-size: 1.55rem; font-weight: 800; }
        .instrument { color: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>; font-size: 1.05rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .url { margin: 22px auto 0; max-width: 560px; overflow-wrap: anywhere; color: #4b5563; font-size: .95rem; }
        .footer { margin-top: 34px; padding-top: 22px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: .92rem; line-height: 1.45; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .piece { width: 100%; min-height: 100vh; border-radius: 0; box-shadow: none; }
        }
        @media (max-width: 820px) {
            body { padding: 0; }
            .toolbar { padding: 12px; margin: 0; }
            .piece { width: 100%; min-height: 100vh; border-radius: 0; }
            .hero, .content { padding-left: 24px; padding-right: 24px; }
            .qr { width: min(100%, 420px); height: auto; aspect-ratio: 1 / 1; }
            h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr') ?>">Volver</a>
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <article class="piece">
        <header class="hero">
            <?php if (! empty($cliente['logo'])): ?>
                <img class="logo" src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_tercero']) ?>">
            <?php else: ?>
                <div class="fallback-logo"><?= esc(substr($cliente['nombre_tercero'], 0, 1)) ?></div>
            <?php endif; ?>
            <h1><?= esc($cliente['nombre_tercero']) ?></h1>
            <p class="subtitle"><?= esc($qr['titulo'] ?: ($qr['tipo_instrumento'] === 'poblacional' ? 'Censo poblacional' : 'Censo de mascotas')) ?></p>
        </header>

        <section class="content">
            <div class="qr"><?= $qrSvg ?></div>
            <div class="scan">Escanea el codigo QR</div>
            <div class="instrument"><?= esc($qr['tipo_instrumento'] === 'poblacional' ? 'Censo poblacional' : 'Censo de mascotas') ?></div>
            <div class="url"><?= esc($url) ?></div>
            <div class="footer">
                La informacion sera tratada por la administracion del conjunto conforme a la autorizacion de tratamiento de datos personales del formulario.
            </div>
        </section>
    </article>
</body>
</html>
