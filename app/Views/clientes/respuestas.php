<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuestas - <?= esc($cliente['nombre_tercero']) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
</head>
<body>
    <?php
        $basePath = $isAdmin ? 'admin/clientes/' . $cliente['id'] . '/respuestas' : 'respuestas';
        $exportPath = $basePath . '/exportar';
        $query = array_filter($filters, static fn ($value) => $value !== null && $value !== '');
    ?>

    <div class="topbar">
        <h1>Censo APP</h1>
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
            <div class="actions" style="margin-top:0;">
                <?php $qs = $query ? '?' . http_build_query($query) : ''; ?>
                <a class="btn btn-primary" href="<?= base_url($basePath . '/completo') . $qs ?>">Censo completo (Excel)</a>
                <a class="btn btn-muted" href="<?= base_url($basePath . '/excel') . $qs ?>">Resumen</a>
                <a class="btn btn-muted" href="<?= base_url($exportPath) . $qs ?>">CSV</a>
            </div>
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
    <?= view('partials/home_fab') ?>
</body>
</html>
