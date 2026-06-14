<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Censo de mascotas</title>
    <?= view('public/partials/form_styles', ['cliente' => $cliente]) ?>
</head>
<body>
    <main class="wrap">
        <header class="top">
            <?php if (! empty($cliente['logo'])): ?><img class="logo" src="<?= base_url($cliente['logo']) ?>" alt=""><?php endif; ?>
            <div>
                <h1>Censo de mascotas</h1>
                <p><?= esc($cliente['nombre_tercero']) ?> · <?= esc(($inmueble['torre_nombre'] ?? '') ? $inmueble['torre_nombre'] . ' - ' . $inmueble['identificador'] : $inmueble['identificador']) ?></p>
            </div>
        </header>

        <form method="post" action="<?= base_url('q/' . $token . '/submit') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="inmueble_id" value="<?= esc($inmueble['id']) ?>">
            <input type="hidden" name="autorizacion_datos" value="1">

            <h2>Responsable</h2>
            <div class="grid">
                <div>
                    <label for="responsable_nombre">Nombre completo</label>
                    <input id="responsable_nombre" name="responsable_nombre" required autocomplete="name">
                </div>
                <div>
                    <label for="responsable_documento">Documento</label>
                    <input id="responsable_documento" name="responsable_documento" required inputmode="numeric">
                </div>
                <div>
                    <label for="responsable_telefono">Telefono</label>
                    <input id="responsable_telefono" name="responsable_telefono" inputmode="tel">
                </div>
                <div>
                    <label for="responsable_correo">Correo</label>
                    <input type="email" id="responsable_correo" name="responsable_correo">
                </div>
                <div class="full">
                    <label for="firmante_nombre">Nombre de quien firma</label>
                    <input id="firmante_nombre" name="firmante_nombre" required>
                </div>
            </div>

            <h2>Mascotas</h2>
            <div id="mascotas" class="repeat">
                <?= view('public/partials/pet_row', ['tiposMascota' => $catalogos['tiposMascota'], 'required' => true]) ?>
            </div>
            <div class="actions"><button class="btn btn-muted" type="button" data-add="mascotas">Agregar mascota</button></div>

            <h2>Firma</h2>
            <canvas id="signaturePad" class="signature"></canvas>
            <input type="hidden" id="firma_imagen" name="firma_imagen" required>
            <div class="actions"><button class="btn btn-muted" type="button" id="clearSignature">Limpiar firma</button></div>

            <button class="btn btn-primary submit" type="submit">Enviar censo de mascotas</button>
        </form>
    </main>

    <?= view('public/partials/repeater_script') ?>
    <?= view('public/partials/signature_script') ?>
</body>
</html>
