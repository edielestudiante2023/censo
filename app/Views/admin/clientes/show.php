<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($cliente['nombre_tercero']) ?> · Censo APP</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <style>
        .sections { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; }
        .section-card { display:block; text-decoration:none; color:#111827; border:1px solid #e6e9ed; border-radius:13px;
            padding:15px 16px; background:#fafbfc; transition:border-color .15s, background .15s, transform .12s; }
        .section-card:hover { border-color:#1f2937; background:#fff; transform:translateY(-2px); }
        .section-card strong { display:block; color:#0f1623; font-size:.96rem; }
        .section-card span { color:#6b7280; font-size:.79rem; line-height:1.35; }
        dl { margin:0; display:grid; grid-template-columns:130px minmax(0,1fr); gap:10px 14px; }
        dt { color:#6b7280; font-weight:700; font-size:.82rem; }
        dd { margin:0; font-size:.9rem; word-break:break-word; overflow-wrap:anywhere; }
        .avatar-lg { width:64px; height:64px; border-radius:14px; object-fit:contain; background:#fff; border:1px solid #e5e7eb; display:block; margin:0 auto 14px; }
        .avatar-lg-fb { width:64px; height:64px; border-radius:14px; display:flex; align-items:center; justify-content:center;
            background:linear-gradient(160deg,#1f2937,#0f1623); color:#c9a227; font-weight:800; font-size:1.6rem; margin:0 auto 14px; }
        .aside-block { margin-bottom:16px; }
        .lbl { display:block; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; margin-bottom:6px; }
        .aside-actions { border-top:1px solid #eef0f2; padding-top:14px; display:flex; flex-direction:column; gap:8px; }
        .aside-actions form { margin:0; }
        .aside-actions .btn { width:100%; }
    </style>
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
                <p><?= esc($cliente['ciudad'] ?: $cliente['slug']) ?></p>
            </div>
            <a class="btn btn-primary" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/edit') ?>">Editar cliente</a>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <section class="card">
            <h3 style="margin:0 0 12px;">Gestion del conjunto</h3>
            <div class="sections">
                <a class="section-card" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/config') ?>"><strong>Configurar conjunto</strong><span>Tipo, torres e inmuebles (generador).</span></a>
                <a class="section-card" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr') ?>"><strong>Codigos QR</strong><span>Generar QR por instrumento y pieza grafica.</span></a>
                <a class="section-card" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios') ?>"><strong>Usuarios</strong><span>Accesos del conjunto (cliente/consejo/comite).</span></a>
                <a class="section-card" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/tablero') ?>"><strong>Tablero</strong><span>Avance del censo y faltantes.</span></a>
                <a class="section-card" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/respuestas') ?>"><strong>Respuestas</strong><span>Censos recibidos, PDF y export CSV.</span></a>
                <a class="section-card" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/inteligencia') ?>"><strong>Estadisticas</strong><span>Estadisticas y graficos con filtros.</span></a>
                <a class="section-card" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/datos-personales') ?>"><strong>Datos personales</strong><span>Programa documental, autorizaciones y derechos de titulares.</span></a>
            </div>
        </section>

        <div class="grid">
            <aside class="card" style="text-align:center;">
                <?php if (! empty($cliente['logo'])): ?>
                    <img class="avatar-lg" src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_tercero']) ?>">
                <?php else: ?>
                    <span class="avatar-lg-fb" style="margin-left:auto;margin-right:auto;"><?= esc(strtoupper(substr((string) $cliente['nombre_tercero'], 0, 1))) ?></span>
                <?php endif; ?>

                <div class="aside-block">
                    <span class="lbl">Estado del cliente</span>
                    <?php if ((int) $cliente['activo'] === 1): ?>
                        <span class="badge badge-on">Activo</span>
                    <?php else: ?>
                        <span class="badge badge-off">Archivado</span>
                    <?php endif; ?>
                </div>

                <div class="aside-block">
                    <span class="lbl">Colores de marca</span>
                    <div class="swatches" style="justify-content:center;">
                        <span class="swatch" title="Color primario: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>" style="background: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>"></span>
                        <span class="swatch" title="Color secundario: <?= esc($cliente['color_secundario'] ?: '#0f766e') ?>" style="background: <?= esc($cliente['color_secundario'] ?: '#0f766e') ?>"></span>
                    </div>
                    <p class="muted" style="font-size:.74rem; margin:8px 0 0;">Se aplican en los PDF, formularios publicos y la pieza grafica del QR de este conjunto.</p>
                </div>

                <div class="aside-actions">
                    <?php if (! empty($cliente['logo'])): ?>
                        <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/logo/delete') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-muted" type="submit">Eliminar logo</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/delete') ?>" onsubmit="return confirm('Archivar este cliente? Dejara de estar activo.');">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger" type="submit">Archivar cliente</button>
                    </form>
                </div>
            </aside>

            <section class="card">
                <dl>
                    <dt>Documento</dt><dd><?= esc($cliente['tipo_documento']) ?> <?= esc($cliente['documento'] ?? '') ?></dd>
                    <dt>Tipo conjunto</dt><dd style="text-transform:capitalize;"><?= esc($cliente['tipo_conjunto']) ?></dd>
                    <dt>Contacto</dt><dd><?= esc($cliente['persona_contacto'] ?? 'Sin contacto') ?></dd>
                    <dt>Correo</dt><dd><?= esc($cliente['email'] ?? 'Sin correo') ?></dd>
                    <dt>Telefono</dt><dd><?= esc($cliente['telefono'] ?? 'Sin telefono') ?></dd>
                    <dt>Ciudad</dt><dd><?= esc($cliente['ciudad'] ?? 'Sin ciudad') ?></dd>
                    <dt>Direccion</dt><dd><?= esc($cliente['direccion'] ?? 'Sin direccion') ?></dd>
                    <dt>Creado</dt><dd><?= esc($cliente['created_at'] ?? '') ?></dd>
                </dl>
            </section>

            <section class="card" style="grid-column:1 / -1;">
                <h3 style="margin:0 0 4px;">Autorizacion de tratamiento de datos (Habeas Data)</h3>
                <p class="muted" style="margin:0 0 12px;">Texto de consentimiento que ve quien diligencia el censo (Ley 1581/2012). Se edita en <a href="<?= base_url('admin/clientes/' . $cliente['id'] . '/edit') ?>">Editar cliente</a>.</p>
                <div class="habeas"><?= esc(\App\Libraries\HabeasData::resolve($cliente)) ?></div>
            </section>
        </div>
    </main>
    <?= view('partials/home_fab') ?>
</body>
</html>
