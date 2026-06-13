<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($cliente['nombre_tercero']) ?> - Censo</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
</head>
<body>
    <div class="topbar">
        <h1>Censo APP</h1>
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
                <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/inteligencia') ?>">Inteligencia</a>
                <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr') ?>">QR</a>
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
