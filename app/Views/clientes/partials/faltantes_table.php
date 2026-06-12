<?php if (empty($faltantes)): ?>
    <div class="empty">No hay faltantes en este instrumento.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Torre</th>
                <th>Inmueble</th>
                <th>Piso</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($faltantes as $inmueble): ?>
                <tr>
                    <td data-label="Tipo"><?= esc($inmueble['tipo']) ?></td>
                    <td data-label="Torre"><?= esc($inmueble['torre_nombre'] ?? 'N/A') ?></td>
                    <td data-label="Inmueble"><strong><?= esc($inmueble['identificador']) ?></strong></td>
                    <td data-label="Piso"><?= esc($inmueble['piso'] ?? 'N/A') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($faltantes) >= 200): ?>
        <p>Se muestran maximo 200 faltantes.</p>
    <?php endif; ?>
<?php endif; ?>
