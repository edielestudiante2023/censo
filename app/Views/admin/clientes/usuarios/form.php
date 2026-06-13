<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - <?= esc($cliente['nombre_tercero']) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
</head>
<body>
    <?php
        $value = static fn (string $key, mixed $default = '') => old($key, $usuario[$key] ?? $default);
        $errors = session('errors') ?? [];
    ?>

    <div class="topbar">
        <h1>Censo APP</h1>
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
