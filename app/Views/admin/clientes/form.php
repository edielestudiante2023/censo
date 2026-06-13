<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - Censo</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
</head>
<body>
    <?php
        $value = static fn (string $key, mixed $default = '') => old($key, $cliente[$key] ?? $default);
        $errors = session('errors') ?? [];
    ?>

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
                <h2><?= esc($title) ?></h2>
                <p>Datos base del conjunto y personalizacion visual.</p>
            </div>
            <a class="btn btn-muted" href="<?= base_url('admin/clientes') ?>">Volver</a>
        </div>

        <section class="card">
            <?php if (! empty($errors)): ?>
                <div class="errors">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="<?= esc($method) ?>" action="<?= esc($action) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="grid">
                    <div class="full">
                        <label for="nombre_tercero">Nombre del conjunto</label>
                        <input id="nombre_tercero" name="nombre_tercero" value="<?= esc($value('nombre_tercero')) ?>" required maxlength="191" autofocus>
                    </div>

                    <div>
                        <label for="tipo_documento">Tipo documento</label>
                        <select id="tipo_documento" name="tipo_documento" required>
                            <?php foreach (['NIT', 'CC', 'CE'] as $tipo): ?>
                                <option value="<?= esc($tipo) ?>" <?= $value('tipo_documento', 'NIT') === $tipo ? 'selected' : '' ?>><?= esc($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="documento">Documento / NIT</label>
                        <input id="documento" name="documento" value="<?= esc($value('documento')) ?>" maxlength="30">
                    </div>

                    <div>
                        <label for="persona_contacto">Persona de contacto</label>
                        <input id="persona_contacto" name="persona_contacto" value="<?= esc($value('persona_contacto')) ?>" maxlength="191">
                    </div>

                    <div>
                        <label for="email">Correo administrativo</label>
                        <input type="email" id="email" name="email" value="<?= esc($value('email')) ?>" maxlength="191">
                    </div>

                    <div>
                        <label for="telefono">Telefono</label>
                        <input id="telefono" name="telefono" value="<?= esc($value('telefono')) ?>" maxlength="50">
                    </div>

                    <div>
                        <label for="ciudad">Ciudad</label>
                        <input id="ciudad" name="ciudad" value="<?= esc($value('ciudad')) ?>" maxlength="100">
                    </div>

                    <div class="full">
                        <label for="direccion">Direccion</label>
                        <input id="direccion" name="direccion" value="<?= esc($value('direccion')) ?>" maxlength="191">
                    </div>

                    <div>
                        <label for="tipo_conjunto">Tipo de conjunto</label>
                        <select id="tipo_conjunto" name="tipo_conjunto" required>
                            <?php foreach (['apartamentos' => 'Apartamentos', 'casas' => 'Casas', 'mixto' => 'Mixto'] as $key => $label): ?>
                                <option value="<?= esc($key) ?>" <?= $value('tipo_conjunto', 'apartamentos') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="slug">Slug</label>
                        <input id="slug" name="slug" value="<?= esc($value('slug')) ?>" maxlength="191" placeholder="se genera automaticamente si queda vacio">
                        <div class="hint">Se usa en URLs, QR y configuraciones internas.</div>
                    </div>

                    <div>
                        <label for="color_primario">Color primario</label>
                        <input type="color" id="color_primario" name="color_primario" value="<?= esc($value('color_primario', '#1f2937')) ?>">
                    </div>

                    <div>
                        <label for="color_secundario">Color secundario</label>
                        <input type="color" id="color_secundario" name="color_secundario" value="<?= esc($value('color_secundario', '#0f766e')) ?>">
                    </div>

                    <div class="full">
                        <label for="logo">Logo</label>
                        <?php if (! empty($cliente['logo'])): ?>
                            <div class="logo-preview" style="margin-bottom:10px;">
                                <img src="<?= base_url($cliente['logo']) ?>" alt="Logo actual">
                                <div>
                                    <strong>Logo actual</strong>
                                    <div class="hint"><?= esc($cliente['logo']) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                        <div class="hint">PNG, JPG o WebP. Maximo 2 MB.</div>
                    </div>

                    <div class="full">
                        <label for="texto_habeas_data">Texto Habeas Data</label>
                        <textarea id="texto_habeas_data" name="texto_habeas_data"><?= esc($value('texto_habeas_data')) ?></textarea>
                        <div class="hint">Variables disponibles: {NOMBRE_CONJUNTO}, {NIT}, {CORREO_ADMIN}.</div>
                    </div>

                    <div class="full checks">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" id="activo" name="activo" value="1" <?= (string) $value('activo', 1) === '1' ? 'checked' : '' ?>>
                        <label for="activo" style="margin:0;">Cliente activo</label>
                    </div>
                </div>

                <div class="actions">
                    <a class="btn btn-muted" href="<?= base_url('admin/clientes') ?>">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Guardar cliente</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
