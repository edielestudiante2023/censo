<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($cliente['nombre_tercero']) ?> - Censo</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .topbar { background: #1f2937; color: #fff; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .topbar h1 { font-size: 1.05rem; margin: 0; }
        .topbar nav { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .topbar a { color: #fff; text-decoration: none; font-size: .85rem; background: rgba(255,255,255,.12); padding: 7px 14px; border-radius: 8px; }
        .wrap { max-width: 1040px; margin: 28px auto; padding: 0 18px; }
        .header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
        h2 { margin: 0; font-size: 1.35rem; }
        p { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
        .grid { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 16px; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); padding: 22px; }
        .logo { width: 112px; height: 112px; object-fit: cover; border-radius: 18px; background: #e5e7eb; display: block; margin-bottom: 16px; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 10px; font-size: .78rem; font-weight: 700; }
        .badge-on { color: #166534; background: #dcfce7; }
        .badge-off { color: #991b1b; background: #fee2e2; }
        .swatches { display: flex; gap: 10px; margin-top: 14px; }
        .swatch { width: 44px; height: 44px; border-radius: 10px; border: 1px solid #d1d5db; }
        dl { display: grid; grid-template-columns: 170px minmax(0, 1fr); gap: 10px 16px; margin: 0; }
        dt { color: #6b7280; font-weight: 700; font-size: .82rem; }
        dd { margin: 0; font-size: .92rem; overflow-wrap: anywhere; }
        .habeas { white-space: pre-wrap; line-height: 1.5; font-size: .9rem; color: #374151; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 10px 15px; font-weight: 700; font-size: .88rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #1f2937; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .alert { padding: 12px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 14px; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        @media (max-width: 820px) {
            .topbar, .header { flex-direction: column; align-items: stretch; }
            .grid, dl { grid-template-columns: 1fr; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Censo PWA</h1>
        <nav>
            <a href="<?= base_url('admin/clientes') ?>">Clientes</a>
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2><?= esc($cliente['nombre_tercero']) ?></h2>
                <p><?= esc($cliente['slug']) ?></p>
            </div>
            <div class="actions" style="margin-top:0;">
                <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios') ?>">Usuarios</a>
                <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/tablero') ?>">Tablero</a>
                <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/respuestas') ?>">Respuestas</a>
                <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/config') ?>">Configurar conjunto</a>
                <a class="btn btn-primary" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/edit') ?>">Editar cliente</a>
            </div>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <div class="grid">
            <aside class="card">
                <?php if (! empty($cliente['logo'])): ?>
                    <img class="logo" src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_tercero']) ?>">
                <?php else: ?>
                    <span class="logo"></span>
                <?php endif; ?>

                <?php if ((int) $cliente['activo'] === 1): ?>
                    <span class="badge badge-on">Activo</span>
                <?php else: ?>
                    <span class="badge badge-off">Inactivo</span>
                <?php endif; ?>

                <div class="swatches">
                    <span class="swatch" title="Color primario" style="background: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>"></span>
                    <span class="swatch" title="Color secundario" style="background: <?= esc($cliente['color_secundario'] ?: '#0f766e') ?>"></span>
                </div>

                <div class="actions">
                    <?php if (! empty($cliente['logo'])): ?>
                        <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/logo/delete') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-muted" type="submit">Eliminar logo</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/delete') ?>" onsubmit="return confirm('Archivar este cliente?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger" type="submit">Archivar</button>
                    </form>
                </div>
            </aside>

            <section class="card">
                <dl>
                    <dt>Documento</dt>
                    <dd><?= esc($cliente['tipo_documento']) ?> <?= esc($cliente['documento'] ?? '') ?></dd>

                    <dt>Tipo conjunto</dt>
                    <dd><?= esc($cliente['tipo_conjunto']) ?></dd>

                    <dt>Contacto</dt>
                    <dd><?= esc($cliente['persona_contacto'] ?? 'Sin contacto') ?></dd>

                    <dt>Correo</dt>
                    <dd><?= esc($cliente['email'] ?? 'Sin correo') ?></dd>

                    <dt>Telefono</dt>
                    <dd><?= esc($cliente['telefono'] ?? 'Sin telefono') ?></dd>

                    <dt>Ciudad</dt>
                    <dd><?= esc($cliente['ciudad'] ?? 'Sin ciudad') ?></dd>

                    <dt>Direccion</dt>
                    <dd><?= esc($cliente['direccion'] ?? 'Sin direccion') ?></dd>

                    <dt>Creado</dt>
                    <dd><?= esc($cliente['created_at'] ?? '') ?></dd>
                </dl>
            </section>

            <section class="card" style="grid-column:1 / -1;">
                <h3 style="margin-top:0;">Texto Habeas Data</h3>
                <div class="habeas"><?= esc($cliente['texto_habeas_data'] ?? '') ?></div>
            </section>
        </div>
    </main>
</body>
</html>
