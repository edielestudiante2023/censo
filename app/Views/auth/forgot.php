<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contrasena · Censo APP</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png') ?>">
    <style>
        :root { --ink:#0f1623; --ink2:#1f2937; --gold:#c9a227; --line:#e5e7eb; --muted:#6b7280; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:22px;
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; color:#111827;
            background: radial-gradient(900px 500px at 12% -8%, rgba(201,162,39,.20), transparent 60%),
                        linear-gradient(135deg,#0f1623 0%,#1a2535 55%,#0b111c 100%); }
        .card { background:#fff; width:100%; max-width:410px; border-radius:22px; padding:34px 30px 26px;
            box-shadow:0 30px 70px rgba(0,0,0,.45); }
        .badge { width:70px; height:70px; border-radius:18px; margin:0 auto 14px; display:block; padding:10px;
            background:linear-gradient(160deg,#1f2937,#0f1623); box-shadow:inset 0 0 0 1px rgba(201,162,39,.45); }
        .badge img { width:100%; height:100%; object-fit:contain; }
        h1 { font-size:1.3rem; margin:0; text-align:center; color:var(--ink); }
        p.sub { text-align:center; color:var(--muted); font-size:.9rem; margin:10px 0 0; }
        label { display:block; font-size:.8rem; font-weight:600; color:#374151; margin:18px 0 7px; }
        input { width:100%; padding:13px 14px; border:1.5px solid var(--line); border-radius:12px; font-size:.96rem; outline:none; }
        input:focus { border-color:var(--gold); box-shadow:0 0 0 4px rgba(201,162,39,.18); }
        .btn { width:100%; margin-top:22px; padding:14px; border:0; border-radius:12px; cursor:pointer; color:#fff;
            font-size:.98rem; font-weight:700; background:linear-gradient(135deg,#1f2937,#0f1623); }
        .btn:hover { filter:brightness(1.08); }
        .alert { padding:11px 14px; border-radius:12px; font-size:.85rem; margin:16px 0 2px; }
        .alert-error { background:#fef2f2; color:#b42318; border:1px solid #fecaca; }
        .links { text-align:center; margin-top:18px; font-size:.85rem; }
        .links a { color:var(--ink2); font-weight:600; text-decoration:none; }
        .links a:hover { text-decoration:underline; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge"><img src="<?= base_url('assets/icons/icon-512.png') ?>" alt="Censo APP"></span>
        <h1>Recuperar contrasena</h1>
        <p class="sub">Ingresa tu correo y te enviaremos un enlace para restablecerla.</p>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('forgot') ?>">
            <?= csrf_field() ?>
            <label for="email">Correo electronico</label>
            <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" placeholder="tucorreo@dominio.com" required autofocus>
            <button type="submit" class="btn">Enviar enlace</button>
        </form>

        <div class="links"><a href="<?= base_url('login') ?>">&larr; Volver a iniciar sesion</a></div>
    </div>
</body>
</html>
