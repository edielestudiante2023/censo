<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion - Censo</title>

    <!-- PWA -->
    <meta name="theme-color" content="#1f2937">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Censo">
    <link rel="manifest" href="<?= base_url('manifest_login.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/icons/icon-192.png') ?>">

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
            width: 72px; height: 72px; border-radius: 16px; margin: 0 auto 10px; display: block;
            box-shadow: 0 6px 14px rgba(0,0,0,0.18);
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
        .alert { padding: 10px 13px; border-radius: 10px; font-size: .85rem; margin-bottom: 4px; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .foot { text-align: center; margin-top: 18px; font-size: .75rem; color: #9ca3af; }

        /* PWA install card */
        .pwa-install-section {
            margin-top: 22px; padding: 16px;
            background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 14px; display: none;
        }
        .pwa-install-section.visible { display: flex; align-items: center; gap: 14px; }
        .pwa-install-icon { width: 56px; height: 56px; border-radius: 12px; box-shadow: 0 6px 14px rgba(0,0,0,0.18); flex-shrink: 0; }
        .pwa-install-info { flex: 1; min-width: 0; }
        .pwa-install-info h5 { margin: 0 0 4px; font-size: .95rem; font-weight: 700; color: #111827; }
        .pwa-install-info p { margin: 0 0 8px; font-size: .78rem; color: #6b7280; line-height: 1.3; }
        .btn-pwa-install {
            background: #1f2937; border: none; border-radius: 10px; color: #fff; font-weight: 600;
            font-size: .85rem; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
        }
        .btn-pwa-install:hover { background: #111827; }
        .pwa-ios-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
        .pwa-ios-modal.visible { display: flex; }
        .pwa-ios-modal-content { background: #fff; border-radius: 18px; max-width: 380px; width: 100%; padding: 24px; }
        .pwa-ios-modal-content h4 { margin: 0 0 12px; color: #111827; }
        .pwa-ios-modal-content ol { padding-left: 20px; line-height: 1.6; color: #374151; font-size: .9rem; }
        .pwa-ios-modal-content .btn-close-ios { margin-top: 12px; width: 100%; background: #1f2937; color: #fff; border: none; border-radius: 10px; padding: 10px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <img src="<?= base_url('assets/icons/icon-192.png') ?>" alt="Censo" class="logo">
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

        <div class="foot">&copy; <?= date('Y') ?> Censo PWA</div>
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
    (function() {
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
            btn.addEventListener('click', function() { iosModal.classList.add('visible'); });
            iosClose.addEventListener('click', function() { iosModal.classList.remove('visible'); });
            iosModal.addEventListener('click', function(e) { if (e.target === iosModal) iosModal.classList.remove('visible'); });
            return;
        }

        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            section.classList.add('visible');
        });

        btn.addEventListener('click', function() {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function(choice) {
                if (choice.outcome === 'accepted') section.classList.remove('visible');
                deferredPrompt = null;
            });
        });

        window.addEventListener('appinstalled', function() {
            section.classList.remove('visible');
            deferredPrompt = null;
        });
    })();

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?= base_url('sw_login.js') ?>', {
                scope: '/',
                updateViaCache: 'none'
            }).catch(function(err) { console.log('SW login error:', err); });
        });
    }
    </script>
</body>
</html>
