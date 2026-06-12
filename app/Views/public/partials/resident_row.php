<div class="row grid">
    <div>
        <label>Nombre completo</label>
        <input name="residentes_nombre[]" autocomplete="name">
    </div>
    <div>
        <label>Documento</label>
        <input name="residentes_documento[]" inputmode="numeric">
    </div>
    <div>
        <label>Parentesco</label>
        <select name="residentes_parentesco_id[]">
            <option value="">Selecciona...</option>
            <?php foreach ($parentescos as $parentesco): ?>
                <option value="<?= esc($parentesco['id']) ?>"><?= esc($parentesco['nombre']) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div>
        <label>Edad</label>
        <input name="residentes_edad[]" type="number" min="0" max="120" inputmode="numeric">
    </div>
</div>
