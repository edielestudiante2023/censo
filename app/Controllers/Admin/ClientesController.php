<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\HabeasData;
use App\Models\ClienteModel;

class ClientesController extends BaseController
{
    public function index()
    {
        $clienteModel = new ClienteModel();
        $q            = trim((string) $this->request->getGet('q'));

        if ($q !== '') {
            $clienteModel
                ->groupStart()
                ->like('nombre_tercero', $q)
                ->orLike('documento', $q)
                ->orLike('email', $q)
                ->orLike('ciudad', $q)
                ->orLike('slug', $q)
                ->groupEnd();
        }

        return view('admin/clientes/index', [
            'clientes' => $clienteModel->orderBy('nombre_tercero', 'ASC')->paginate(15),
            'pager'    => $clienteModel->pager,
            'q'        => $q,
        ]);
    }

    public function new()
    {
        return view('admin/clientes/form', [
            'cliente'            => $this->emptyCliente(),
            'action'             => base_url('admin/clientes'),
            'method'             => 'post',
            'title'              => 'Nuevo cliente',
            'habeasDataStandard' => HabeasData::standard(),
        ]);
    }

    public function create()
    {
        $data = $this->clienteData();

        if (! $this->validateCliente($data)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $logo = $this->storeLogo();
        if ($logo === false) {
            return redirect()->back()->withInput()->with('errors', ['logo' => 'El logo debe ser PNG, JPG o WebP y pesar maximo 2 MB.']);
        }

        if ($logo !== null) {
            $data['logo'] = $logo;
        }

        (new ClienteModel())->insert($data);

        return redirect()->to('/admin/clientes')->with('success', 'Cliente creado correctamente.');
    }

    public function show(int $id)
    {
        $cliente = $this->findCliente($id);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return view('admin/clientes/show', ['cliente' => $cliente]);
    }

    public function edit(int $id)
    {
        $cliente = $this->findCliente($id);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $cliente['texto_habeas_data'] = HabeasData::customOrStandard($cliente['texto_habeas_data'] ?? null);

        return view('admin/clientes/form', [
            'cliente'            => $cliente,
            'action'             => base_url('admin/clientes/' . $id),
            'method'             => 'post',
            'title'              => 'Editar cliente',
            'habeasDataStandard' => HabeasData::standard(),
        ]);
    }

    public function update(int $id)
    {
        $cliente = $this->findCliente($id);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $data = $this->clienteData($id);

        if (! $this->validateCliente($data, $id)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $logo = $this->storeLogo();
        if ($logo === false) {
            return redirect()->back()->withInput()->with('errors', ['logo' => 'El logo debe ser PNG, JPG o WebP y pesar maximo 2 MB.']);
        }

        if ($logo !== null) {
            $data['logo'] = $logo;
            $this->deletePublicFile($cliente['logo'] ?? null);
        }

        (new ClienteModel())->update($id, $data);

        return redirect()->to('/admin/clientes/' . $id)->with('success', 'Cliente actualizado correctamente.');
    }

    public function removeLogo(int $id)
    {
        $cliente = $this->findCliente($id);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $this->deletePublicFile($cliente['logo'] ?? null);
        (new ClienteModel())->update($id, ['logo' => null]);

        return redirect()->back()->with('success', 'Logo eliminado correctamente.');
    }

    public function delete(int $id)
    {
        $cliente = $this->findCliente($id);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        (new ClienteModel())->delete($id);

        return redirect()->to('/admin/clientes')->with('success', 'Cliente archivado correctamente.');
    }

    private function clienteData(?int $id = null): array
    {
        $nombre = trim((string) $this->request->getPost('nombre_tercero'));
        $slug   = trim((string) $this->request->getPost('slug'));
        $slug   = $slug !== '' ? url_title($slug, '-', true) : url_title($nombre, '-', true);

        return [
            'nombre_tercero'    => $nombre,
            'tipo_documento'    => trim((string) $this->request->getPost('tipo_documento')) ?: 'NIT',
            'documento'         => $this->nullablePost('documento'),
            'direccion'         => $this->nullablePost('direccion'),
            'ciudad'            => $this->nullablePost('ciudad'),
            'telefono'          => $this->nullablePost('telefono'),
            'persona_contacto'  => $this->nullablePost('persona_contacto'),
            'email'             => $this->nullablePost('email'),
            'color_primario'    => $this->nullablePost('color_primario') ?: '#1f2937',
            'color_secundario'  => $this->nullablePost('color_secundario') ?: '#0f766e',
            'tipo_conjunto'     => $this->request->getPost('tipo_conjunto') ?: 'apartamentos',
            'slug'              => $this->uniqueSlug($slug !== '' ? $slug : 'cliente', $id),
            'texto_habeas_data' => HabeasData::customOrStandard($this->nullablePost('texto_habeas_data')),
            'activo'            => $this->request->getPost('activo') ? 1 : 0,
        ];
    }

    private function validateCliente(array $data, ?int $id = null): bool
    {
        $slugRule = 'required|max_length[191]|alpha_dash|is_unique[clientes.slug';
        if ($id !== null) {
            $slugRule .= ',id,' . $id;
        }
        $slugRule .= ']';

        return $this->validateData($data, [
            'nombre_tercero'    => 'required|max_length[191]',
            'tipo_documento'    => 'required|max_length[20]',
            'documento'         => 'permit_empty|max_length[30]',
            'direccion'         => 'permit_empty|max_length[191]',
            'ciudad'            => 'permit_empty|max_length[100]',
            'telefono'          => 'permit_empty|max_length[50]',
            'persona_contacto'  => 'permit_empty|max_length[191]',
            'email'             => 'permit_empty|valid_email|max_length[191]',
            'color_primario'    => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'color_secundario'  => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'tipo_conjunto'     => 'required|in_list[casas,apartamentos,mixto]',
            'slug'              => $slugRule,
            'texto_habeas_data' => 'permit_empty',
            'activo'            => 'required|in_list[0,1]',
        ]);
    }

    private function storeLogo(): string|false|null
    {
        $logo = $this->request->getFile('logo');
        if (! $logo || $logo->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $logo->isValid() || $logo->getSize() > 2 * 1024 * 1024) {
            return false;
        }

        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/webp'];
        if (! in_array($logo->getMimeType(), $allowedMimeTypes, true)) {
            return false;
        }

        $targetDir = FCPATH . 'uploads/clientes/logos';
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $name = $logo->getRandomName();
        $logo->move($targetDir, $name);

        return 'uploads/clientes/logos/' . $name;
    }

    private function findCliente(int $id): ?array
    {
        return (new ClienteModel())->find($id);
    }

    private function nullablePost(string $key): ?string
    {
        $value = trim((string) $this->request->getPost($key));

        return $value === '' ? null : $value;
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $clienteModel = new ClienteModel();
        $slug         = $base;
        $suffix       = 2;

        while (true) {
            $query = $clienteModel->withDeleted()->where('slug', $slug);
            if ($ignoreId !== null) {
                $query->where('id !=', $ignoreId);
            }

            if (! $query->first()) {
                return $slug;
            }

            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }

    private function emptyCliente(): array
    {
        return [
            'nombre_tercero'    => '',
            'tipo_documento'    => 'NIT',
            'documento'         => '',
            'direccion'         => '',
            'ciudad'            => '',
            'telefono'          => '',
            'persona_contacto'  => '',
            'email'             => '',
            'logo'              => null,
            'color_primario'    => '#1f2937',
            'color_secundario'  => '#0f766e',
            'tipo_conjunto'     => 'apartamentos',
            'slug'              => '',
            'texto_habeas_data' => HabeasData::standard(),
            'activo'            => 1,
        ];
    }

    private function deletePublicFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        $path = realpath(FCPATH . $relativePath);
        if ($path && str_starts_with($path, realpath(FCPATH . 'uploads') ?: FCPATH)) {
            @unlink($path);
        }
    }
}
