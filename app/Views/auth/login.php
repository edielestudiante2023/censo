<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion · Censo APP</title>

    <!-- Favicon -->
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png') ?>">

    <!-- PWA -->
    <meta name="theme-color" content="#101826">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Censo">
    <link rel="manifest" href="<?= base_url('manifest_login.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/icons/icon-192.png') ?>">

    <style>
        :root {
            --ink: #0f1623; --ink2: #1f2937; --gold: #c9a227; --gold-2: #e3bd45;
            --line: #e5e7eb; --muted: #6b7280;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            color: #111827; padding: 22px;
            background:
                radial-gradient(900px 500px at 12% -8%, rgba(201,162,39,0.20), transparent 60%),
                radial-gradient(800px 600px at 110% 110%, rgba(201,162,39,0.12), transparent 55%),
                linear-gradient(135deg, #0f1623 0%, #1a2535 55%, #0b111c 100%);
        }
        .shell { width: 100%; max-width: 410px; }
        .card {
            background: rgba(255,255,255,0.98);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 22px;
            padding: 34px 30px 26px;
            box-shadow: 0 30px 70px rgba(0,0,0,0.45), 0 2px 8px rgba(0,0,0,0.2);
        }
        .brand { text-align: center; margin-bottom: 20px; }
        .brand .badge {
            width: 78px; height: 78px; border-radius: 20px; margin: 0 auto 14px; display: block;
            background: linear-gradient(160deg, #1f2937, #0f1623);
            box-shadow: 0 10px 24px rgba(15,22,35,0.45), inset 0 0 0 1px rgba(201,162,39,0.45);
            padding: 11px;
        }
        .brand .badge img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .brand h1 { font-size: 1.45rem; margin: 0; letter-spacing: .2px; color: var(--ink); }
        .brand .tag {
            display: inline-block; margin-top: 7px; font-size: .72rem; font-weight: 700; letter-spacing: .9px;
            text-transform: uppercase; color: var(--gold); background: rgba(201,162,39,0.12);
            padding: 4px 11px; border-radius: 999px;
        }
        .welcome { text-align: center; color: var(--muted); font-size: .9rem; margin: 14px 0 4px; }

        .alert { padding: 11px 14px; border-radius: 12px; font-size: .85rem; margin: 14px 0 2px; display: flex; gap: 8px; align-items: center; }
        .alert-error { background: #fef2f2; color: #b42318; border: 1px solid #fecaca; }
        .alert-success { background: #ecfdf3; color: #067647; border: 1px solid #abefc6; }

        form { margin-top: 8px; }
        .field { margin-top: 15px; }
        label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: 7px; }
        .control { position: relative; }
        .control .ic {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: #9ca3af; pointer-events: none;
        }
        input {
            width: 100%; padding: 13px 14px 13px 42px; border: 1.5px solid var(--line); border-radius: 12px;
            font-size: .96rem; outline: none; transition: border-color .15s, box-shadow .15s; background: #fff; color: #111827;
        }
        input::placeholder { color: #aab1bd; }
        input:focus { border-color: var(--gold); box-shadow: 0 0 0 4px rgba(201,162,39,0.18); }
        .toggle {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            border: 0; background: transparent; color: var(--muted); cursor: pointer; font-size: .76rem;
            font-weight: 600; padding: 6px 8px; border-radius: 8px;
        }
        .toggle:hover { color: var(--ink2); background: #f3f4f6; }

        .btn {
            width: 100%; margin-top: 22px; padding: 14px; border: 0; border-radius: 12px; cursor: pointer;
            font-size: .98rem; font-weight: 700; letter-spacing: .3px; color: #fff;
            background: linear-gradient(135deg, #1f2937, #0f1623);
            box-shadow: 0 10px 22px rgba(15,22,35,0.35); transition: transform .12s, box-shadow .12s;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(15,22,35,0.45); }
        .btn:active { transform: translateY(0); }

        .foot { text-align: center; margin-top: 20px; font-size: .72rem; color: #9ca3af; }

        /* PWA install card */
        .pwa-install-section {
            margin-top: 20px; padding: 15px; background: #f9fafb; border: 1.5px dashed #d8dce3; border-radius: 14px; display: none;
        }
        .pwa-install-section.visible { display: flex; align-items: center; gap: 13px; }
        .pwa-install-icon { width: 50px; height: 50px; border-radius: 12px; box-shadow: 0 6px 14px rgba(0,0,0,0.18); flex-shrink: 0; }
        .pwa-install-info { flex: 1; min-width: 0; }
        .pwa-install-info h5 { margin: 0 0 3px; font-size: .9rem; font-weight: 700; color: var(--ink); }
        .pwa-install-info p { margin: 0 0 8px; font-size: .76rem; color: var(--muted); line-height: 1.3; }
        .btn-pwa-install {
            background: var(--gold); border: 0; border-radius: 9px; color: #1a1304; font-weight: 700; font-size: .82rem;
            padding: 8px 14px; cursor: pointer;
        }
        .btn-pwa-install:hover { background: var(--gold-2); }
        .pwa-ios-modal { display: none; position: fixed; inset: 0; background: rgba(7,11,18,0.75); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
        .pwa-ios-modal.visible { display: flex; }
        .pwa-ios-modal-content { background: #fff; border-radius: 18px; max-width: 380px; width: 100%; padding: 24px; }
        .pwa-ios-modal-content h4 { margin: 0 0 12px; color: var(--ink); }
        .pwa-ios-modal-content ol { padding-left: 20px; line-height: 1.6; color: #374151; font-size: .9rem; }
        .pwa-ios-modal-content .btn-close-ios { margin-top: 14px; width: 100%; background: var(--ink2); color: #fff; border: 0; border-radius: 10px; padding: 11px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
            <div class="brand">
                <span class="badge"><img src="<?= base_url('assets/icons/icon-512.png') ?>" alt="Censo APP"></span>
                <h1>Censo APP</h1>
                <span class="tag">Propiedad Horizontal</span>
            </div>

            <p class="welcome">Ingresa para administrar tus censos.</p>

            <?php if (session('error')): ?>
                <div class="alert alert-error"><?= esc(session('error')) ?></div>
            <?php endif; ?>
            <?php if (session('success')): ?>
                <div class="alert alert-success"><?= esc(session('success')) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login') ?>" autocomplete="on">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Correo electronico</label>
                    <div class="control">
                        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                        <input type="email" id="email" name="email" placeholder="tucorreo@dominio.com" value="<?= esc(old('email')) ?>" required autofocus>
                    </div>
                </div>

                <div class="field">
                    <label for="password">Contrasena</label>
                    <div class="control">
                        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input type="password" id="password" name="password" placeholder="Tu contrasena" required>
                        <button type="button" class="toggle" id="pwToggle" aria-label="Mostrar contrasena">Ver</button>
                    </div>
                </div>

                <button type="submit" class="btn">Ingresar</button>
            </form>

            <!-- PWA: tarjeta de instalacion (no requiere sesion) -->
            <div class="pwa-install-section" id="pwaInstallSection">
                <img src="<?= base_url('assets/icons/icon-192.png') ?>" alt="App" class="pwa-install-icon">
                <div class="pwa-install-info">
                    <h5>Instala la app</h5>
                    <p>Acceso rapido desde la pantalla de inicio de tu dispositivo.</p>
                    <button type="button" class="btn-pwa-install" id="pwaInstallBtn">
                        <span id="pwaInstallBtnText">Descargar app</span>
                    </button>
                </div>
            </div>

            <div class="foot">
                &copy; <?= date('Y') ?> Censo APP<br>
                Desarrollado por <strong>Enterprisesst</strong> · empowered by <strong>Cycloid Talent SAS</strong>
            </div>
        </div>
    </div>

    <!-- Modal iOS -->
    <div class="pwa-ios-modal" id="pwaIosModal">
        <div class="pwa-ios-modal-content">
            <h4>Como instalar en iPhone/iPad</h4>
            <ol>
                <li>Toca el boton <strong>Compartir</strong> en la barra de Safari.</li>
                <li>Elige <strong>"Anadir a pantalla de inicio"</strong>.</li>
                <li>Confirma con <strong>Anadir</strong>.</li>
            </ol>
            <button type="button" class="btn-close-ios" id="pwaIosModalClose">Entendido</button>
        </div>
    </div>

    <script>
    (function () {
        var pw = document.getElementById('password');
        var tg = document.getElementById('pwToggle');
        if (tg) {
            tg.addEventListener('click', function () {
                var show = pw.type === 'password';
                pw.type = show ? 'text' : 'password';
                tg.textContent = show ? 'Ocultar' : 'Ver';
            });
        }
    })();

    (function () {
        var deferredPrompt = null;
        var section = document.getElementById('pwaInstallSection');
        var btn = document.getElementById('pwaInstallBtn');
        var btnText = document.getElementById('pwaInstallBtnText');
        var iosModal = document.getElementById('pwaIosModal');
        var iosClose = document.getElementById('pwaIosModalClose');

        var ua = window.navigator.userAgent;
        var isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches
                        || window.navigator.standalone === true;

        if (isStandalone) { return; }

        if (isIOS) {
            section.classList.add('visible');
            btnText.textContent = 'Como instalar';
            btn.addEventListener('click', function () { iosModal.classList.add('visible'); });
            iosClose.addEventListener('click', function () { iosModal.classList.remove('visible'); });
            iosModal.addEventListener('click', function (e) { if (e.target === iosModal) iosModal.classList.remove('visible'); });
            return;
        }

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
            section.classList.add('visible');
        });

        btn.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (choice) {
                if (choice.outcome === 'accepted') section.classList.remove('visible');
                deferredPrompt = null;
            });
        });

        window.addEventListener('appinstalled', function () {
            section.classList.remove('visible');
            deferredPrompt = null;
        });
    })();

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('<?= base_url('sw_login.js') ?>', {
                scope: '/',
                updateViaCache: 'none'
            }).catch(function (err) { console.log('SW login error:', err); });
        });
    }
    </script>
</body>
</html>
