<?php
$bool = static fn ($v) => $v === null || $v === '' ? '—' : ((int) $v === 1 ? 'Si' : 'No');
$val  = static fn ($v) => ($v === null || $v === '') ? '—' : $v;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    body { margin: 0; color: #222; font-size: 11px; }
    .header { border-bottom: 3px solid <?= esc($color) ?>; padding-bottom: 10px; margin-bottom: 14px; }
    .header td { vertical-align: middle; }
    .logo { height: 56px; }
    .title { font-size: 18px; font-weight: bold; color: <?= esc($color) ?>; }
    .subtitle { font-size: 11px; color: #666; }
    .cliente-name { font-size: 13px; font-weight: bold; }
    h2 { font-size: 12px; color: #fff; background: <?= esc($color) ?>; padding: 5px 8px; margin: 14px 0 6px; border-radius: 3px; }
    table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.data th, table.data td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; font-size: 10px; }
    table.data th { background: #f3f4f6; font-weight: bold; }
    .kv { width: 100%; border-collapse: collapse; }
    .kv td { padding: 3px 6px; font-size: 10.5px; vertical-align: top; }
    .kv td.k { width: 38%; color: #555; font-weight: bold; }
    .firma-box { border: 1px solid #ddd; border-radius: 4px; padding: 8px; width: 260px; }
    .firma-img { height: 70px; }
    .foot { margin-top: 18px; font-size: 8.5px; color: #999; border-top: 1px solid #eee; padding-top: 6px; }
    .empty { color: #999; font-style: italic; }
</style>
</head>
<body>

<table class="header" width="100%">
    <tr>
        <td width="70">
            <?php if ($logo): ?><img src="<?= $logo ?>" class="logo" alt=""><?php endif; ?>
        </td>
        <td>
            <div class="cliente-name"><?= esc($cliente['nombre_tercero']) ?></div>
            <div class="subtitle">NIT/Doc: <?= esc($val($cliente['documento'] ?? null)) ?></div>
        </td>
        <td align="right">
            <div class="title">Censo Poblacional</div>
            <div class="subtitle">Generado: <?= esc(date('Y-m-d H:i')) ?></div>
        </td>
    </tr>
</table>

<h2>Inmueble</h2>
<table class="kv">
    <tr>
        <td class="k">Tipo</td><td><?= esc(ucfirst((string) ($inmueble['tipo'] ?? '—'))) ?></td>
        <td class="k">Torre / Bloque</td><td><?= esc($val($inmueble['torre_nombre'] ?? null)) ?></td>
    </tr>
    <tr>
        <td class="k">Identificador</td><td><?= esc($val($inmueble['identificador'] ?? null)) ?></td>
        <td class="k">Piso</td><td><?= esc($val($inmueble['piso'] ?? null)) ?></td>
    </tr>
</table>

<h2>Datos del hogar</h2>
<table class="kv">
    <tr>
        <td class="k">Vive en la copropiedad</td><td><?= esc($bool($censo['vive_en_copropiedad'])) ?></td>
        <td class="k">Tiene/aspira parqueadero</td><td><?= esc($bool($censo['tiene_parqueadero'])) ?></td>
    </tr>
    <tr>
        <td class="k">Direccion de notificacion</td><td><?= esc($val($censo['direccion_notificacion'])) ?></td>
        <td class="k">Quien vive</td><td><?= esc($val($censo['quien_vive'])) ?></td>
    </tr>
    <tr>
        <td class="k">Administrado por</td><td><?= esc($val($censo['administrado_por'])) ?></td>
        <td class="k">Correo de contacto</td><td><?= esc($val($censo['correo_contacto'])) ?></td>
    </tr>
    <tr>
        <td class="k">Inmobiliaria</td><td><?= esc($val($censo['inmobiliaria_nombre'])) ?></td>
        <td class="k">Tel / Correo inmob.</td><td><?= esc($val($censo['inmobiliaria_telefono'])) ?> / <?= esc($val($censo['inmobiliaria_correo'])) ?></td>
    </tr>
</table>

<h2>Propietario(s)</h2>
<?php if ($propietarios): ?>
<table class="data">
    <tr><th>Nombre</th><th>Documento</th><th>Telefono</th><th>Correo</th></tr>
    <?php foreach ($propietarios as $p): ?>
    <tr><td><?= esc($val($p['nombre'])) ?></td><td><?= esc($val($p['documento'])) ?></td><td><?= esc($val($p['telefono'])) ?></td><td><?= esc($val($p['correo'])) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">Sin registros.</div><?php endif; ?>

<h2>Arrendatario(s)</h2>
<?php if ($arrendatarios): ?>
<table class="data">
    <tr><th>Nombre</th><th>Documento</th><th>Telefono</th><th>Correo</th></tr>
    <?php foreach ($arrendatarios as $a): ?>
    <tr><td><?= esc($val($a['nombre'])) ?></td><td><?= esc($val($a['documento'])) ?></td><td><?= esc($val($a['telefono'])) ?></td><td><?= esc($val($a['correo'])) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">Sin registros.</div><?php endif; ?>

<h2>Residentes</h2>
<?php if ($residentes): ?>
<table class="data">
    <tr><th>Nombre</th><th>Documento</th><th>Parentesco</th><th>Edad</th></tr>
    <?php foreach ($residentes as $r): ?>
    <tr><td><?= esc($val($r['nombre'])) ?></td><td><?= esc($val($r['documento'])) ?></td><td><?= esc($val($r['parentesco'])) ?></td><td><?= esc($val($r['edad'])) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">Sin registros.</div><?php endif; ?>

<h2>Vehiculos</h2>
<?php if ($vehiculos): ?>
<table class="data">
    <tr><th>Tipo</th><th>Marca</th><th>Linea</th><th>Modelo</th><th>Color</th><th>Placa</th></tr>
    <?php foreach ($vehiculos as $v): ?>
    <tr><td><?= esc($val($v['tipo_vehiculo'])) ?></td><td><?= esc($val($v['marca'])) ?></td><td><?= esc($val($v['linea'])) ?></td><td><?= esc($val($v['modelo'])) ?></td><td><?= esc($val($v['color'])) ?></td><td><?= esc($val($v['placa'])) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">Sin registros.</div><?php endif; ?>

<h2>Telefonos de contacto</h2>
<?php if ($telefonos): ?>
<table class="data">
    <tr><th>#</th><th>Numero</th></tr>
    <?php foreach ($telefonos as $t): ?>
    <tr><td><?= esc($val($t['orden'])) ?></td><td><?= esc($val($t['numero'])) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">Sin registros.</div><?php endif; ?>

<h2>Condicion de discapacidad</h2>
<div><?= esc($val($censo['discapacidad_descripcion'])) ?></div>

<h2>Observaciones</h2>
<div><?= esc($val($censo['observaciones'])) ?></div>

<h2>Firma</h2>
<table width="100%"><tr>
    <td>
        <div class="firma-box">
            <?php if ($firma): ?><img src="<?= $firma ?>" class="firma-img" alt=""><?php endif; ?>
            <div style="border-top:1px solid #999; margin-top:4px; padding-top:3px; font-size:10px;">
                <?= esc($val($censo['firmante_nombre'])) ?>
            </div>
        </div>
    </td>
    <td valign="bottom" align="right" style="font-size:9px; color:#666;">
        Autorizacion Habeas Data (Ley 1581/2012): <?= esc($bool($censo['autorizacion_datos'])) ?><br>
        Fecha autorizacion: <?= esc($val($censo['fecha_autorizacion'])) ?>
    </td>
</tr></table>

<div class="foot">
    Documento generado automaticamente por Censo PWA — Cliente: <?= esc($cliente['nombre_tercero']) ?> ·
    Registro #<?= esc($censo['id']) ?> · IP: <?= esc($val($censo['ip'])) ?> · Fecha respuesta: <?= esc($val($censo['created_at'])) ?>
</div>

</body>
</html>
