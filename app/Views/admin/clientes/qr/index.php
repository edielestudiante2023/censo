<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR - <?= esc($cliente['nombre_tercero']) ?></title>
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
        h3 { margin: 0 0 12px; font-size: 1rem; }
        p { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
        .grid { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 16px; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); padding: 20px; }
        label { display: block; font-weight: 700; font-size: .82rem; color: #374151; margin-bottom: 6px; }
        input, select { width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 10px 12px; font-size: .92rem; color: #111827; background: #fff; }
        .field { margin-bottom: 13px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 9px 13px; font-weight: 700; font-size: .86rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #1f2937; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .alert { padding: 12px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 14px; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .qr-list { display: grid; gap: 14px; }
        .qr-item { border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 16px; align-items: start; }
        .qr-preview { width: 150px; height: 150px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; padding: 8px; }
        .qr-preview img { width: 100%; height: 100%; display: block; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 9px; font-size: .75rem; font-weight: 700; }
        .badge-on { color: #166534; background: #dcfce7; }
        .badge-off { color: #991b1b; background: #fee2e2; }
        .muted { color: #6b7280; font-size: .82rem; overflow-wrap: anywhere; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .checks { display: flex; align-items: center; gap: 8px; margin-top: 10px; }
        .checks input { width: auto; }
        .empty { padding: 24px; text-align: center; color: #6b7280; background: #f9fafb; border-radius: 10px; }
        @media (max-width: 820px) {
            .topbar, .header { flex-direction: column; align-items: stretch; }
            .grid, .qr-item { grid-template-columns: 1fr; }
            .qr-preview { width: 180px; height: 180px; }
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
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2>Codigos QR</h2>
                <p><?= esc($cliente['nombre_tercero']) ?></p>
            </div>
            <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Volver al cliente</a>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <section class="grid">
            <div class="card">
                <h3>Generar QR</h3>
                <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr') ?>">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="tipo_instrumento">Instrumento</label>
                        <select id="tipo_instrumento" name="tipo_instrumento" required>
                            <option value="poblacional">Censo poblacional</option>
                            <option value="mascotas">Censo de mascotas</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="titulo">Titulo</label>
                        <input id="titulo" name="titulo" maxlength="191" placeholder="Censo poblacional">
                    </div>
                    <button class="btn btn-primary" type="submit">Generar QR</button>
                </form>
            </div>

            <div class="card">
                <h3>QR generados</h3>
                <?php if (empty($qrCodes)): ?>
                    <div class="empty">Aun no hay QR generados para este cliente.</div>
                <?php else: ?>
                    <div class="qr-list">
                        <?php foreach ($qrCodes as $qr): ?>
                            <article class="qr-item">
                                <div class="qr-preview">
                                    <img src="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr/' . $qr['id'] . '.svg') ?>" alt="<?= esc($qr['titulo'] ?: $qr['tipo_instrumento']) ?>">
                                </div>
                                <div>
                                    <span class="badge <?= (int) $qr['activo'] === 1 ? 'badge-on' : 'badge-off' ?>"><?= (int) $qr['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span>
                                    <h3 style="margin-top:10px;"><?= esc($qr['titulo'] ?: $qr['tipo_instrumento']) ?></h3>
                                    <p><?= esc($qr['tipo_instrumento']) ?></p>
                                    <p class="muted"><?= esc(base_url('q/' . $qr['token'])) ?></p>

                                    <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr/' . $qr['id']) ?>">
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
                                            <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr/' . $qr['id'] . '.svg') ?>" target="_blank">SVG</a>
                                            <a class="btn btn-primary" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr/' . $qr['id'] . '/pieza') ?>" target="_blank">Pieza</a>
                                        </div>
                                    </form>
                                    <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/qr/' . $qr['id'] . '/regenerate') ?>" onsubmit="return confirm('Regenerar el token invalidara el QR anterior. Continuar?');">
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
</body>
</html>
