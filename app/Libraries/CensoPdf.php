<?php

namespace App\Libraries;

use App\Models\ClienteModel;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Genera el PDF de un censo (poblacional o mascotas) con branding del cliente,
 * lo guarda en writable/uploads y actualiza pdf_ruta del censo.
 */
class CensoPdf
{
    private const PDF_IMAGE_MAX_DIMENSION = 480;
    private const PDF_IMAGE_JPEG_QUALITY = 76;

    public function generate(string $instrumento, int $censoId): ?string
    {
        if ($instrumento === 'poblacional') {
            $data = $this->poblacionalData($censoId);
            $view = 'pdf/poblacional';
        } elseif ($instrumento === 'mascotas') {
            $data = $this->mascotasData($censoId);
            $view = 'pdf/mascotas';
        } else {
            return null;
        }

        if ($data === null) {
            return null;
        }

        $html = view($view, $data);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        $binary = $dompdf->output();

        $clienteId   = (int) $data['cliente']['id'];
        $dir         = 'uploads/censos/' . date('Ymd') . '/cliente-' . $clienteId;
        $absoluteDir = WRITEPATH . $dir;
        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        $path = $dir . '/pdf-' . $instrumento . '-' . $censoId . '-' . bin2hex(random_bytes(6)) . '.pdf';
        file_put_contents(WRITEPATH . $path, $binary);

        $table = $instrumento === 'poblacional' ? 'censos_poblacionales' : 'censos_mascotas';
        db_connect()->table($table)->where('id', $censoId)->update([
            'pdf_ruta'   => $path,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $path;
    }

    private function poblacionalData(int $censoId): ?array
    {
        $db    = db_connect();
        $censo = $db->table('censos_poblacionales')->where('id', $censoId)->where('deleted_at', null)->get()->getRowArray();
        if (! $censo) {
            return null;
        }

        $cliente  = (new ClienteModel())->find((int) $censo['cliente_id']);
        $inmueble = $this->inmueble((int) $censo['inmueble_id']);

        return [
            'cliente'      => $cliente,
            'censo'        => $censo,
            'inmueble'     => $inmueble,
            'propietarios' => $db->table('censo_propietarios')->where('censo_id', $censoId)->get()->getResultArray(),
            'arrendatarios' => $db->table('censo_arrendatarios')->where('censo_id', $censoId)->get()->getResultArray(),
            'residentes'   => $db->table('censo_residentes cr')
                ->select('cr.*, p.nombre AS parentesco')
                ->join('parentescos p', 'p.id = cr.parentesco_id', 'left')
                ->where('cr.censo_id', $censoId)->get()->getResultArray(),
            'vehiculos'    => $db->table('censo_vehiculos cv')
                ->select('cv.*, tv.nombre AS tipo_vehiculo')
                ->join('tipos_vehiculo tv', 'tv.id = cv.tipo_vehiculo_id', 'left')
                ->where('cv.censo_id', $censoId)->get()->getResultArray(),
            'telefonos'    => $db->table('censo_telefonos')->where('censo_id', $censoId)->orderBy('orden', 'ASC')->get()->getResultArray(),
            'logo'         => $this->imageDataUri(FCPATH . ($cliente['logo'] ?? '')),
            'firma'        => $this->imageDataUri(WRITEPATH . ($censo['firma_imagen'] ?? '')),
            'color'        => $this->color($cliente),
        ];
    }

    private function mascotasData(int $censoId): ?array
    {
        $db    = db_connect();
        $censo = $db->table('censos_mascotas')->where('id', $censoId)->where('deleted_at', null)->get()->getRowArray();
        if (! $censo) {
            return null;
        }

        $cliente  = (new ClienteModel())->find((int) $censo['cliente_id']);
        $inmueble = $this->inmueble((int) $censo['inmueble_id']);

        $mascotas = $db->table('mascotas m')
            ->select('m.*, tm.nombre AS tipo_mascota')
            ->join('tipos_mascota tm', 'tm.id = m.tipo_mascota_id', 'left')
            ->where('m.censo_mascota_id', $censoId)->get()->getResultArray();

        foreach ($mascotas as &$m) {
            $m['foto_data']        = $this->imageDataUri(WRITEPATH . ($m['foto_ruta'] ?? ''));
            $m['foto_carne_data']  = $this->imageDataUri(WRITEPATH . ($m['foto_carne_ruta'] ?? ''));
            $m['foto_poliza_data'] = $this->imageDataUri(WRITEPATH . ($m['foto_poliza_ruta'] ?? ''));
        }
        unset($m);

        return [
            'cliente'  => $cliente,
            'censo'    => $censo,
            'inmueble' => $inmueble,
            'mascotas' => $mascotas,
            'logo'     => $this->imageDataUri(FCPATH . ($cliente['logo'] ?? '')),
            'firma'    => $this->imageDataUri(WRITEPATH . ($censo['firma_imagen'] ?? '')),
            'color'    => $this->color($cliente),
        ];
    }

    private function inmueble(int $inmuebleId): ?array
    {
        return db_connect()->table('inmuebles i')
            ->select('i.id, i.tipo, i.identificador, i.piso, t.nombre AS torre_nombre')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('i.id', $inmuebleId)
            ->get()
            ->getRowArray();
    }

    private function color(?array $cliente): string
    {
        $color = trim((string) ($cliente['color_primario'] ?? ''));

        return preg_match('/^#?[0-9a-fA-F]{6}$/', $color) ? (str_starts_with($color, '#') ? $color : '#' . $color) : '#1f2937';
    }

    /** Lee una imagen y la devuelve como data URI JPEG reducido, o null. */
    private function imageDataUri(string $absolutePath): ?string
    {
        if ($absolutePath === '' || ! is_file($absolutePath)) {
            return null;
        }

        $raw = @file_get_contents($absolutePath);
        if ($raw === false) {
            return null;
        }

        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return null;
        }

        $width = imagesx($img);
        $height = imagesy($img);
        $scale = min(1, self::PDF_IMAGE_MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $white);
        imagecopyresampled($target, $img, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($target, null, self::PDF_IMAGE_JPEG_QUALITY);
        $jpeg = (string) ob_get_clean();
        imagedestroy($img);
        imagedestroy($target);

        return 'data:image/jpeg;base64,' . base64_encode($jpeg);
    }
}
