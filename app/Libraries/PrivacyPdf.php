<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;

final class PrivacyPdf
{
    public function contents(string $path): string
    {
        $blob = file_get_contents(WRITEPATH . $path);
        if ($blob === false) {
            throw new \RuntimeException('No fue posible leer el PDF probatorio.');
        }
        return (new PrivacyVault())->decryptFile($blob, 'pdf|' . str_replace('\\', '/', $path));
    }

    public function consent(array $consent, array $cliente, array $purposes, ?array $document): string
    {
        $signature = ! empty($consent['firma_imagen']) ? '<h2>Firma electronica</h2><img src="' . esc($consent['firma_imagen'], 'attr') . '" style="max-width:320px;max-height:120px">' : '';
        $html = '<article class="legal-document"><header><p><strong>' . esc($cliente['nombre_tercero']) .
            '</strong></p><h1>Constancia de decision sobre tratamiento de datos</h1><p>Constancia DP-C-' . esc($consent['id']) .
            '</p></header>' . ($consent['instancia_html'] ?? '') . $signature . '<h2>Sellado de evidencia</h2><p>Version documental: ' . esc($consent['documento_version'] ?? $document['version'] ?? '-') .
            '<br>Hash del documento maestro: ' . esc($consent['documento_hash'] ?? $document['hash_sha256'] ?? '-') .
            '<br>Hash SHA-256 de la instancia: ' . esc($consent['instancia_hash'] ?? '-') . '<br>Hash de evidencia: ' . esc($consent['evidencia_hash']) .
            '<br>Fecha y hora: ' . esc($consent['otorgado_at']) . ' (' . esc($consent['zona_horaria'] ?? 'America/Bogota') . ')' .
            '<br>Canal: ' . esc($consent['canal'] ?? 'portal_web') . '<br>Tipo de evidencia: ' . esc($consent['tipo_evidencia'] ?? 'firma_electronica') .
            '<br>Verificacion de identidad: ' . esc($consent['verificacion_identidad'] ?? '-') . '</p></article>';
        $path = $this->render((int) $cliente['id'], 'consentimiento-' . $consent['id'], $html);
        return $path;
    }

    public function document(array $document, array $cliente): string
    {
        $meta = '<div class="meta"><strong>Codigo:</strong> ' . esc($document['codigo']) .
            ' &nbsp; <strong>Version:</strong> ' . esc($document['version']) .
            ' &nbsp; <strong>Estado:</strong> ' . esc($document['estado']) . '</div>';
        $path = $this->render((int) $cliente['id'], 'documento-' . $document['id'], $meta . $document['contenido_html']);
        db_connect()->table('dp_documentos')->where('id', $document['id'])->update([
            'pdf_ruta' => $path, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $path;
    }

    public function request(array $request, array $cliente, array $actions): string
    {
        $rows = '';
        foreach ($actions as $action) {
            $rows .= '<tr><td>' . esc($action['base_nombre'] ?? '') . '</td><td>' . esc($action['accion']) .
                '</td><td>' . esc($action['estado']) . '</td><td>' . nl2br(esc($action['detalle'] ?? '')) . '</td></tr>';
        }
        $html = '<article class="legal-document"><header><p><strong>' . esc($cliente['nombre_tercero']) .
            '</strong></p><h1>Respuesta a solicitud de proteccion de datos</h1><p>Radicado ' . esc($request['radicado']) .
            '</p></header><p>Señor(a) <strong>' . esc($request['titular_nombre']) . '</strong>:</p><p>' .
            nl2br(esc($request['respuesta_texto'] ?? '')) . '</p><h2>Acciones por base de datos</h2><table><thead><tr><th>Base</th><th>Accion</th><th>Estado</th><th>Detalle</th></tr></thead><tbody>' .
            $rows . '</tbody></table>' . (! empty($request['fundamento_conservacion']) ? '<h2>Informacion conservada o bloqueada</h2><p>' . nl2br(esc($request['fundamento_conservacion'])) . '</p>' : '') .
            '<p>Esta respuesta se conserva como evidencia del tramite y no autoriza usos adicionales de la informacion.</p></article>';
        return $this->render((int) $cliente['id'], 'respuesta-' . $request['radicado'], $html);
    }

    public function confidentiality(array $agreement, array $client): string
    {
        $signature = '<h2>Firma electronica</h2><img src="' . esc($agreement['firma_imagen'], 'attr') . '" style="max-width:320px;max-height:120px">';
        $html = $agreement['instancia_html'] . $signature . '<h2>Evidencia</h2><p>Fecha: ' . esc($agreement['aceptado_at']) .
            '<br>Hash de instancia: ' . esc($agreement['instancia_hash']) . '<br>Hash de firma: ' . esc(hash('sha256', $agreement['firma_imagen'])) .
            '<br>Canal: OTP al correo y firma electronica.</p>';
        return $this->render((int) $client['id'], 'compromiso-confidencialidad-' . $agreement['id'], $html);
    }

    public function processorAgreement(array $agreement, array $client): string
    {
        $html = $agreement['instancia_html'] . '<h2>Firmas electronicas</h2><p>Responsable: ' . esc($agreement['responsable_firmante']) .
            ' · ' . esc($agreement['responsable_firmado_at']) . '</p><img src="' . esc($agreement['responsable_firma'], 'attr') . '" style="max-width:280px;max-height:100px">' .
            '<p>Encargado: ' . esc($agreement['encargado_firmado_at']) . '</p><img src="' . esc($agreement['encargado_firma'], 'attr') . '" style="max-width:280px;max-height:100px">' .
            '<p>Hash instancia: ' . esc($agreement['instancia_hash']) . '<br>Hash firma Responsable: ' . esc($agreement['responsable_firma_hash']) .
            '<br>Hash firma Encargado: ' . esc(hash('sha256', $agreement['encargado_firma'])) . '</p>';
        return $this->render((int) $client['id'], 'acuerdo-encargado-' . $agreement['id'], $html);
    }

    private function render(int $clienteId, string $name, string $content): string
    {
        $css = '<style>@page{margin:26mm 22mm}body{font-family:DejaVu Sans,sans-serif;color:#18212f;font-size:10.5pt;line-height:1.55}h1{font-size:19pt;margin:5px 0 12px}h2{font-size:12.5pt;margin:18px 0 6px;border-bottom:1px solid #d8dee8;padding-bottom:4px}header{border-bottom:3px solid #1f2937;margin-bottom:18px}.meta{font-size:8.5pt;background:#f3f4f6;padding:8px;margin-bottom:14px}table{width:100%;border-collapse:collapse;font-size:8.5pt}th,td{border:1px solid #cfd6df;padding:6px;vertical-align:top}th{background:#eef1f5;text-align:left}footer{margin-top:24px;border-top:1px solid #d8dee8;font-size:8.5pt}</style>';
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml('<!doctype html><html lang="es"><head><meta charset="UTF-8">' . $css . '</head><body>' . $content . '</body></html>', 'UTF-8');
        $dompdf->setPaper('letter');
        $dompdf->render();

        $dir = 'uploads/proteccion-datos/cliente-' . $clienteId . '/' . date('Ym');
        if (! is_dir(WRITEPATH . $dir)) {
            mkdir(WRITEPATH . $dir, 0775, true);
        }
        $path = $dir . '/' . url_title($name, '-', true) . '-' . bin2hex(random_bytes(5)) . '.pdf.enc';
        $plain = $dompdf->output();
        $encrypted = (new PrivacyVault())->encryptFile($plain, 'pdf|' . $path);
        if (file_put_contents(WRITEPATH . $path, $encrypted, LOCK_EX) === false) {
            throw new \RuntimeException('No fue posible almacenar el PDF probatorio cifrado.');
        }
        return $path;
    }
}
