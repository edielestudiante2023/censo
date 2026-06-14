<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Censo poblacional</title>
    <?= view('public/partials/form_styles', ['cliente' => $cliente]) ?>
</head>
<body>
    <main class="wrap">
        <header class="top">
            <?php if (! empty($cliente['logo'])): ?><img class="logo" src="<?= base_url($cliente['logo']) ?>" alt=""><?php endif; ?>
            <div>
                <h1>Censo poblacional</h1>
                <p><?= esc($cliente['nombre_tercero']) ?> · <?= esc(($inmueble['torre_nombre'] ?? '') ? $inmueble['torre_nombre'] . ' - ' . $inmueble['identificador'] : $inmueble['identificador']) ?></p>
            </div>
        </header>

        <form method="post" action="<?= base_url('q/' . $token . '/submit') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="inmueble_id" value="<?= esc($inmueble['id']) ?>">
            <input type="hidden" name="autorizacion_datos" value="1">

            <h2>Datos generales</h2>
            <div class="grid">
                <div>
                    <label for="firmante_nombre">Nombre de quien diligencia</label>
                    <input id="firmante_nombre" name="firmante_nombre" required>
                </div>
                <div>
                    <label for="correo_contacto">Correo de contacto</label>
                    <input type="email" id="correo_contacto" name="correo_contacto">
                </div>
                <div>
                    <label for="vive_en_copropiedad">Vive en la copropiedad</label>
                    <select id="vive_en_copropiedad" name="vive_en_copropiedad">
                        <option value="">Selecciona...</option>
                        <option value="1">Si</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div>
                    <label for="tiene_parqueadero">Tiene parqueadero</label>
                    <select id="tiene_parqueadero" name="tiene_parqueadero">
                        <option value="">Selecciona...</option>
                        <option value="1">Si</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div>
                    <label for="tiene_mascotas">Tiene mascotas</label>
                    <select id="tiene_mascotas" name="tiene_mascotas">
                        <option value="">Selecciona...</option>
                        <option value="1">Si</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div>
                    <label for="quien_vive">Quien vive</label>
                    <input id="quien_vive" name="quien_vive" placeholder="Propietario, arrendatario, familiar...">
                </div>
                <div>
                    <label for="administrado_por">Administrado por</label>
                    <select id="administrado_por" name="administrado_por">
                        <option value="">No aplica</option>
                        <option value="persona_natural">Persona natural</option>
                        <option value="inmobiliaria">Inmobiliaria</option>
                    </select>
                </div>
                <div class="full">
                    <label for="direccion_notificacion">Direccion de notificacion</label>
                    <input id="direccion_notificacion" name="direccion_notificacion">
                </div>
                <div>
                    <label for="inmobiliaria_nombre">Inmobiliaria</label>
                    <input id="inmobiliaria_nombre" name="inmobiliaria_nombre">
                </div>
                <div>
                    <label for="inmobiliaria_telefono">Telefono inmobiliaria</label>
                    <input id="inmobiliaria_telefono" name="inmobiliaria_telefono">
                </div>
                <div class="full">
                    <label for="inmobiliaria_correo">Correo inmobiliaria</label>
                    <input type="email" id="inmobiliaria_correo" name="inmobiliaria_correo">
                </div>
                <div class="full">
                    <label for="discapacidad_descripcion">Discapacidad o condiciones relevantes</label>
                    <textarea id="discapacidad_descripcion" name="discapacidad_descripcion"></textarea>
                </div>
                <div class="full">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones"></textarea>
                </div>
            </div>

            <h2>Propietarios</h2>
            <div id="propietarios" class="repeat"><?= view('public/partials/person_row', ['prefix' => 'propietarios']) ?></div>
            <div class="actions"><button class="btn btn-muted" type="button" data-add="propietarios">Agregar propietario</button></div>

            <h2>Arrendatarios</h2>
            <div id="arrendatarios" class="repeat"><?= view('public/partials/person_row', ['prefix' => 'arrendatarios']) ?></div>
            <div class="actions"><button class="btn btn-muted" type="button" data-add="arrendatarios">Agregar arrendatario</button></div>

            <h2>Residentes</h2>
            <div id="residentes" class="repeat"><?= view('public/partials/resident_row', ['parentescos' => $catalogos['parentescos']]) ?></div>
            <div class="actions"><button class="btn btn-muted" type="button" data-add="residentes">Agregar residente</button></div>

            <h2>Mascotas</h2>
            <div id="mascotas" class="repeat">
                <?= view('public/partials/pet_row', ['tiposMascota' => $catalogos['tiposMascota'], 'required' => false]) ?>
            </div>
            <div class="actions"><button class="btn btn-muted" type="button" data-add="mascotas">Agregar mascota</button></div>

            <h2>Vehiculos</h2>
            <div id="vehiculos" class="repeat"><?= view('public/partials/vehicle_row', ['tiposVehiculo' => $catalogos['tiposVehiculo']]) ?></div>
            <div class="actions"><button class="btn btn-muted" type="button" data-add="vehiculos">Agregar vehiculo</button></div>

            <h2>Telefonos</h2>
            <div id="telefonos" class="repeat"><input name="telefonos_numero[]" placeholder="Telefono"></div>
            <div class="actions"><button class="btn btn-muted" type="button" data-add="telefonos">Agregar telefono</button></div>

            <h2>Firma</h2>
            <canvas id="signaturePad" class="signature"></canvas>
            <input type="hidden" id="firma_imagen" name="firma_imagen" required>
            <div class="actions"><button class="btn btn-muted" type="button" id="clearSignature">Limpiar firma</button></div>

            <button class="btn btn-primary submit" type="submit">Enviar censo poblacional</button>
        </form>
    </main>

    <?= view('public/partials/repeater_script') ?>
    <?= view('public/partials/signature_script') ?>
</body>
</html>
