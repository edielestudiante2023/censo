<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - <?= esc($cliente['nombre_tercero']) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
</head>
<body>
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
                <h2>Usuarios del cliente</h2>
                <p><?= esc($cliente['nombre_tercero']) ?></p>
            </div>
            <a class="btn btn-primary" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios/new') ?>">Nuevo usuario</a>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <section class="card">
            <?php if (empty($usuarios)): ?>
                <div class="empty">Este cliente aun no tiene usuarios.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Telefono</th>
                            <th>Estado</th>
                            <th>Ultimo ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td data-label="Usuario">
                                    <strong><?= esc($usuario['nombre']) ?></strong>
                                    <div class="muted"><?= esc($usuario['email']) ?></div>
                                </td>
                                <td data-label="Rol"><?= esc($usuario['rol']) ?></td>
                                <td data-label="Telefono"><?= esc($usuario['telefono'] ?? 'Sin telefono') ?></td>
                                <td data-label="Estado">
                                    <?php if ((int) $usuario['activo'] === 1): ?>
                                        <span class="badge badge-on">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-off">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Ultimo ingreso"><?= esc($usuario['last_login'] ?? 'Nunca') ?></td>
                                <td data-label="Acciones">
                                    <div class="actions">
                                        <a class="btn btn-muted" href="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios/' . $usuario['id'] . '/edit') ?>">Editar</a>
                                        <form method="post" action="<?= base_url('admin/clientes/' . $cliente['id'] . '/usuarios/' . $usuario['id'] . '/delete') ?>" onsubmit="return confirm('Archivar este usuario?');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger" type="submit">Archivar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
