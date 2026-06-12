<div class="row grid">
    <div>
        <label>Tipo</label>
        <select name="vehiculos_tipo_vehiculo_id[]">
            <option value="">Selecciona...</option>
            <?php foreach ($tiposVehiculo as $tipo): ?>
                <option value="<?= esc($tipo['id']) ?>"><?= esc($tipo['nombre']) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div>
        <label>Placa</label>
        <input name="vehiculos_placa[]" style="text-transform: uppercase;">
    </div>
    <div>
        <label>Marca</label>
        <input name="vehiculos_marca[]">
    </div>
    <div>
        <label>Linea</label>
        <input name="vehiculos_linea[]">
    </div>
    <div>
        <label>Modelo</label>
        <input name="vehiculos_modelo[]" inputmode="numeric">
    </div>
    <div>
        <label>Color</label>
        <input name="vehiculos_color[]">
    </div>
</div>
