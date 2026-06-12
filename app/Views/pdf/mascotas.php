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
    .kv { width: 100%; border-collapse: collapse; }
    .kv td { padding: 3px 6px; font-size: 10.5px; vertical-align: top; }
    .kv td.k { width: 38%; color: #555; font-weight: bold; }
    .pet { border: 1px solid #ddd; border-radius: 4px; padding: 8px; margin-bottom: 8px; }
    .pet-title { font-weight: bold; color: <?= esc($color) ?>; margin-bottom: 4px; }
    .pet table { width: 100%; border-collapse: collapse; }
    .pet td { padding: 2px 6px; font-size: 10px; vertical-align: top; }
    .pet td.k { width: 22%; color: #555; font-weight: bold; }
    .fotos img { height: 70px; margin-right: 8px; border: 1px solid #ccc; }
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
            <div class="title">Censo de Mascotas</div>
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

<h2>Responsable</h2>
<table class="kv">
    <tr>
        <td class="k">Nombre</td><td><?= esc($val($censo['responsable_nombre'])) ?></td>
        <td class="k">Documento</td><td><?= esc($val($censo['responsable_documento'])) ?></td>
    </tr>
    <tr>
        <td class="k">Telefono</td><td><?= esc($val($censo['responsable_telefono'])) ?></td>
        <td class="k">Correo</td><td><?= esc($val($censo['responsable_correo'])) ?></td>
    </tr>
</table>

<h2>Mascotas</h2>
<?php if ($mascotas): ?>
    <?php foreach ($mascotas as $i => $m): ?>
    <div class="pet">
        <div class="pet-title">Mascota <?= $i + 1 ?>: <?= esc($val($m['nombre'])) ?></div>
        <table>
            <tr>
                <td class="k">Tipo</td><td><?= esc($val($m['tipo_mascota'])) ?></td>
                <td class="k">Edad</td><td><?= esc($val($m['edad'])) ?></td>
            </tr>
            <tr>
                <td class="k">Raza / Color</td><td><?= esc($val($m['raza_color'])) ?></td>
                <td class="k">Vacunacion / Esteril.</td><td><?= esc($bool($m['vacunacion_completa'])) ?> / <?= esc($bool($m['esterilizada'])) ?></td>
            </tr>
        </table>
        <?php if ($m['foto_data'] || $m['foto_carne_data'] || $m['foto_poliza_data']): ?>
        <div class="fotos" style="margin-top:6px;">
            <?php if ($m['foto_data']): ?><img src="<?= $m['foto_data'] ?>" alt="foto"><?php endif; ?>
            <?php if ($m['foto_carne_data']): ?><img src="<?= $m['foto_carne_data'] ?>" alt="carne"><?php endif; ?>
            <?php if ($m['foto_poliza_data']): ?><img src="<?= $m['foto_poliza_data'] ?>" alt="poliza"><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php else: ?><div class="empty">Sin mascotas registradas.</div><?php endif; ?>

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
