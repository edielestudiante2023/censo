<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuestas - <?= esc($cliente['nombre_tercero']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .topbar { background: #1f2937; color: #fff; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .topbar h1 { font-size: 1.05rem; margin: 0; }
        .topbar nav { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .topbar a { color: #fff; text-decoration: none; font-size: .85rem; background: rgba(255,255,255,.12); padding: 7px 14px; border-radius: 8px; }
        .wrap { max-width: 1180px; margin: 28px auto; padding: 0 18px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
        h2 { margin: 0; font-size: 1.35rem; }
        p { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); overflow: hidden; }
        .filters { padding: 16px; border-bottom: 1px solid #e5e7eb; display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; align-items: end; }
        label { display: block; font-weight: 700; font-size: .76rem; color: #374151; margin-bottom: 5px; }
        input, select { width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 9px 10px; font-size: .88rem; color: #111827; background: #fff; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 10px 13px; font-weight: 700; font-size: .86rem; cursor: pointer; text-decoration: none; white-space: nowrap; }
        .btn-primary { background: #1f2937; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #edf0f3; vertical-align: middle; font-size: .88rem; }
        th { color: #4b5563; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; background: #f9fafb; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 9px; font-size: .75rem; font-weight: 700; background: #eef2ff; color: #3730a3; }
        .muted { color: #6b7280; font-size: .82rem; }
        .empty { padding: 34px 16px; text-align: center; color: #6b7280; }
        @media (max-width: 980px) {
            .filters { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 720px) {
            .topbar, .header { flex-direction: column; align-items: stretch; }
            .filters { grid-template-columns: 1fr; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr { border-bottom: 1px solid #e5e7eb; padding: 10px 0; }
            td { border: 0; padding: 6px 14px; }
            td[data-label]::before { content: attr(data-label); display: block; color: #6b7280; font-size: .72rem; text-transform: uppercase; margin-bottom: 3px; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <?php
        $basePath = $isAdmin ? 'admin/clientes/' . $cliente['id'] . '/respuestas' : 'respuestas';
        $exportPath = $basePath . '/exportar';
        $query = array_filter($filters, static fn ($value) => $value !== null && $value !== '');
    ?>

    <div class="topbar">
        <h1>Censo PWA</h1>
        <nav>
            <?php if ($isAdmin): ?>
                <a href="<?= base_url('admin/clientes/' . $cliente['id'] . '/tablero') ?>">Tablero</a>
                <a href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Cliente</a>
                <a href="<?= base_url('admin/clientes') ?>">Clientes</a>
            <?php else: ?>
                <a href="<?= base_url('tablero') ?>">Tablero</a>
                <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <?php endif; ?>
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2>Respuestas</h2>
                <p><?= esc($cliente['nombre_tercero']) ?></p>
            </div>
            <a class="btn btn-primary" href="<?= base_url($exportPath) . ($query ? '?' . http_build_query($query) : '') ?>">Exportar CSV</a>
        </div>

        <section class="card">
            <form class="filters" method="get" action="<?= base_url($basePath) ?>">
                <div>
                    <label for="instrumento">Instrumento</label>
                    <select id="instrumento" name="instrumento">
                        <option value="">Todos</option>
                        <option value="poblacional" <?= $filters['instrumento'] === 'poblacional' ? 'selected' : '' ?>>Poblacional</option>
                        <option value="mascotas" <?= $filters['instrumento'] === 'mascotas' ? 'selected' : '' ?>>Mascotas</option>
                    </select>
                </div>
                <div>
                    <label for="torre_id">Torre</label>
                    <select id="torre_id" name="torre_id">
                        <option value="">Todas</option>
                        <?php foreach ($torres as $torre): ?>
                            <option value="<?= esc($torre['id']) ?>" <?= (string) $filters['torre_id'] === (string) $torre['id'] ? 'selected' : '' ?>><?= esc($torre['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="inmueble_id">Inmueble</label>
                    <select id="inmueble_id" name="inmueble_id">
                        <option value="">Todos</option>
                        <?php foreach ($inmuebles as $inmueble): ?>
                            <?php $label = trim(($inmueble['torre_nombre'] ? $inmueble['torre_nombre'] . ' · ' : '') . $inmueble['identificador']); ?>
                            <option value="<?= esc($inmueble['id']) ?>" <?= (string) $filters['inmueble_id'] === (string) $inmueble['id'] ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="desde">Desde</label>
                    <input type="date" id="desde" name="desde" value="<?= esc($filters['desde'] ?? '') ?>">
                </div>
                <div>
                    <label for="hasta">Hasta</label>
                    <input type="date" id="hasta" name="hasta" value="<?= esc($filters['hasta'] ?? '') ?>">
                </div>
                <div style="display:flex; gap:8px;">
                    <button class="btn btn-muted" type="submit">Filtrar</button>
                    <a class="btn btn-muted" href="<?= base_url($basePath) ?>">Limpiar</a>
                </div>
            </form>

            <?php if (empty($respuestas)): ?>
                <div class="empty">No hay respuestas con los filtros seleccionados.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Instrumento</th>
                            <th>Inmueble</th>
                            <th>Fecha</th>
                            <th>Firmante</th>
                            <th>Contacto</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($respuestas as $respuesta): ?>
                            <tr>
                                <td data-label="Instrumento"><span class="badge"><?= esc($respuesta['instrumento']) ?></span></td>
                                <td data-label="Inmueble">
                                    <strong><?= esc($respuesta['identificador']) ?></strong>
                                    <div class="muted"><?= esc($respuesta['torre_nombre'] ?: $respuesta['tipo_inmueble']) ?></div>
                                </td>
                                <td data-label="Fecha"><?= esc($respuesta['created_at']) ?></td>
                                <td data-label="Firmante"><?= esc($respuesta['firmante_nombre'] ?: 'Sin firmante') ?></td>
                                <td data-label="Contacto"><?= esc($respuesta['contacto'] ?: 'Sin contacto') ?></td>
                                <td data-label="PDF">
                                    <a class="btn btn-muted" href="<?= base_url($basePath . '/pdf/' . $respuesta['instrumento'] . '/' . $respuesta['id']) ?>">Descargar PDF</a>
                                    <div class="muted"><?= (int) $respuesta['pdf_enviado'] === 1 ? 'Correo enviado' : 'Correo pendiente' ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($respuestas) >= 300): ?>
                    <p style="padding:0 16px 16px;">Se muestran maximo 300 respuestas. Usa la exportacion para descargar el resultado completo filtrado.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
