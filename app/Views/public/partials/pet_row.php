<?php
$index = (int) ($index ?? 0);
$required = ! empty($required);
?>
<div class="row grid" data-pet-row>
    <div>
        <label>Nombre</label>
        <input name="mascota_nombre[]" <?= $required ? 'required' : '' ?>>
    </div>
    <div>
        <label>Tipo</label>
        <select name="tipo_mascota_id[]">
            <option value="">Selecciona...</option>
            <?php foreach ($tiposMascota as $tipo): ?>
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
        <select name="vacunacion_completa[<?= esc($index) ?>]" data-indexed-name="vacunacion_completa">
            <option value="">Selecciona...</option>
            <option value="1">Si</option>
            <option value="0">No</option>
        </select>
    </div>
    <div>
        <label>Esterilizada</label>
        <select name="esterilizada[<?= esc($index) ?>]" data-indexed-name="esterilizada">
            <option value="">Selecciona...</option>
            <option value="1">Si</option>
            <option value="0">No</option>
        </select>
    </div>
    <div>
        <label>Foto mascota</label>
        <input type="file" name="foto_<?= esc($index) ?>" data-file-prefix="foto" accept="image/png,image/jpeg,image/webp">
    </div>
    <div>
        <label>Carne vacunas</label>
        <input type="file" name="foto_carne_<?= esc($index) ?>" data-file-prefix="foto_carne" accept="image/png,image/jpeg,image/webp,application/pdf">
    </div>
    <div class="full">
        <label>Poliza</label>
        <input type="file" name="foto_poliza_<?= esc($index) ?>" data-file-prefix="foto_poliza" accept="image/png,image/jpeg,image/webp,application/pdf">
    </div>
</div>
