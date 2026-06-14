<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('Errors.pageNotFound') ?> · Censo APP</title>
    <style>
        *{box-sizing:border-box;}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#fff;
            background:radial-gradient(900px 500px at 12% -8%,rgba(201,162,39,.20),transparent 60%),
                       linear-gradient(135deg,#0f1623 0%,#1a2535 55%,#0b111c 100%);}
        .box{text-align:center;max-width:460px;}
        .code{font-size:6rem;font-weight:800;line-height:1;color:#c9a227;letter-spacing:2px;}
        h1{font-size:1.4rem;margin:6px 0 10px;}
        p{color:#c7ccd6;font-size:.95rem;margin:0 0 22px;line-height:1.5;}
        .btn{display:inline-block;background:#c9a227;color:#1a1304;text-decoration:none;font-weight:700;
            padding:12px 24px;border-radius:11px;font-size:.95rem;}
        .btn:hover{background:#e3bd45;}
        .foot{margin-top:26px;font-size:.72rem;color:#7b8190;}
        code{display:block;margin-top:14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);
            padding:10px 12px;border-radius:8px;color:#e3bd45;font-size:.8rem;word-break:break-word;}
    </style>
</head>
<body>
    <div class="box">
        <div class="code">404</div>
        <h1>Pagina no encontrada</h1>
        <p>La direccion que buscas no existe o fue movida.</p>
        <?php if (ENVIRONMENT !== 'production' && ! empty($message) && $message !== '(null)') : ?>
            <code><?= nl2br(esc($message)) ?></code>
        <?php endif; ?>
        <div style="margin-top:22px;"><a class="btn" href="<?= site_url('/') ?>">Volver al inicio</a></div>
        <div class="foot">Censo APP · Desarrollado por Enterprisesst &middot; empowered by Cycloid Talent SAS</div>
    </div>
</body>
</html>
