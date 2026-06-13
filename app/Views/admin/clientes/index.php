<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Censo</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
</head>
<body>
    <div class="topbar">
        <h1>Censo APP</h1>
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
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/tablero') ?>">Tablero</a>
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/respuestas') ?>">Respuestas</a>
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr') ?>">QR</a>
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
