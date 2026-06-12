<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Censo</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .topbar { background: #1f2937; color: #fff; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .topbar h1 { font-size: 1.05rem; margin: 0; }
        .topbar nav { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .topbar a { color: #fff; text-decoration: none; font-size: .85rem; background: rgba(255,255,255,.12); padding: 7px 14px; border-radius: 8px; }
        .topbar a:hover { background: rgba(255,255,255,.22); }
        .wrap { max-width: 1120px; margin: 28px auto; padding: 0 18px; }
        .header { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
        .header h2 { margin: 0; font-size: 1.35rem; }
        .header p { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 9px 14px; font-weight: 700; font-size: .86rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #1f2937; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); overflow: hidden; }
        .toolbar { padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 10px; }
        .toolbar input { flex: 1; min-width: 160px; border: 1px solid #d1d5db; border-radius: 9px; padding: 10px 12px; font-size: .92rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 13px 16px; text-align: left; border-bottom: 1px solid #edf0f3; vertical-align: middle; font-size: .9rem; }
        th { color: #4b5563; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; background: #f9fafb; }
        .logo { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: #e5e7eb; display: block; }
        .client { font-weight: 700; color: #111827; }
        .muted { color: #6b7280; font-size: .82rem; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 9px; font-size: .75rem; font-weight: 700; }
        .badge-on { color: #166534; background: #dcfce7; }
        .badge-off { color: #991b1b; background: #fee2e2; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .alert { padding: 12px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 14px; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .empty { padding: 34px 16px; text-align: center; color: #6b7280; }
        .pager { padding: 14px 16px; }
        @media (max-width: 760px) {
            .header, .toolbar { align-items: stretch; flex-direction: column; }
            .topbar { align-items: flex-start; flex-direction: column; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr { border-bottom: 1px solid #e5e7eb; padding: 12px 0; }
            td { border: 0; padding: 7px 16px; }
            td[data-label]::before { content: attr(data-label); display: block; color: #6b7280; font-size: .72rem; text-transform: uppercase; margin-bottom: 3px; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Censo PWA</h1>
        <nav>
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2>Clientes</h2>
                <p>Administracion de conjuntos, branding y datos base.</p>
            </div>
            <a class="btn btn-primary" href="<?= base_url('admin/clientes/new') ?>">Nuevo cliente</a>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <section class="card">
            <form class="toolbar" method="get" action="<?= base_url('admin/clientes') ?>">
                <input type="search" name="q" value="<?= esc($q ?? '') ?>" placeholder="Buscar por nombre, NIT, correo o ciudad">
                <button class="btn btn-muted" type="submit">Buscar</button>
                <?php if (($q ?? '') !== ''): ?>
                    <a class="btn btn-muted" href="<?= base_url('admin/clientes') ?>">Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if (empty($clientes)): ?>
                <div class="empty">Aun no hay clientes creados.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Documento</th>
                            <th>Contacto</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td data-label="Cliente">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <?php if (! empty($cliente['logo'])): ?>
                                            <img class="logo" src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_tercero']) ?>">
                                        <?php else: ?>
                                            <span class="logo"></span>
                                        <?php endif; ?>
                                        <div>
                                            <div class="client"><?= esc($cliente['nombre_tercero']) ?></div>
                                            <div class="muted"><?= esc($cliente['slug']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Documento">
                                    <?= esc($cliente['tipo_documento']) ?> <?= esc($cliente['documento'] ?? '') ?>
                                </td>
                                <td data-label="Contacto">
                                    <div><?= esc($cliente['persona_contacto'] ?? 'Sin contacto') ?></div>
                                    <div class="muted"><?= esc($cliente['email'] ?? '') ?></div>
                                </td>
                                <td data-label="Tipo"><?= esc($cliente['tipo_conjunto']) ?></td>
                                <td data-label="Estado">
                                    <?php if ((int) $cliente['activo'] === 1): ?>
                                        <span class="badge badge-on">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-off">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acciones">
                                    <div class="actions">
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Ver</a>
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/edit') ?>">Editar</a>
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/config') ?>">Configurar</a>
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios') ?>">Usuarios</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="pager"><?= $pager->links() ?></div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
