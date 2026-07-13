<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes · Censo APP</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <style>
        .cli { display:flex; align-items:center; gap:12px; }
        .avatar { width:42px; height:42px; border-radius:11px; flex-shrink:0; object-fit:contain; background:#fff; border:1px solid #e5e7eb; }
        .avatar-fb { width:42px; height:42px; border-radius:11px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
            background:linear-gradient(160deg,#1f2937,#0f1623); color:#c9a227; font-weight:800; font-size:1.1rem; }
        .menu { position:relative; display:inline-block; }
        .menu-btn { display:inline-flex; align-items:center; gap:8px; }
        .caret { font-size:.7rem; opacity:.8; }
        .menu-list { position:absolute; right:0; top:calc(100% + 6px); min-width:200px; background:#fff; border:1px solid #e5e7eb;
            border-radius:13px; box-shadow:0 16px 38px rgba(16,22,35,.22); padding:6px; z-index:60; display:none; }
        .menu.open .menu-list { display:block; }
        .menu-list a { display:block; padding:12px 13px; border-radius:9px; text-decoration:none; color:#1f2937; font-size:.88rem; font-weight:600; }
        .menu-list a:hover { background:#f3f4f6; }
        .menu-list a.danger { color:#b42318; }
        .menu-sep { height:1px; background:#eceef1; margin:5px 6px; }
        @media (max-width:720px){ .menu, .menu-btn { width:100%; } .menu-list { right:auto; left:0; width:100%; } }
    </style>
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
            <a class="btn btn-primary" href="<?= base_url('admin/clientes/new') ?>">+ Nuevo cliente</a>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <section class="card">
            <form class="toolbar" method="get" action="<?= base_url('admin/clientes') ?>">
                <input type="search" name="q" value="<?= esc($q ?? '') ?>" placeholder="Buscar por nombre, NIT, correo o ciudad" style="max-width:420px;">
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
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <?php $base = base_url('admin/clientes/' . $cliente['id']); ?>
                            <tr>
                                <td data-label="Cliente">
                                    <div class="cli">
                                        <?php if (! empty($cliente['logo'])): ?>
                                            <img class="avatar" src="<?= base_url($cliente['logo']) ?>" alt="">
                                        <?php else: ?>
                                            <span class="avatar-fb"><?= esc(strtoupper(substr((string) $cliente['nombre_tercero'], 0, 1))) ?></span>
                                        <?php endif; ?>
                                        <div>
                                            <div class="client" style="font-weight:700;"><?= esc($cliente['nombre_tercero']) ?></div>
                                            <div class="muted"><?= esc($cliente['ciudad'] ?: $cliente['slug']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Documento"><?= esc($cliente['tipo_documento']) ?> <?= esc($cliente['documento'] ?? '') ?></td>
                                <td data-label="Contacto">
                                    <div><?= esc($cliente['persona_contacto'] ?? 'Sin contacto') ?></div>
                                    <div class="muted"><?= esc($cliente['email'] ?? '') ?></div>
                                </td>
                                <td data-label="Tipo" style="text-transform:capitalize;"><?= esc($cliente['tipo_conjunto']) ?></td>
                                <td data-label="Estado">
                                    <?php if ((int) $cliente['activo'] === 1): ?>
                                        <span class="badge badge-on">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-off">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acciones" style="text-align:right;">
                                    <div class="menu">
                                        <button type="button" class="btn btn-primary menu-btn" data-menu>Gestionar <span class="caret">&#9662;</span></button>
                                        <div class="menu-list">
                                            <a href="<?= $base ?>">Ver detalle</a>
                                            <a href="<?= $base ?>/edit">Editar</a>
                                            <div class="menu-sep"></div>
                                            <a href="<?= $base ?>/tablero">Tablero</a>
                                            <a href="<?= $base ?>/respuestas">Respuestas</a>
                                            <a href="<?= $base ?>/inteligencia">Estadisticas</a>
                                            <a href="<?= $base ?>/datos-personales">Datos personales</a>
                                            <div class="menu-sep"></div>
                                            <a href="<?= $base ?>/qr">Codigos QR</a>
                                            <a href="<?= $base ?>/config">Configurar conjunto</a>
                                            <a href="<?= $base ?>/usuarios">Usuarios</a>
                                        </div>
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

    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-menu]');
            var openMenus = document.querySelectorAll('.menu.open');
            if (btn) {
                e.preventDefault();
                var menu = btn.closest('.menu');
                var wasOpen = menu.classList.contains('open');
                openMenus.forEach(function (m) { m.classList.remove('open'); });
                if (!wasOpen) { menu.classList.add('open'); }
            } else if (!e.target.closest('.menu-list')) {
                openMenus.forEach(function (m) { m.classList.remove('open'); });
            }
        });
    </script>
    <?= view('partials/home_fab') ?>
</body>
</html>
