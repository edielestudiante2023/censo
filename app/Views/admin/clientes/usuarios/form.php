<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - <?= esc($cliente['nombre_tercero']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .topbar { background: #1f2937; color: #fff; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .topbar h1 { font-size: 1.05rem; margin: 0; }
        .topbar nav { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .topbar a { color: #fff; text-decoration: none; font-size: .85rem; background: rgba(255,255,255,.12); padding: 7px 14px; border-radius: 8px; }
        .wrap { max-width: 820px; margin: 28px auto; padding: 0 18px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
        h2 { margin: 0; font-size: 1.35rem; }
        p { margin: 4px 0 0; color: #6b7280; font-size: .9rem; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,.06); padding: 22px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .full { grid-column: 1 / -1; }
        label { display: block; font-weight: 700; font-size: .82rem; color: #374151; margin-bottom: 6px; }
        input, select {
            width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 10px 12px;
            font-size: .92rem; color: #111827; background: #fff;
        }
        .hint { color: #6b7280; font-size: .76rem; margin-top: 5px; }
        .checks { display: flex; align-items: center; gap: 9px; padding-top: 24px; }
        .checks input { width: auto; }
        .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 9px; padding: 10px 15px; font-weight: 700; font-size: .88rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #1f2937; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111827; }
        .errors { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: .88rem; }
        .errors ul { margin: 0; padding-left: 18px; }
        @media (max-width: 720px) {
            .topbar, .header { flex-direction: column; align-items: stretch; }
            .grid { grid-template-columns: 1fr; }
            .full { grid-column: auto; }
            .actions { justify-content: stretch; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <?php
        $value = static fn (string $key, mixed $default = '') => old($key, $usuario[$key] ?? $default);
        $errors = session('errors') ?? [];
    ?>

    <div class="topbar">
        <h1>Censo PWA</h1>
        <nav>
            <a href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios') ?>">Usuarios</a>
            <a href="<?= base_url('admin/clientes/' . $cliente['id']) ?>">Cliente</a>
            <a href="<?= base_url('logout') ?>">Cerrar sesion</a>
        </nav>
    </div>

    <main class="wrap">
        <div class="header">
            <div>
                <h2><?= esc($title) ?></h2>
                <p><?= esc($cliente['nombre_tercero']) ?></p>
            </div>
            <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios') ?>">Volver</a>
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

            <form method="post" action="<?= esc($action) ?>">
                <?= csrf_field() ?>
                <div class="grid">
                    <div class="full">
                        <label for="nombre">Nombre</label>
                        <input id="nombre" name="nombre" value="<?= esc($value('nombre')) ?>" maxlength="191" required autofocus>
                    </div>

                    <div>
                        <label for="email">Correo electronico</label>
                        <input type="email" id="email" name="email" value="<?= esc($value('email')) ?>" maxlength="191" required>
                    </div>

                    <div>
                        <label for="telefono">Telefono</label>
                        <input id="telefono" name="telefono" value="<?= esc($value('telefono')) ?>" maxlength="50">
                    </div>

                    <div>
                        <label for="rol_id">Rol</label>
                        <select id="rol_id" name="rol_id" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= esc($rol['id']) ?>" <?= (string) $value('rol_id') === (string) $rol['id'] ? 'selected' : '' ?>>
                                    <?= esc($rol['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="password"><?= $isEdit ? 'Nueva contrasena' : 'Contrasena' ?></label>
                        <input type="password" id="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                        <div class="hint"><?= $isEdit ? 'Dejala vacia para conservar la actual.' : 'Minimo 8 caracteres.' ?></div>
                    </div>

                    <div class="full checks">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" id="activo" name="activo" value="1" <?= (string) $value('activo', 1) === '1' ? 'checked' : '' ?>>
                        <label for="activo" style="margin:0;">Usuario activo</label>
                    </div>
                </div>

                <div class="actions">
                    <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios') ?>">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Guardar usuario</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
