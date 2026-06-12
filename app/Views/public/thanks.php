<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario recibido</title>
    <?= view('public/partials/form_styles', ['cliente' => $cliente]) ?>
</head>
<body>
    <main class="wrap">
        <header class="top">
            <h1>Formulario recibido</h1>
            <p><?= esc($cliente['nombre_tercero']) ?> · <?= esc(($inmueble['torre_nombre'] ?? '') ? $inmueble['torre_nombre'] . ' - ' . $inmueble['identificador'] : $inmueble['identificador']) ?></p>
        </header>
        <section class="panel">
            <h2>Gracias</h2>
            <p>La informacion fue registrada correctamente.</p>
            <?php if (! empty($pdfReady)): ?>
                <p><a class="btn btn-primary" href="<?= esc(base_url('q/' . $token . '/pdf')) ?>">Descargar PDF del formulario</a></p>
            <?php endif; ?>
            <a class="btn" href="<?= esc(base_url('q/' . $token)) ?>">Enviar otro formulario</a>
        </section>
    </main>
</body>
</html>
