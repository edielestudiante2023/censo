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
    .kv { width: 100%; border-collapse: collapse; }
    .kv td { padding: 4px 7px; font-size: 10px; vertical-align: top; border-bottom: 1px solid #f0f1f3; }
    .kv td.k { width: 24%; color: #6b7280; font-weight: bold; }
    .pet { border: 1px solid #e3e6ea; border-radius: 7px; padding: 11px; margin-bottom: 11px; }
    .pet-title { font-weight: bold; color: <?= esc($color) ?>; font-size: 11.5px; margin-bottom: 6px; }
    .pet-data { width: 100%; border-collapse: collapse; }
    .pet-data td { padding: 3px 7px; font-size: 10px; vertical-align: top; }
    .pet-data td.k { width: 16%; color: #6b7280; font-weight: bold; }
    .photos { width: 100%; border-collapse: collapse; margin-top: 9px; table-layout: fixed; }
    .photos td { width: 33.33%; text-align: center; vertical-align: top; padding: 4px; }
    .photo-frame { border: 1px solid #d8dce2; border-radius: 6px; background: #f9fafb; padding: 5px; height: 130px; }
    .photo-frame img { max-height: 118px; max-width: 100%; }
    .photo-cap { font-size: 8.5px; color: #6b7280; margin-top: 4px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
    .photo-empty { color: #c2c7cf; font-size: 9px; padding-top: 50px; }
    .firma-box { border: 1px solid #e3e6ea; border-radius: 6px; padding: 10px; width: 270px; }
    .firma-img { height: 66px; }
    .firma-name { border-top: 1px solid #9ca3af; margin-top: 6px; padding-top: 4px; font-size: 10px; font-weight: bold; }
    .foot { margin-top: 20px; font-size: 8px; color: #9ca3af; border-top: 1px solid #eef0f2; padding-top: 7px; line-height: 1.5; }
    .empty { color: #9ca3af; font-style: italic; font-size: 9.5px; }
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
            <div class="doc-title">Censo de Mascotas</div>
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
        <table class="pet-data">
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
                <td>
                    <div class="photo-frame"><?php if ($m['foto_data']): ?><img src="<?= $m['foto_data'] ?>" alt=""><?php else: ?><div class="photo-empty">Sin foto</div><?php endif; ?></div>
                    <div class="photo-cap">Foto de la mascota</div>
                </td>
                <td>
                    <div class="photo-frame"><?php if ($m['foto_carne_data']): ?><img src="<?= $m['foto_carne_data'] ?>" alt=""><?php else: ?><div class="photo-empty">No aplica</div><?php endif; ?></div>
                    <div class="photo-cap">Carne de vacunas</div>
                </td>
                <td>
                    <div class="photo-frame"><?php if ($m['foto_poliza_data']): ?><img src="<?= $m['foto_poliza_data'] ?>" alt=""><?php else: ?><div class="photo-empty">No aplica</div><?php endif; ?></div>
                    <div class="photo-cap">Poliza raza peligrosa</div>
                </td>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
<?php else: ?><div class="empty">Sin mascotas registradas.</div><?php endif; ?>

<h2>Firma de quien diligencia</h2>
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
