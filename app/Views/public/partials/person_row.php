<div class="row grid">
    <div>
        <label>Nombre completo</label>
        <input name="<?= esc($prefix) ?>_nombre[]" autocomplete="name">
    </div>
    <div>
        <label>Documento</label>
        <input name="<?= esc($prefix) ?>_documento[]" inputmode="numeric">
    </div>
    <div>
        <label>Telefono</label>
        <input name="<?= esc($prefix) ?>_telefono[]" inputmode="tel">
    </div>
    <div>
        <label>Correo</label>
        <input type="email" name="<?= esc($prefix) ?>_correo[]">
    </div>
</div>
