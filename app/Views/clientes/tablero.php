<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero - <?= esc($cliente['nombre_tercero']) ?></title>
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
        h3 { margin: 0 0 14px; font-size: 1rem; }
        p { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
        .stat, .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
        .stat { padding: 17px; }
        .stat strong { display: block; font-size: 1.65rem; }
        .stat span { color: #6b7280; font-size: .82rem; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .card { padding: 20px; overflow: hidden; }
        .metric { display: grid; gap: 8px; margin-bottom: 16px; }
        .metric-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: .9rem; }
        .bar { height: 10px; border-radius: 999px; background: #e5e7eb; overflow: hidden; }
        .bar span { display: block; height: 100%; background: #0f766e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 11px; text-align: left; border-bottom: 1px solid #edf0f3; vertical-align: middle; font-size: .86rem; }
        th { color: #4b5563; font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; background: #f9fafb; }
        .empty { padding: 20px; color: #6b7280; text-align: center; background: #f9fafb; border-radius: 10px; }
        .muted { color: #6b7280; font-size: .82rem; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 10px 15px; font-weight: 700; font-size: .88rem; cursor: pointer; text-decoration: none; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        @media (max-width: 840px) {
            .topbar, .header { flex-direction: column; align-items: stretch; }
            .stats, .grid { grid-template-columns: 1fr; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr { border-bottom: 1px solid #e5e7eb; padding: 10px 0; }
            td { border: 0; padding: 6px 11px; }
            td[data-label]::before { content: attr(data-label); display: block; color: #6b7280; font-size: .72rem; text-transform: uppercase; margin-bottom: 3px; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Censo PWA</h1>
        <nav>
            <?php if ($isAdmin): ?>
                <a href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Cliente</a>
                <a href="<?= base_url('admin/clientes') ?>">Clientes</a>
            <?php else: ?>
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
                <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Volver al cliente</a>
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
</body>
</html>
