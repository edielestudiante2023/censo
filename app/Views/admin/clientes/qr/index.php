<?php
$basePath  = $basePath ?? ('admin/clientes/' . $cliente['id'] . '/qr');
$isAdminQr = $isAdminQr ?? true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codigos QR · <?= esc($cliente['nombre_tercero']) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <style>
        .qr-item p { margin: 4px 0; }
        .qr-url { word-break: break-all; font-size: .78rem; color: #6b7280; background: #f9fafb; border: 1px solid #eef0f3; border-radius: 8px; padding: 7px 9px; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Censo APP</h1>
        <nav>
            <?php if ($isAdminQr): ?>
                <a href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Cliente</a>
                <a href="<?= base_url('admin/clientes') ?>">Clientes</a>
            <?php else: ?>
                <a href="<?= base_url('dashboard') ?>">Inicio</a>
            <?php endif; ?>
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2>Codigos QR</h2>
                <p>Genera el QR de cada instrumento para imprimir y pegar en las torres. <?= esc($cliente['nombre_tercero']) ?></p>
            </div>
            <a class="btn btn-muted" href="<?= base_url($isAdminQr ? 'admin/clientes/' . $cliente['id'] : 'dashboard') ?>">Volver</a>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <section class="grid">
            <div class="card">
                <h3 style="margin-top:0;">Generar QR</h3>
                <form method="post" action="<?= base_url($basePath) ?>">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="tipo_instrumento">Instrumento</label>
                        <select id="tipo_instrumento" name="tipo_instrumento" required>
                            <option value="poblacional">Censo poblacional</option>
                            <option value="mascotas">Censo de mascotas</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="anio">Ano de la campana</label>
                        <input id="anio" name="anio" type="number" min="2020" max="2100" value="<?= esc(date('Y')) ?>" required>
                        <p class="hint">Cada ano es una campana nueva: el QR anterior de ese instrumento queda inactivo.</p>
                    </div>
                    <div class="field">
                        <label for="titulo">Titulo (opcional)</label>
                        <input id="titulo" name="titulo" maxlength="191" placeholder="Censo poblacional">
                    </div>
                    <button class="btn btn-primary" type="submit">Generar QR</button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">QR generados</h3>
                <?php if (empty($qrCodes)): ?>
                    <div class="empty">Aun no hay QR generados.</div>
                <?php else: ?>
                    <div class="qr-list">
                        <?php foreach ($qrCodes as $qr): ?>
                            <article class="qr-item">
                                <div class="qr-preview">
                                    <img src="<?= base_url($basePath . '/' . $qr['id'] . '.svg') ?>" alt="<?= esc($qr['titulo'] ?: $qr['tipo_instrumento']) ?>">
                                </div>
                                <div>
                                    <span class="badge <?= (int) $qr['activo'] === 1 ? 'badge-on' : 'badge-off' ?>"><?= (int) $qr['activo'] === 1 ? 'Campana vigente' : 'Historico (inactivo)' ?></span>
                                    <h3 style="margin:10px 0 2px;"><?= esc($qr['titulo'] ?: $qr['tipo_instrumento']) ?></h3>
                                    <p class="muted"><span style="text-transform:capitalize;"><?= esc($qr['tipo_instrumento']) ?></span> · Ano <?= esc($qr['anio'] ?? '-') ?></p>
                                    <p class="qr-url"><?= esc(base_url('q/' . $qr['token'])) ?></p>

                                    <form method="post" action="<?= base_url($basePath . '/' . $qr['id']) ?>">
                                        <?= csrf_field() ?>
                                        <div class="field">
                                            <label for="titulo_<?= esc($qr['id']) ?>">Titulo</label>
                                            <input id="titulo_<?= esc($qr['id']) ?>" name="titulo" value="<?= esc($qr['titulo'] ?? '') ?>" maxlength="191">
                                        </div>
                                        <div class="checks">
                                            <input type="hidden" name="activo" value="0">
                                            <input type="checkbox" id="activo_<?= esc($qr['id']) ?>" name="activo" value="1" <?= (int) $qr['activo'] === 1 ? 'checked' : '' ?>>
                                            <label for="activo_<?= esc($qr['id']) ?>" style="margin:0;">Activo</label>
                                        </div>
                                        <div class="actions">
                                            <button class="btn btn-muted" type="submit">Guardar</button>
                                            <a class="btn btn-muted" href="<?= base_url($basePath . '/' . $qr['id'] . '.svg') ?>" target="_blank" rel="noopener">SVG</a>
                                            <a class="btn btn-primary" href="<?= base_url($basePath . '/' . $qr['id'] . '/pieza') ?>" target="_blank" rel="noopener">Pieza grafica</a>
                                        </div>
                                    </form>
                                    <form method="post" action="<?= base_url($basePath . '/' . $qr['id'] . '/regenerate') ?>" onsubmit="return confirm('Regenerar el token invalidara el QR anterior. Continuar?');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-danger" type="submit" style="margin-top:8px;">Regenerar token</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?= view('partials/home_fab') ?>
</body>
</html>
