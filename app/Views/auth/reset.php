<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contrasena · Censo APP</title>
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
        label { display:block; font-size:.8rem; font-weight:600; color:#374151; margin:16px 0 7px; }
        .control { position:relative; }
        input { width:100%; padding:13px 14px; border:1.5px solid var(--line); border-radius:12px; font-size:.96rem; outline:none; }
        input:focus { border-color:var(--gold); box-shadow:0 0 0 4px rgba(201,162,39,.18); }
        .toggle { position:absolute; right:8px; top:50%; transform:translateY(-50%); border:0; background:transparent;
            color:var(--muted); cursor:pointer; font-size:.76rem; font-weight:600; padding:6px 8px; border-radius:8px; }
        .btn { width:100%; margin-top:22px; padding:14px; border:0; border-radius:12px; cursor:pointer; color:#fff;
            font-size:.98rem; font-weight:700; background:linear-gradient(135deg,#1f2937,#0f1623); }
        .btn:hover { filter:brightness(1.08); }
        .hint { font-size:.76rem; color:var(--muted); margin-top:7px; }
        .alert { padding:11px 14px; border-radius:12px; font-size:.85rem; margin:16px 0 2px; }
        .alert-error { background:#fef2f2; color:#b42318; border:1px solid #fecaca; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge"><img src="<?= base_url('assets/icons/icon-512.png') ?>" alt="Censo APP"></span>
        <h1>Crear nueva contrasena</h1>
        <p class="sub">Define tu nueva contrasena para ingresar.</p>

        <?php if (session('error')): ?>
            <div class="alert alert-error"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('reset/' . esc($token, 'url')) ?>">
            <?= csrf_field() ?>
            <label for="password">Nueva contrasena</label>
            <div class="control">
                <input type="password" id="password" name="password" minlength="12" required autofocus>
                <button type="button" class="toggle" id="t1">Ver</button>
            </div>
            <div class="hint">Minimo 12 caracteres.</div>

            <label for="password_confirm">Confirmar contrasena</label>
            <div class="control">
                <input type="password" id="password_confirm" name="password_confirm" minlength="12" required>
                <button type="button" class="toggle" id="t2">Ver</button>
            </div>

            <button type="submit" class="btn">Guardar contrasena</button>
        </form>
    </div>
    <script>
    ['t1','t2'].forEach(function(id){
        var b=document.getElementById(id);
        var inp=b.previousElementSibling;
        b.addEventListener('click',function(){
            var s=inp.type==='password'; inp.type=s?'text':'password'; b.textContent=s?'Ocultar':'Ver';
        });
    });
    </script>
</body>
</html>
