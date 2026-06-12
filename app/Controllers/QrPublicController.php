<?php

namespace App\Controllers;

use App\Models\CensoArrendatarioModel;
use App\Models\CensoMascotaModel;
use App\Models\CensoPoblacionalModel;
use App\Models\CensoPropietarioModel;
use App\Models\CensoResidenteModel;
use App\Models\CensoTelefonoModel;
use App\Models\CensoVehiculoModel;
use App\Models\ClienteModel;
use App\Models\InmuebleModel;
use App\Models\MascotaModel;
use App\Models\ParentescoModel;
use App\Models\QrCodeModel;
use App\Models\TipoMascotaModel;
use App\Models\TipoVehiculoModel;

class QrPublicController extends BaseController
{
    public function resolve(string $token)
    {
        $context = $this->context($token);
        if (! $context) {
            return view('public/qr_not_found');
        }

        return view('public/qr_select', $context + [
            'inmuebles' => $this->inmuebles((int) $context['cliente']['id']),
            'habeasData' => $this->habeasData($context['cliente']),
        ]);
    }

    public function form(string $token)
    {
        $context = $this->context($token);
        if (! $context) {
            return view('public/qr_not_found');
        }

        $inmueble = $this->findInmueble((int) $context['cliente']['id'], (int) $this->request->getPost('inmueble_id'));
        if (! $inmueble) {
            return redirect()->to('/q/' . $token)->with('error', 'Selecciona un inmueble valido.');
        }

        if ($this->request->getPost('autorizacion_datos') !== '1') {
            return redirect()->to('/q/' . $token)->with('error', 'Debes aceptar la autorizacion de tratamiento de datos.');
        }

        return view('public/form_' . $context['qr']['tipo_instrumento'], $context + [
            'inmueble' => $inmueble,
            'catalogos' => $this->catalogos(),
        ]);
    }

    public function submit(string $token)
    {
        $context = $this->context($token);
        if (! $context) {
            return view('public/qr_not_found');
        }

        $clienteId = (int) $context['cliente']['id'];
        $qr        = $context['qr'];
        $inmueble  = $this->findInmueble($clienteId, (int) $this->request->getPost('inmueble_id'));

        if (! $inmueble) {
            return redirect()->to('/q/' . $token)->with('error', 'Selecciona un inmueble valido.');
        }

        if ($this->request->getPost('autorizacion_datos') !== '1') {
            return redirect()->to('/q/' . $token)->with('error', 'Debes aceptar la autorizacion de tratamiento de datos.');
        }

        $errors = $this->validateSubmission((string) $qr['tipo_instrumento']);
        if ($errors !== []) {
            return redirect()->to('/q/' . $token)->with('error', implode(' ', $errors));
        }

        $signature = $this->saveSignature((string) $this->request->getPost('firma_imagen'), $clienteId);
        if ($signature === null) {
            return redirect()->to('/q/' . $token)->with('error', 'La firma es obligatoria.');
        }

        $db = db_connect();
        $db->transStart();

        if ($qr['tipo_instrumento'] === 'poblacional') {
            $this->savePoblacional($clienteId, (int) $qr['id'], (int) $inmueble['id'], $signature);
        } else {
            $this->saveMascotas($clienteId, (int) $qr['id'], (int) $inmueble['id'], $signature);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('/q/' . $token)->with('error', 'No fue posible guardar el formulario. Intenta nuevamente.');
        }

        return view('public/thanks', $context + ['inmueble' => $inmueble]);
    }

    private function savePoblacional(int $clienteId, int $qrId, int $inmuebleId, string $signature): void
    {
        $model = new CensoPoblacionalModel();
        $model->insert([
            'cliente_id' => $clienteId,
            'qr_id' => $qrId,
            'inmueble_id' => $inmuebleId,
            'autorizacion_datos' => 1,
            'fecha_autorizacion' => date('Y-m-d H:i:s'),
            'vive_en_copropiedad' => $this->nullableBoolPost('vive_en_copropiedad'),
            'direccion_notificacion' => $this->nullablePost('direccion_notificacion'),
            'quien_vive' => $this->nullablePost('quien_vive'),
            'administrado_por' => $this->nullablePost('administrado_por'),
            'inmobiliaria_nombre' => $this->nullablePost('inmobiliaria_nombre'),
            'inmobiliaria_telefono' => $this->nullablePost('inmobiliaria_telefono'),
            'inmobiliaria_correo' => $this->nullablePost('inmobiliaria_correo'),
            'correo_contacto' => $this->nullablePost('correo_contacto'),
            'discapacidad_descripcion' => $this->nullablePost('discapacidad_descripcion'),
            'tiene_parqueadero' => $this->nullableBoolPost('tiene_parqueadero'),
            'observaciones' => $this->nullablePost('observaciones'),
            'firmante_nombre' => $this->nullablePost('firmante_nombre'),
            'firma_imagen' => $signature,
            'ip' => $this->request->getIPAddress(),
            'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
        ]);

        $censoId = (int) $model->getInsertID();
        $this->savePeopleRows(new CensoPropietarioModel(), $censoId, 'propietarios');
        $this->savePeopleRows(new CensoArrendatarioModel(), $censoId, 'arrendatarios');
        $this->saveResidents($censoId);
        $this->saveVehicles($censoId);
        $this->savePhones($censoId);
    }

    private function validateSubmission(string $type): array
    {
        $errors = [];

        if ($this->nullablePost('firmante_nombre') === null) {
            $errors[] = 'El nombre de quien firma es obligatorio.';
        }

        if ($type !== 'mascotas') {
            return $errors;
        }

        if ($this->nullablePost('responsable_nombre') === null) {
            $errors[] = 'El nombre del responsable es obligatorio.';
        }

        if ($this->nullablePost('responsable_documento') === null) {
            $errors[] = 'El documento del responsable es obligatorio.';
        }

        if (! $this->hasPetRow()) {
            $errors[] = 'Registra al menos una mascota.';
        }

        foreach ($this->request->getFiles() as $key => $file) {
            if (! preg_match('/^foto(_carne|_poliza)?_\d+$/', (string) $key)) {
                continue;
            }

            if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (! $file->isValid() || $file->getSize() > 4 * 1024 * 1024) {
                $errors[] = 'Cada archivo debe ser valido y pesar maximo 4 MB.';
                break;
            }

            if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
                $errors[] = 'Los archivos deben ser imagen o PDF.';
                break;
            }
        }

        return $errors;
    }

    private function hasPetRow(): bool
    {
        $names = (array) $this->request->getPost('mascota_nombre');
        $types = (array) $this->request->getPost('tipo_mascota_id');

        foreach ($names as $index => $name) {
            if (trim((string) $name) !== '' || ! empty($types[$index])) {
                return true;
            }
        }

        return false;
    }

    private function saveMascotas(int $clienteId, int $qrId, int $inmuebleId, string $signature): void
    {
        $model = new CensoMascotaModel();
        $model->insert([
            'cliente_id' => $clienteId,
            'qr_id' => $qrId,
            'inmueble_id' => $inmuebleId,
            'autorizacion_datos' => 1,
            'fecha_autorizacion' => date('Y-m-d H:i:s'),
            'responsable_nombre' => $this->nullablePost('responsable_nombre'),
            'responsable_documento' => $this->nullablePost('responsable_documento'),
            'responsable_telefono' => $this->nullablePost('responsable_telefono'),
            'responsable_correo' => $this->nullablePost('responsable_correo'),
            'firmante_nombre' => $this->nullablePost('firmante_nombre'),
            'firma_imagen' => $signature,
            'ip' => $this->request->getIPAddress(),
            'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
        ]);

        $censoId = (int) $model->getInsertID();
        $names   = (array) $this->request->getPost('mascota_nombre');
        $types   = (array) $this->request->getPost('tipo_mascota_id');
        $ages    = (array) $this->request->getPost('mascota_edad');
        $raza    = (array) $this->request->getPost('raza_color');
        $vacunas = (array) $this->request->getPost('vacunacion_completa');
        $esteril = (array) $this->request->getPost('esterilizada');

        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '' && empty($types[$index]) && empty($raza[$index])) {
                continue;
            }

            (new MascotaModel())->insert([
                'censo_mascota_id' => $censoId,
                'nombre' => $name ?: null,
                'tipo_mascota_id' => ! empty($types[$index]) ? (int) $types[$index] : null,
                'edad' => $this->arrayValue($ages, $index),
                'raza_color' => $this->arrayValue($raza, $index),
                'vacunacion_completa' => isset($vacunas[$index]) ? (int) $vacunas[$index] : null,
                'esterilizada' => isset($esteril[$index]) ? (int) $esteril[$index] : null,
                'foto_ruta' => $this->saveUpload('foto_' . $index, $clienteId),
                'foto_carne_ruta' => $this->saveUpload('foto_carne_' . $index, $clienteId),
                'foto_poliza_ruta' => $this->saveUpload('foto_poliza_' . $index, $clienteId),
            ]);
        }
    }

    private function savePeopleRows($model, int $censoId, string $prefix): void
    {
        $names = (array) $this->request->getPost($prefix . '_nombre');
        $docs  = (array) $this->request->getPost($prefix . '_documento');
        $tels  = (array) $this->request->getPost($prefix . '_telefono');
        $mails = (array) $this->request->getPost($prefix . '_correo');

        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '' && empty($docs[$index])) {
                continue;
            }

            $model->insert([
                'censo_id' => $censoId,
                'nombre' => $name ?: null,
                'documento' => $this->arrayValue($docs, $index),
                'telefono' => $this->arrayValue($tels, $index),
                'correo' => $this->arrayValue($mails, $index),
            ]);
        }
    }

    private function saveResidents(int $censoId): void
    {
        $names = (array) $this->request->getPost('residentes_nombre');
        $docs  = (array) $this->request->getPost('residentes_documento');
        $pars  = (array) $this->request->getPost('residentes_parentesco_id');
        $ages  = (array) $this->request->getPost('residentes_edad');

        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '' && empty($docs[$index])) {
                continue;
            }

            (new CensoResidenteModel())->insert([
                'censo_id' => $censoId,
                'nombre' => $name ?: null,
                'documento' => $this->arrayValue($docs, $index),
                'parentesco_id' => ! empty($pars[$index]) ? (int) $pars[$index] : null,
                'edad' => ! empty($ages[$index]) ? (int) $ages[$index] : null,
            ]);
        }
    }

    private function saveVehicles(int $censoId): void
    {
        $types = (array) $this->request->getPost('vehiculos_tipo_vehiculo_id');
        $marca = (array) $this->request->getPost('vehiculos_marca');
        $linea = (array) $this->request->getPost('vehiculos_linea');
        $model = (array) $this->request->getPost('vehiculos_modelo');
        $color = (array) $this->request->getPost('vehiculos_color');
        $placa = (array) $this->request->getPost('vehiculos_placa');

        foreach ($types as $index => $type) {
            if (empty($type) && empty($placa[$index])) {
                continue;
            }

            (new CensoVehiculoModel())->insert([
                'censo_id' => $censoId,
                'tipo_vehiculo_id' => ! empty($type) ? (int) $type : null,
                'marca' => $this->arrayValue($marca, $index),
                'linea' => $this->arrayValue($linea, $index),
                'modelo' => $this->arrayValue($model, $index),
                'color' => $this->arrayValue($color, $index),
                'placa' => $this->arrayValue($placa, $index),
            ]);
        }
    }

    private function savePhones(int $censoId): void
    {
        foreach ((array) $this->request->getPost('telefonos_numero') as $index => $number) {
            $number = trim((string) $number);
            if ($number === '') {
                continue;
            }

            (new CensoTelefonoModel())->insert([
                'censo_id' => $censoId,
                'numero' => $number,
                'orden' => $index + 1,
            ]);
        }
    }

    private function context(string $token): ?array
    {
        $qr = (new QrCodeModel())->findByToken($token);
        if (! $qr) {
            return null;
        }

        $cliente = (new ClienteModel())->find((int) $qr['cliente_id']);
        if (! $cliente || (int) $cliente['activo'] !== 1) {
            return null;
        }

        return ['cliente' => $cliente, 'qr' => $qr, 'token' => $token];
    }

    private function inmuebles(int $clienteId): array
    {
        return db_connect()->table('inmuebles i')
            ->select('i.id, i.tipo, i.identificador, i.piso, t.nombre AS torre_nombre')
            ->join('torres t', 't.id = i.torre_id', 'left')
            ->where('i.cliente_id', $clienteId)
            ->where('i.deleted_at', null)
            ->orderBy('i.tipo', 'ASC')
            ->orderBy('t.nombre', 'ASC')
            ->orderBy('i.identificador', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function findInmueble(int $clienteId, int $inmuebleId): ?array
    {
        return (new InmuebleModel())
            ->where('cliente_id', $clienteId)
            ->where('id', $inmuebleId)
            ->first();
    }

    private function catalogos(): array
    {
        return [
            'parentescos' => (new ParentescoModel())->where('activo', 1)->orderBy('nombre', 'ASC')->findAll(),
            'tiposVehiculo' => (new TipoVehiculoModel())->where('activo', 1)->orderBy('nombre', 'ASC')->findAll(),
            'tiposMascota' => (new TipoMascotaModel())->where('activo', 1)->orderBy('nombre', 'ASC')->findAll(),
        ];
    }

    private function habeasData(array $cliente): string
    {
        return strtr((string) $cliente['texto_habeas_data'], [
            '{NOMBRE_CONJUNTO}' => (string) $cliente['nombre_tercero'],
            '{NIT}' => (string) ($cliente['documento'] ?? ''),
            '{CORREO_ADMIN}' => (string) ($cliente['email'] ?? ''),
        ]);
    }

    private function saveSignature(string $dataUri, int $clienteId): ?string
    {
        if (! preg_match('/^data:image\/png;base64,/', $dataUri)) {
            return null;
        }

        $binary = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);
        if ($binary === false || strlen($binary) < 200) {
            return null;
        }

        $dir = 'uploads/censos/' . date('Ymd') . '/cliente-' . $clienteId;
        $absoluteDir = WRITEPATH . $dir;
        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        $path = $dir . '/firma-' . bin2hex(random_bytes(12)) . '.png';
        file_put_contents(WRITEPATH . $path, $binary);

        return $path;
    }

    private function saveUpload(string $key, int $clienteId): ?string
    {
        $file = $this->request->getFile($key);
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid() || $file->getSize() > 4 * 1024 * 1024) {
            return null;
        }

        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
            return null;
        }

        $dir = 'uploads/censos/' . date('Ymd') . '/cliente-' . $clienteId;
        $absoluteDir = WRITEPATH . $dir;
        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        $name = $file->getRandomName();
        $file->move($absoluteDir, $name);

        return $dir . '/' . $name;
    }

    private function nullablePost(string $key): ?string
    {
        $value = trim((string) $this->request->getPost($key));

        return $value === '' ? null : $value;
    }

    private function nullableBoolPost(string $key): ?int
    {
        $value = $this->request->getPost($key);

        return $value === null || $value === '' ? null : (int) $value;
    }

    private function arrayValue(array $items, int|string $index): ?string
    {
        $value = trim((string) ($items[$index] ?? ''));

        return $value === '' ? null : $value;
    }
}
