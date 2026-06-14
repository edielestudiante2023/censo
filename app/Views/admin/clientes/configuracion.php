<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuracion - <?= esc($cliente['nombre_tercero']) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
</head>
<body>
    <?php $errors = session('errors') ?? []; ?>

    <div class="topbar">
        <h1>Censo APP</h1>
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
    <?= view('partials/home_fab') ?>
</body>
</html>
