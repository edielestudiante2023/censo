<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion - Censo</title>
    <!-- PWA (el bloque de instalacion se agrega en el Hito 11) -->
    <meta name="theme-color" content="#1f2937">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%); padding: 20px;
        }
        .login-card {
            background: #fff; width: 100%; max-width: 380px; border-radius: 18px;
            padding: 32px 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.35);
        }
        .brand { text-align: center; margin-bottom: 22px; }
        .brand .logo {
            width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 10px;
            background: #1f2937; color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 26px; font-weight: 700;
        }
        .brand h1 { font-size: 1.25rem; margin: 0; color: #111827; }
        .brand p { margin: 4px 0 0; font-size: .82rem; color: #6b7280; }
        label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin: 14px 0 6px; }
        input {
            width: 100%; padding: 11px 13px; border: 1px solid #d1d5db; border-radius: 10px;
            font-size: .95rem; outline: none; transition: border-color .15s;
        }
        input:focus { border-color: #1f2937; }
        .btn {
            width: 100%; margin-top: 22px; padding: 12px; border: none; border-radius: 10px;
            background: #1f2937; color: #fff; font-size: .95rem; font-weight: 600; cursor: pointer;
        }
        .btn:hover { background: #111827; }
        .alert {
            padding: 10px 13px; border-radius: 10px; font-size: .85rem; margin-bottom: 4px;
        }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .foot { text-align: center; margin-top: 18px; font-size: .75rem; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="logo">C</div>
            <h1>Censo PWA</h1>
            <p>Propiedad horizontal</p>
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('login') ?>" autocomplete="on">
            <?= csrf_field() ?>
            <label for="email">Correo electronico</label>
            <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" required autofocus>

            <label for="password">Contrasena</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn">Ingresar</button>
        </form>

        <div class="foot">&copy; <?= date('Y') ?> Censo PWA</div>
    </div>
</body>
</html>
