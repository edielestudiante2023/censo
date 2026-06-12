<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - Censo</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .topbar { background: #1f2937; color: #fff; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; }
        .topbar h1 { font-size: 1.05rem; margin: 0; }
        .topbar a { color: #fff; text-decoration: none; font-size: .85rem; background: rgba(255,255,255,.12); padding: 7px 14px; border-radius: 8px; }
        .topbar a:hover { background: rgba(255,255,255,.22); }
        .wrap { max-width: 900px; margin: 28px auto; padding: 0 18px; }
        .card { background: #fff; border-radius: 14px; padding: 26px; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
        .badge { display: inline-block; background: #eef2ff; color: #3730a3; font-size: .75rem; font-weight: 600; padding: 4px 10px; border-radius: 999px; text-transform: capitalize; }
        .alert { padding: 10px 13px; border-radius: 10px; font-size: .85rem; margin-bottom: 14px; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .modules { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-top: 22px; }
        .module { display: block; text-decoration: none; color: #111827; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background: #f9fafb; }
        .module strong { display: block; margin-bottom: 5px; }
        .module span { color: #6b7280; font-size: .84rem; line-height: 1.35; }
        .module:hover { border-color: #1f2937; background: #fff; }
        h2 { margin: 6px 0 4px; }
        p.muted { color: #6b7280; font-size: .9rem; margin: 0; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Censo PWA</h1>
        <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
    </div>
    <div class="wrap">
        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <div class="card">
            <span class="badge"><?= esc($rol ?? 'sin rol') ?></span>
            <h2>Hola, <?= esc($nombre ?? 'usuario') ?> 👋</h2>
            <p class="muted"><?= esc($email ?? '') ?></p>
            <p class="muted" style="margin-top:16px;">Bienvenido al panel.</p>

            <?php if (in_array($rol ?? '', ['superadmin', 'admin'], true)): ?>
                <div class="modules">
                    <a class="module" href="<?= base_url('admin/clientes') ?>">
                        <strong>Clientes</strong>
                        <span>Crear conjuntos, editar branding y preparar la configuracion del inmueble.</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
