<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - Censo</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .topbar { background: #1f2937; color: #fff; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .topbar h1 { font-size: 1.05rem; margin: 0; }
        .topbar nav { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .topbar a { color: #fff; text-decoration: none; font-size: .85rem; background: rgba(255,255,255,.12); padding: 7px 14px; border-radius: 8px; }
        .wrap { max-width: 960px; margin: 28px auto; padding: 0 18px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
        h2 { margin: 0; font-size: 1.35rem; }
        p { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); padding: 22px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .full { grid-column: 1 / -1; }
        label { display: block; font-weight: 700; font-size: .82rem; color: #374151; margin-bottom: 6px; }
        input, select, textarea {
            width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 10px 12px;
            font-size: .92rem; color: #111827; background: #fff;
        }
        textarea { min-height: 150px; resize: vertical; line-height: 1.45; }
        input[type="color"] { height: 42px; padding: 4px; }
        input[type="file"] { padding: 9px; }
        .hint { color: #6b7280; font-size: .76rem; margin-top: 5px; }
        .checks { display: flex; align-items: center; gap: 9px; padding-top: 24px; }
        .checks input { width: auto; }
        .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 10px 15px; font-weight: 700; font-size: .88rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #1f2937; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        .errors { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: .88rem; }
        .errors ul { margin: 0; padding-left: 18px; }
        .logo-preview { display: flex; align-items: center; gap: 12px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f9fafb; }
        .logo-preview img { width: 58px; height: 58px; border-radius: 10px; object-fit: cover; }
        @media (max-width: 720px) {
            .topbar, .header { flex-direction: column; align-items: stretch; }
            .grid { grid-template-columns: 1fr; }
            .actions { justify-content: stretch; }
            .btn { width: 100%; }
        }
    </style>
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
