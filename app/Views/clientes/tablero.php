<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero - <?= esc($cliente['nombre_tercero']) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
</head>
<body>
    <div class="topbar">
        <h1>Censo APP</h1>
        <nav>
            <?php if ($isAdmin): ?>
                <a href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Cliente</a>
                <a href="<?= base_url('admin/clientes/' . $cliente['id'] . '/respuestas') ?>">Respuestas</a>
                <a href="<?= base_url('admin/clientes') ?>">Clientes</a>
            <?php else: ?>
                <a href="<?= base_url('respuestas') ?>">Respuestas</a>
                <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <?php endif; ?>
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2>Tablero de avance</h2>
                <p><?= esc($cliente['nombre_tercero']) ?></p>
            </div>
            <?php if ($isAdmin): ?>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a class="btn btn-primary" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/inteligencia') ?>">Ver inteligencia</a>
                    <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/respuestas') ?>">Ver respuestas</a>
                    <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Volver al cliente</a>
                </div>
            <?php else: ?>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a class="btn btn-primary" href="<?= base_url('inteligencia') ?>">Ver inteligencia</a>
                    <a class="btn btn-muted" href="<?= base_url('respuestas') ?>">Ver respuestas</a>
                </div>
            <?php endif; ?>
        </div>

        <section class="stats">
            <div class="stat">
                <strong><?= esc($totales['inmuebles']) ?></strong>
                <span>Inmuebles registrados</span>
            </div>
            <div class="stat">
                <strong><?= esc($totales['poblacional_respondidos']) ?></strong>
                <span>Censos poblacionales recibidos</span>
            </div>
            <div class="stat">
                <strong><?= esc($totales['mascotas_respondidos']) ?></strong>
                <span>Censos de mascotas recibidos</span>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h3>Censo poblacional</h3>
                <div class="metric">
                    <div class="metric-row">
                        <strong><?= esc($totales['poblacional_porcentaje']) ?>% diligenciado</strong>
                        <span class="muted"><?= esc($totales['poblacional_faltantes']) ?> faltantes</span>
                    </div>
                    <div class="bar"><span style="width: <?= esc($totales['poblacional_porcentaje']) ?>%;"></span></div>
                </div>
                <?= view('clientes/partials/faltantes_table', ['faltantes' => $faltantesPoblacional]) ?>
            </div>

            <div class="card">
                <h3>Censo de mascotas</h3>
                <div class="metric">
                    <div class="metric-row">
                        <strong><?= esc($totales['mascotas_porcentaje']) ?>% diligenciado</strong>
                        <span class="muted"><?= esc($totales['mascotas_faltantes']) ?> faltantes</span>
                    </div>
                    <div class="bar"><span style="width: <?= esc($totales['mascotas_porcentaje']) ?>%;"></span></div>
                </div>
                <?= view('clientes/partials/faltantes_table', ['faltantes' => $faltantesMascotas]) ?>
            </div>

            <div class="card">
                <h3>Ultimos poblacionales</h3>
                <?= view('clientes/partials/respuestas_table', ['respuestas' => $ultimosPoblacional]) ?>
            </div>

            <div class="card">
                <h3>Ultimos mascotas</h3>
                <?= view('clientes/partials/respuestas_table', ['respuestas' => $ultimosMascotas]) ?>
            </div>
        </section>
    </main>
    <?= view('partials/home_fab') ?>
</body>
</html>
