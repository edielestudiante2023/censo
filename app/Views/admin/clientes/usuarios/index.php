<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - <?= esc($cliente['nombre_tercero']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .topbar { background: #1f2937; color: #fff; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .topbar h1 { font-size: 1.05rem; margin: 0; }
        .topbar nav { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .topbar a { color: #fff; text-decoration: none; font-size: .85rem; background: rgba(255,255,255,.12); padding: 7px 14px; border-radius: 8px; }
        .wrap { max-width: 1120px; margin: 28px auto; padding: 0 18px; }
        .header { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
        h2 { margin: 0; font-size: 1.35rem; }
        p { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); overflow: hidden; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 9px 14px; font-weight: 700; font-size: .86rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #1f2937; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .alert { padding: 12px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 14px; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 13px 16px; text-align: left; border-bottom: 1px solid #edf0f3; vertical-align: middle; font-size: .9rem; }
        th { color: #4b5563; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; background: #f9fafb; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 9px; font-size: .75rem; font-weight: 700; }
        .badge-on { color: #166534; background: #dcfce7; }
        .badge-off { color: #991b1b; background: #fee2e2; }
        .muted { color: #6b7280; font-size: .82rem; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .empty { padding: 34px 16px; text-align: center; color: #6b7280; }
        @media (max-width: 760px) {
            .topbar, .header { align-items: stretch; flex-direction: column; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr { border-bottom: 1px solid #e5e7eb; padding: 12px 0; }
            td { border: 0; padding: 7px 16px; }
            td[data-label]::before { content: attr(data-label); display: block; color: #6b7280; font-size: .72rem; text-transform: uppercase; margin-bottom: 3px; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Censo PWA</h1>
        <nav>
            <a href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Cliente</a>
            <a href="<?= base_url('admin/clientes') ?>">Clientes</a>
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2>Usuarios del cliente</h2>
                <p><?= esc($cliente['nombre_tercero']) ?></p>
            </div>
            <a class="btn btn-primary" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios/new') ?>">Nuevo usuario</a>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <section class="card">
            <?php if (empty($usuarios)): ?>
                <div class="empty">Este cliente aun no tiene usuarios.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Telefono</th>
                            <th>Estado</th>
                            <th>Ultimo ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td data-label="Usuario">
                                    <strong><?= esc($usuario['nombre']) ?></strong>
                                    <div class="muted"><?= esc($usuario['email']) ?></div>
                                </td>
                                <td data-label="Rol"><?= esc($usuario['rol']) ?></td>
                                <td data-label="Telefono"><?= esc($usuario['telefono'] ?? 'Sin telefono') ?></td>
                                <td data-label="Estado">
                                    <?php if ((int) $usuario['activo'] === 1): ?>
                                        <span class="badge badge-on">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-off">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Ultimo ingreso"><?= esc($usuario['last_login'] ?? 'Nunca') ?></td>
                                <td data-label="Acciones">
                                    <div class="actions">
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios/' . $usuario['id'] . '/edit') ?>">Editar</a>
                                        <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios/' . $usuario['id'] . '/delete') ?>" onsubmit="return confirm('Archivar este usuario?');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger" type="submit">Archivar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
