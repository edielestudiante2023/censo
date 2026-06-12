<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuracion - <?= esc($cliente['nombre_tercero']) ?></title>
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
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .full { grid-column: 1 / -1; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); padding: 20px; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
        .stat { background: #fff; border-radius: 14px; padding: 16px; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
        .stat strong { display: block; font-size: 1.45rem; }
        .stat span { color: #6b7280; font-size: .8rem; }
        label { display: block; font-weight: 700; font-size: .82rem; color: #374151; margin-bottom: 6px; }
        input, select {
            width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 10px 12px;
            font-size: .92rem; color: #111827; background: #fff;
        }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .form-grid .full { grid-column: 1 / -1; }
        .hint { color: #6b7280; font-size: .76rem; margin-top: 6px; line-height: 1.35; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; margin-top: 14px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 10px 15px; font-weight: 700; font-size: .88rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #1f2937; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .alert { padding: 12px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 14px; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .errors { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: .88rem; }
        .errors ul { margin: 0; padding-left: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 12px; text-align: left; border-bottom: 1px solid #edf0f3; vertical-align: middle; font-size: .88rem; }
        th { color: #4b5563; font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; background: #f9fafb; }
        .empty { padding: 20px; color: #6b7280; text-align: center; background: #f9fafb; border-radius: 10px; }
        .inline-list { display: grid; gap: 8px; }
        .torre-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid #edf0f3; }
        .torre-row:last-child { border-bottom: 0; }
        .muted { color: #6b7280; font-size: .82rem; }
        @media (max-width: 840px) {
            .topbar, .header { flex-direction: column; align-items: stretch; }
            .grid, .stats, .form-grid { grid-template-columns: 1fr; }
            .full, .form-grid .full { grid-column: auto; }
            .actions { justify-content: stretch; }
            .btn { width: 100%; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr { border-bottom: 1px solid #e5e7eb; padding: 10px 0; }
            td { border: 0; padding: 6px 12px; }
            td[data-label]::before { content: attr(data-label); display: block; color: #6b7280; font-size: .72rem; text-transform: uppercase; margin-bottom: 3px; }
        }
    </style>
</head>
<body>
    <?php $errors = session('errors') ?? []; ?>

    <div class="topbar">
        <h1>Censo PWA</h1>
        <nav>
            <a href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Cliente</a>
            <a href="<?= base_url('admin/clientes') ?>">Clientes</a>
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2>Configuracion del conjunto</h2>
                <p><?= esc($cliente['nombre_tercero']) ?> · <?= esc($cliente['tipo_conjunto']) ?></p>
            </div>
            <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Volver al cliente</a>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (! empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat"><strong><?= esc($totales['torres']) ?></strong><span>Torres</span></div>
            <div class="stat"><strong><?= esc($totales['casas']) ?></strong><span>Casas</span></div>
            <div class="stat"><strong><?= esc($totales['apartamentos']) ?></strong><span>Apartamentos</span></div>
            <div class="stat"><strong><?= esc($totales['inmuebles']) ?></strong><span>Total inmuebles</span></div>
        </section>

        <section class="grid">
            <div class="card">
                <h3>Tipo de conjunto</h3>
                <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/config/tipo') ?>">
                    <?= csrf_field() ?>
                    <label for="tipo_conjunto">Tipo</label>
                    <select id="tipo_conjunto" name="tipo_conjunto" required>
                        <?php foreach (['apartamentos' => 'Apartamentos', 'casas' => 'Casas', 'mixto' => 'Mixto'] as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= $cliente['tipo_conjunto'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Actualizar tipo</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3>Crear torre manual</h3>
                <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/config/torres') ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div>
                            <label for="nombre">Nombre</label>
                            <input id="nombre" name="nombre" value="<?= esc(old('nombre')) ?>" placeholder="Torre 1" maxlength="100" required>
                        </div>
                        <div>
                            <label for="num_pisos">Pisos</label>
                            <input type="number" id="num_pisos" name="num_pisos" value="<?= esc(old('num_pisos')) ?>" min="1" max="200">
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Crear torre</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3>Generar casas</h3>
                <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/config/generar-casas') ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div>
                            <label for="prefijo">Prefijo</label>
                            <input id="prefijo" name="prefijo" value="<?= esc(old('prefijo', 'Casa ')) ?>" maxlength="20">
                        </div>
                        <div>
                            <label for="padding">Relleno</label>
                            <input type="number" id="padding" name="padding" value="<?= esc(old('padding', '0')) ?>" min="0" max="10">
                        </div>
                        <div>
                            <label for="desde">Desde</label>
                            <input type="number" id="desde" name="desde" value="<?= esc(old('desde', '1')) ?>" min="1" required>
                        </div>
                        <div>
                            <label for="hasta">Hasta</label>
                            <input type="number" id="hasta" name="hasta" value="<?= esc(old('hasta', '50')) ?>" min="1" required>
                        </div>
                    </div>
                    <div class="hint">Ejemplo: prefijo "Casa ", desde 1, hasta 50 genera Casa 1 ... Casa 50. Los existentes se omiten.</div>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Generar casas</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3>Generar apartamentos</h3>
                <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/config/generar-apartamentos') ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div>
                            <label for="prefijo_torre">Prefijo torre</label>
                            <input id="prefijo_torre" name="prefijo_torre" value="<?= esc(old('prefijo_torre', 'Torre')) ?>" maxlength="60" required>
                        </div>
                        <div>
                            <label for="pisos">Pisos</label>
                            <input type="number" id="pisos" name="pisos" value="<?= esc(old('pisos', '5')) ?>" min="1" max="200" required>
                        </div>
                        <div>
                            <label for="torre_desde">Torre desde</label>
                            <input type="number" id="torre_desde" name="torre_desde" value="<?= esc(old('torre_desde', '1')) ?>" min="1" required>
                        </div>
                        <div>
                            <label for="torre_hasta">Torre hasta</label>
                            <input type="number" id="torre_hasta" name="torre_hasta" value="<?= esc(old('torre_hasta', '1')) ?>" min="1" required>
                        </div>
                        <div>
                            <label for="aptos_por_piso">Aptos por piso</label>
                            <input type="number" id="aptos_por_piso" name="aptos_por_piso" value="<?= esc(old('aptos_por_piso', '4')) ?>" min="1" max="200" required>
                        </div>
                        <div>
                            <label for="apto_desde">Apto inicial</label>
                            <input type="number" id="apto_desde" name="apto_desde" value="<?= esc(old('apto_desde', '1')) ?>" min="1" required>
                        </div>
                    </div>
                    <div class="hint">Ejemplo: 5 pisos y 4 aptos por piso genera 101, 102, 103, 104 ... 504. Las torres existentes se reutilizan.</div>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Generar apartamentos</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3>Torres</h3>
                <?php if (empty($torres)): ?>
                    <div class="empty">No hay torres creadas.</div>
                <?php else: ?>
                    <div class="inline-list">
                        <?php foreach ($torres as $torre): ?>
                            <div class="torre-row">
                                <div>
                                    <strong><?= esc($torre['nombre']) ?></strong>
                                    <div class="muted"><?= esc($torre['num_pisos'] ?: 'Sin pisos definidos') ?></div>
                                </div>
                                <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/config/torres/' . $torre['id'] . '/delete') ?>" onsubmit="return confirm('Archivar esta torre?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger" type="submit">Archivar</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card full">
                <h3>Inmuebles recientes</h3>
                <?php if (empty($inmuebles)): ?>
                    <div class="empty">No hay inmuebles creados.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Torre</th>
                                <th>Identificador</th>
                                <th>Piso</th>
                                <th>Creado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inmuebles as $inmueble): ?>
                                <tr>
                                    <td data-label="Tipo"><?= esc($inmueble['tipo']) ?></td>
                                    <td data-label="Torre"><?= esc($inmueble['torre_nombre'] ?? 'N/A') ?></td>
                                    <td data-label="Identificador"><strong><?= esc($inmueble['identificador']) ?></strong></td>
                                    <td data-label="Piso"><?= esc($inmueble['piso'] ?? 'N/A') ?></td>
                                    <td data-label="Creado"><?= esc($inmueble['created_at'] ?? '') ?></td>
                                    <td data-label="Acciones">
                                        <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/config/inmuebles/' . $inmueble['id'] . '/delete') ?>" onsubmit="return confirm('Archivar este inmueble?');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger" type="submit">Archivar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin-top:12px;">Se muestran maximo 200 inmuebles para mantener esta pantalla liviana.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
