<?php
$q = function (array $extra = [], array $remove = []) use ($filters) {
    $base = [];
    foreach (['torre_id', 'tipo', 'sexo', 'edad', 'parentesco_id'] as $k) {
        if ($filters[$k] !== null) {
            $base[$k] = $filters[$k];
        }
    }
    foreach ($remove as $k) {
        unset($base[$k]);
    }
    $base = array_merge($base, $extra);
    return $base ? '?' . http_build_query($base) : '';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inteligencia · <?= esc($cliente['nombre_tercero']) ?></title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; background:#f3f4f6; color:#111827; }
        .topbar { background:#0f1623; color:#fff; padding:14px 22px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; }
        .topbar h1 { font-size:1.05rem; margin:0; }
        .topbar nav { display:flex; gap:8px; flex-wrap:wrap; }
        .topbar a { color:#fff; text-decoration:none; font-size:.85rem; background:rgba(255,255,255,.12); padding:7px 14px; border-radius:8px; }
        .topbar a:hover { background:rgba(255,255,255,.22); }
        .wrap { max-width:1200px; margin:24px auto; padding:0 18px; }
        h2 { margin:0 0 4px; }
        .muted { color:#6b7280; font-size:.9rem; }
        .kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:12px; margin:18px 0; }
        .kpi { background:#fff; border-radius:14px; padding:16px 18px; box-shadow:0 4px 14px rgba(0,0,0,.06); }
        .kpi .n { font-size:1.7rem; font-weight:800; color:#0f1623; }
        .kpi .l { font-size:.78rem; color:#6b7280; text-transform:uppercase; letter-spacing:.03em; margin-top:3px; }
        .kpi .gold { color:#c9a227; }
        .panel { background:#fff; border-radius:14px; box-shadow:0 4px 14px rgba(0,0,0,.06); padding:16px; margin-bottom:16px; }
        .filters { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; align-items:end; }
        label { display:block; font-weight:700; font-size:.74rem; color:#374151; margin-bottom:5px; }
        select { width:100%; border:1px solid #d1d5db; border-radius:9px; padding:9px 10px; font-size:.86rem; background:#fff; }
        .btn { display:inline-flex; align-items:center; justify-content:center; border:0; border-radius:9px; padding:9px 13px; font-weight:700; font-size:.84rem; cursor:pointer; text-decoration:none; }
        .btn-primary { background:#0f1623; color:#fff; } .btn-muted { background:#e5e7eb; color:#111827; }
        .chips { display:flex; gap:8px; flex-wrap:wrap; margin:6px 0 0; }
        .chip { background:#eef2ff; color:#3730a3; border-radius:999px; padding:5px 12px; font-size:.78rem; font-weight:600; text-decoration:none; display:inline-flex; gap:7px; align-items:center; }
        .chip b { font-weight:800; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px; }
        .chart-card { background:#fff; border-radius:14px; box-shadow:0 4px 14px rgba(0,0,0,.06); padding:16px; }
        .chart-card h3 { margin:0 0 4px; font-size:.98rem; }
        .chart-card .hint { font-size:.72rem; color:#9ca3af; margin-bottom:8px; }
        .chart-box { position:relative; height:260px; }
        .empty { padding:40px; text-align:center; color:#6b7280; }
        @media (max-width:980px){ .filters{ grid-template-columns:repeat(2,1fr);} }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Censo APP · Inteligencia</h1>
        <nav>
            <?php if ($isAdmin): ?>
                <a href="<?= base_url('admin/clientes/' . $cliente['id'] . '/tablero') ?>">Tablero</a>
                <a href="<?= base_url('admin/clientes/' . $cliente['id'] . '/respuestas') ?>">Respuestas</a>
                <a href="<?= base_url('admin/clientes') ?>">Clientes</a>
            <?php else: ?>
                <a href="<?= base_url('tablero') ?>">Tablero</a>
                <a href="<?= base_url('respuestas') ?>">Respuestas</a>
                <a href="<?= base_url('dashboard') ?>">Inicio</a>
            <?php endif; ?>
            <a href="<?= base_url('logout') ?>">Salir</a>
        </nav>
    </div>

    <main class="wrap">
        <h2>Inteligencia de negocio</h2>
        <p class="muted"><?= esc($cliente['nombre_tercero']) ?> · datos del censo poblacional</p>

        <div class="kpis">
            <div class="kpi"><div class="n"><?= esc($kpis['personas']) ?></div><div class="l">Personas</div></div>
            <div class="kpi"><div class="n"><?= esc($kpis['hogares']) ?></div><div class="l">Hogares respondidos</div></div>
            <div class="kpi"><div class="n gold"><?= esc($kpis['cobertura']) ?>%</div><div class="l">Cobertura (<?= esc($kpis['respondidos']) ?>/<?= esc($kpis['inmuebles']) ?> inmuebles)</div></div>
            <div class="kpi"><div class="n"><?= esc($kpis['mascotas']) ?></div><div class="l">Mascotas</div></div>
            <div class="kpi"><div class="n"><?= esc($kpis['vehiculos']) ?></div><div class="l">Vehiculos</div></div>
        </div>

        <div class="panel">
            <form class="filters" method="get" action="<?= base_url($basePath) ?>">
                <div>
                    <label>Torre</label>
                    <select name="torre_id">
                        <option value="">Todas</option>
                        <?php foreach ($torres as $t): ?>
                            <option value="<?= esc($t['id']) ?>" <?= (string) $filters['torre_id'] === (string) $t['id'] ? 'selected' : '' ?>><?= esc($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Tipo inmueble</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="casa" <?= $filters['tipo'] === 'casa' ? 'selected' : '' ?>>Casa</option>
                        <option value="apartamento" <?= $filters['tipo'] === 'apartamento' ? 'selected' : '' ?>>Apartamento</option>
                    </select>
                </div>
                <div>
                    <label>Sexo</label>
                    <select name="sexo">
                        <option value="">Todos</option>
                        <option value="M" <?= $filters['sexo'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $filters['sexo'] === 'F' ? 'selected' : '' ?>>Femenino</option>
                        <option value="Otro" <?= $filters['sexo'] === 'Otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                <div>
                    <label>Rango edad</label>
                    <select name="edad">
                        <option value="">Todos</option>
                        <?php foreach (['0-12', '13-17', '18-29', '30-44', '45-59', '60+'] as $rg): ?>
                            <option value="<?= $rg ?>" <?= $filters['edad'] === $rg ? 'selected' : '' ?>><?= $rg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Parentesco</label>
                    <select name="parentesco_id">
                        <option value="">Todos</option>
                        <?php foreach ($parentescos as $p): ?>
                            <option value="<?= esc($p['id']) ?>" <?= (string) $filters['parentesco_id'] === (string) $p['id'] ? 'selected' : '' ?>><?= esc($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex; gap:8px;">
                    <button class="btn btn-primary" type="submit">Aplicar</button>
                    <a class="btn btn-muted" href="<?= base_url($basePath) ?>">Limpiar</a>
                </div>
            </form>

            <?php if (! empty($chips)): ?>
                <div class="chips">
                    <span class="muted" style="align-self:center;">Filtros activos:</span>
                    <?php foreach ($chips as $c): ?>
                        <a class="chip" href="<?= base_url($basePath . $q([], [$c['key']])) ?>"><?= esc($c['label']) ?> <b>&times;</b></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ((int) $kpis['personas'] === 0 && empty($chips)): ?>
            <div class="panel empty">Aun no hay respuestas del censo poblacional para mostrar estadisticas.</div>
        <?php endif; ?>

        <div class="grid">
            <?php foreach ($charts as $i => $ch): ?>
                <div class="chart-card">
                    <h3><?= esc($ch['title']) ?></h3>
                    <div class="hint"><?= $ch['key'] !== '' ? 'Haz clic en un segmento para filtrar.' : 'Informativo.' ?></div>
                    <div class="chart-box"><canvas id="chart<?= $i ?>"></canvas></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="<?= base_url('assets/js/chart.umd.min.js') ?>"></script>
    <script>
        var CHARTS = <?= json_encode($charts, JSON_UNESCAPED_UNICODE) ?>;
        var PALETTE = ['#0f1623','#c9a227','#3b82f6','#10b981','#ef4444','#8b5cf6','#f59e0b','#06b6d4','#ec4899','#64748b'];

        function navigate(key, value) {
            var u = new URL(window.location.href);
            u.searchParams.set(key, value);
            window.location.href = u.toString();
        }

        CHARTS.forEach(function (cfg, i) {
            var el = document.getElementById('chart' + i);
            if (!el) return;
            var isPie = cfg.type === 'doughnut' || cfg.type === 'pie';
            new Chart(el, {
                type: cfg.type,
                data: {
                    labels: cfg.labels,
                    datasets: [{
                        data: cfg.data,
                        backgroundColor: isPie ? cfg.labels.map(function (_, j) { return PALETTE[j % PALETTE.length]; }) : '#0f1623',
                        borderColor: '#fff', borderWidth: isPie ? 2 : 0, borderRadius: isPie ? 0 : 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: isPie, position: 'bottom' } },
                    scales: isPie ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    onClick: function (e, els) {
                        if (!cfg.key || !els.length) return;
                        var v = cfg.values[els[0].index];
                        if (v !== null && v !== undefined) navigate(cfg.key, v);
                    }
                }
            });
        });
    </script>
</body>
</html>
