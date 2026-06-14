<?php
$bool = static fn ($v) => $v === null || $v === '' ? '—' : ((int) $v === 1 ? 'Si' : 'No');
$val  = static fn ($v) => ($v === null || $v === '') ? '—' : $v;
$sx   = static fn ($v) => $v === 'M' ? 'Masculino' : ($v === 'F' ? 'Femenino' : ($v === 'Otro' ? 'Otro' : '—'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 34px 38px; }
    body { margin: 0; color: #1f2430; font-size: 10.5px; }
    .header { width: 100%; border-bottom: 2.5px solid <?= esc($color) ?>; padding-bottom: 12px; margin-bottom: 16px; }
    .header td { vertical-align: middle; }
    .logo { height: 54px; }
    .cliente-name { font-size: 14px; font-weight: bold; color: #111827; }
    .subtitle { font-size: 10px; color: #6b7280; }
    .doc-title { font-size: 19px; font-weight: bold; color: <?= esc($color) ?>; }
    .doc-meta { font-size: 9.5px; color: #6b7280; }
    h2 { font-size: 11px; color: #fff; background: <?= esc($color) ?>; padding: 6px 10px; margin: 16px 0 7px; border-radius: 4px; letter-spacing: .3px; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th, table.data td { border: 1px solid #e3e6ea; padding: 5px 7px; text-align: left; font-size: 9.5px; }
    table.data th { background: #f3f4f6; font-weight: bold; color: #374151; }
    table.data tr:nth-child(even) td { background: #fafbfc; }
    .kv { width: 100%; border-collapse: collapse; }
    .kv td { padding: 4px 7px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #f0f1f3; }
    .kv td.k { width: 24%; color: #6b7280; font-weight: bold; }
    .firma-box { border: 1px solid #e3e6ea; border-radius: 6px; padding: 10px; width: 270px; }
    .firma-img { height: 66px; }
    .firma-name { border-top: 1px solid #9ca3af; margin-top: 6px; padding-top: 4px; font-size: 10px; font-weight: bold; }
    .foot { margin-top: 20px; font-size: 8px; color: #9ca3af; border-top: 1px solid #eef0f2; padding-top: 7px; line-height: 1.5; }
    .empty { color: #9ca3af; font-style: italic; font-size: 9.5px; padding: 2px 0; }
    .pet-card { border: 1px solid #e3e6ea; border-radius: 6px; padding: 8px; margin-bottom: 9px; page-break-inside: avoid; }
    .pet-title { font-size: 10.5px; font-weight: bold; margin-bottom: 5px; color: #111827; }
    .photos { width: 100%; border-collapse: collapse; margin-top: 7px; }
    .photos td { width: 33.333%; text-align: center; vertical-align: top; padding: 3px; border: 0; }
    .photo { max-width: 145px; max-height: 110px; border: 1px solid #e5e7eb; border-radius: 4px; }
    .photo-cap { font-size: 8px; color: #6b7280; margin-top: 2px; text-transform: uppercase; font-weight: bold; }
</style>
</head>
<body>

<table class="header">
    <tr>
        <td width="64"><?php if ($logo): ?><img src="<?= $logo ?>" class="logo" alt=""><?php endif; ?></td>
        <td>
            <div class="cliente-name"><?= esc($cliente['nombre_tercero']) ?></div>
            <div class="subtitle">NIT/Doc: <?= esc($val($cliente['documento'] ?? null)) ?></div>
        </td>
        <td align="right">
            <div class="doc-title">Censo Poblacional</div>
            <div class="doc-meta">Generado: <?= esc(date('Y-m-d H:i')) ?> · Registro #<?= esc($censo['id']) ?></div>
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
        <td class="k">Tiene mascotas</td><td><?= esc($bool($censo['tiene_mascotas'] ?? null)) ?></td>
        <td class="k"></td><td></td>
    </tr>
    <tr>
        <td class="k">Direccion notificacion</td><td><?= esc($val($censo['direccion_notificacion'])) ?></td>
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
    <tr><th>Nombre</th><th>Documento</th><th>Sexo</th><th>Parentesco</th><th>Edad</th></tr>
    <?php foreach ($residentes as $r): ?>
    <tr><td><?= esc($val($r['nombre'])) ?></td><td><?= esc($val($r['documento'])) ?></td><td><?= esc($sx($r['sexo'] ?? null)) ?></td><td><?= esc($val($r['parentesco'])) ?></td><td><?= esc($val($r['edad'])) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">Sin registros.</div><?php endif; ?>

<h2>Mascotas</h2>
<?php if ($mascotas): ?>
    <?php foreach ($mascotas as $i => $m): ?>
        <div class="pet-card">
            <div class="pet-title">Mascota <?= esc($i + 1) ?>: <?= esc($val($m['nombre'])) ?></div>
            <table class="kv">
                <tr>
                    <td class="k">Tipo</td><td><?= esc($val($m['tipo_mascota'])) ?></td>
                    <td class="k">Edad</td><td><?= esc($val($m['edad'])) ?></td>
                </tr>
                <tr>
                    <td class="k">Raza / Color</td><td><?= esc($val($m['raza_color'])) ?></td>
                    <td class="k">Vacunacion / Esteril.</td><td><?= esc($bool($m['vacunacion_completa'])) ?> / <?= esc($bool($m['esterilizada'])) ?></td>
                </tr>
            </table>
            <table class="photos">
                <tr>
                    <td><?php if ($m['foto_data']): ?><img src="<?= $m['foto_data'] ?>" class="photo" alt=""><?php endif; ?><div class="photo-cap">Foto de la mascota</div></td>
                    <td><?php if ($m['foto_carne_data']): ?><img src="<?= $m['foto_carne_data'] ?>" class="photo" alt=""><?php endif; ?><div class="photo-cap">Carne de vacunas</div></td>
                    <td><?php if ($m['foto_poliza_data']): ?><img src="<?= $m['foto_poliza_data'] ?>" class="photo" alt=""><?php endif; ?><div class="photo-cap">Poliza</div></td>
                </tr>
            </table>
        </div>
    <?php endforeach; ?>
<?php else: ?><div class="empty">Sin mascotas registradas.</div><?php endif; ?>

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
    <tr><th width="40">#</th><th>Numero</th></tr>
    <?php foreach ($telefonos as $t): ?>
    <tr><td><?= esc($val($t['orden'])) ?></td><td><?= esc($val($t['numero'])) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">Sin registros.</div><?php endif; ?>

<h2>Condicion de discapacidad</h2>
<div style="font-size:10px;"><?= esc($val($censo['discapacidad_descripcion'])) ?></div>

<h2>Observaciones</h2>
<div style="font-size:10px;"><?= esc($val($censo['observaciones'])) ?></div>

<h2>Firma del diligenciamiento</h2>
<table width="100%"><tr>
    <td>
        <div class="firma-box">
            <?php if ($firma): ?><img src="<?= $firma ?>" class="firma-img" alt=""><?php endif; ?>
            <div class="firma-name"><?= esc($val($censo['firmante_nombre'])) ?></div>
        </div>
    </td>
    <td valign="bottom" align="right" style="font-size:9px; color:#6b7280;">
        Autorizacion Habeas Data (Ley 1581/2012): <strong><?= esc($bool($censo['autorizacion_datos'])) ?></strong><br>
        Fecha autorizacion: <?= esc($val($censo['fecha_autorizacion'])) ?>
    </td>
</tr></table>

<div class="foot">
    Documento generado automaticamente por Censo APP — Cliente: <?= esc($cliente['nombre_tercero']) ?> ·
    IP: <?= esc($val($censo['ip'])) ?> · Fecha respuesta: <?= esc($val($censo['created_at'])) ?>
    <br>Desarrollado por Enterprisesst · empowered by Cycloid Talent SAS
</div>

</body>
</html>
