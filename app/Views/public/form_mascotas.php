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
            <h1>Censo de mascotas</h1>
            <p><?= esc($cliente['nombre_tercero']) ?> · <?= esc(($inmueble['torre_nombre'] ?? '') ? $inmueble['torre_nombre'] . ' - ' . $inmueble['identificador'] : $inmueble['identificador']) ?></p>
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
                <div class="row grid" data-pet-row>
                    <div>
                        <label>Nombre</label>
                        <input name="mascota_nombre[]" required>
                    </div>
                    <div>
                        <label>Tipo</label>
                        <select name="tipo_mascota_id[]">
                            <option value="">Selecciona...</option>
                            <?php foreach ($catalogos['tiposMascota'] as $tipo): ?>
                                <option value="<?= esc($tipo['id']) ?>"><?= esc($tipo['nombre']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div>
                        <label>Edad</label>
                        <input name="mascota_edad[]" placeholder="Ej. 3 anos">
                    </div>
                    <div>
                        <label>Raza / color</label>
                        <input name="raza_color[]">
                    </div>
                    <div>
                        <label>Vacunacion completa</label>
                        <select name="vacunacion_completa[0]" data-indexed-name="vacunacion_completa">
                            <option value="">Selecciona...</option>
                            <option value="1">Si</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div>
                        <label>Esterilizada</label>
                        <select name="esterilizada[0]" data-indexed-name="esterilizada">
                            <option value="">Selecciona...</option>
                            <option value="1">Si</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div>
                        <label>Foto mascota</label>
                        <input type="file" name="foto_0" data-file-prefix="foto" accept="image/png,image/jpeg,image/webp">
                    </div>
                    <div>
                        <label>Carne vacunas</label>
                        <input type="file" name="foto_carne_0" data-file-prefix="foto_carne" accept="image/png,image/jpeg,image/webp,application/pdf">
                    </div>
                    <div class="full">
                        <label>Poliza</label>
                        <input type="file" name="foto_poliza_0" data-file-prefix="foto_poliza" accept="image/png,image/jpeg,image/webp,application/pdf">
                    </div>
                </div>
            </div>
            <div class="actions"><button class="btn btn-muted" type="button" id="addPet">Agregar mascota</button></div>

            <h2>Firma</h2>
            <canvas id="signaturePad" class="signature"></canvas>
            <input type="hidden" id="firma_imagen" name="firma_imagen" required>
            <div class="actions"><button class="btn btn-muted" type="button" id="clearSignature">Limpiar firma</button></div>

            <button class="btn btn-primary submit" type="submit">Enviar censo de mascotas</button>
        </form>
    </main>

    <script>
    (function() {
        var list = document.getElementById('mascotas');
        var add = document.getElementById('addPet');

        function refresh(row, index) {
            row.querySelectorAll('[data-indexed-name]').forEach(function(field) {
                field.name = field.getAttribute('data-indexed-name') + '[' + index + ']';
            });
            row.querySelectorAll('[data-file-prefix]').forEach(function(field) {
                field.name = field.getAttribute('data-file-prefix') + '_' + index;
            });
        }

        add.addEventListener('click', function() {
            var clone = list.firstElementChild.cloneNode(true);
            clone.querySelectorAll('input, select, textarea').forEach(function(field) {
                field.value = '';
            });
            list.appendChild(clone);
            refresh(clone, list.children.length - 1);
        });
    })();
    </script>
    <?= view('public/partials/signature_script') ?>
</body>
</html>
