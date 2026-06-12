<?php if (empty($respuestas)): ?>
    <div class="empty">Aun no hay respuestas registradas.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Torre</th>
                <th>Inmueble</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($respuestas as $respuesta): ?>
                <tr>
                    <td data-label="Torre"><?= esc($respuesta['torre_nombre'] ?? 'N/A') ?></td>
                    <td data-label="Inmueble"><strong><?= esc($respuesta['identificador']) ?></strong></td>
                    <td data-label="Fecha"><?= esc($respuesta['created_at'] ?? $respuesta['fecha_autorizacion'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
