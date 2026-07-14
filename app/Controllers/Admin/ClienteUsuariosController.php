<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClienteModel;
use App\Models\RolModel;
use App\Models\UsuarioModel;

class ClienteUsuariosController extends BaseController
{
    private const CLIENTE_ROLES = ['cliente', 'consejo', 'comite'];

    public function index(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return view('admin/clientes/usuarios/index', [
            'cliente'  => $cliente,
            'usuarios' => $this->usersQuery($clienteId)->orderBy('u.nombre', 'ASC')->get()->getResultArray(),
        ]);
    }

    public function new(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        return view('admin/clientes/usuarios/form', [
            'cliente' => $cliente,
            'usuario' => $this->emptyUsuario(),
            'roles'   => $this->clienteRoles(),
            'action'  => base_url('admin/clientes/' . $clienteId . '/usuarios'),
            'title'   => 'Nuevo usuario',
            'isEdit'  => false,
        ]);
    }

    public function create(int $clienteId)
    {
        $cliente = $this->findCliente($clienteId);
        if (! $cliente) {
            return redirect()->to('/admin/clientes')->with('error', 'Cliente no encontrado.');
        }

        $data     = $this->usuarioData($clienteId);
        $password = (string) $this->request->getPost('password');

        if (! $this->validateUsuario($data, $password)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        (new UsuarioModel())->insert($data);

        return redirect()->to('/admin/clientes/' . $clienteId . '/usuarios')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(int $clienteId, int $usuarioId)
    {
        $cliente = $this->findCliente($clienteId);
        $usuario = $this->findUsuario($clienteId, $usuarioId);

        if (! $cliente || ! $usuario) {
            return redirect()->to('/admin/clientes')->with('error', 'Usuario no encontrado.');
        }

        return view('admin/clientes/usuarios/form', [
            'cliente' => $cliente,
            'usuario' => $usuario,
            'roles'   => $this->clienteRoles(),
            'action'  => base_url('admin/clientes/' . $clienteId . '/usuarios/' . $usuarioId),
            'title'   => 'Editar usuario',
            'isEdit'  => true,
        ]);
    }

    public function update(int $clienteId, int $usuarioId)
    {
        $cliente = $this->findCliente($clienteId);
        $usuario = $this->findUsuario($clienteId, $usuarioId);

        if (! $cliente || ! $usuario) {
            return redirect()->to('/admin/clientes')->with('error', 'Usuario no encontrado.');
        }

        $data     = $this->usuarioData($clienteId);
        $password = (string) $this->request->getPost('password');

        if (! $this->validateUsuario($data, $password, $usuarioId, true)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        (new UsuarioModel())->update($usuarioId, $data);

        return redirect()->to('/admin/clientes/' . $clienteId . '/usuarios')->with('success', 'Usuario actualizado correctamente.');
    }

    public function delete(int $clienteId, int $usuarioId)
    {
        $usuario = $this->findUsuario($clienteId, $usuarioId);
        if (! $usuario) {
            return redirect()->back()->with('error', 'Usuario no encontrado.');
        }

        if ((int) $usuario['id'] === (int) session()->get('user_id')) {
            return redirect()->back()->with('error', 'No puedes archivar tu propio usuario desde esta pantalla.');
        }

        (new UsuarioModel())->delete($usuarioId);

        return redirect()->back()->with('success', 'Usuario archivado correctamente.');
    }

    private function usuarioData(int $clienteId): array
    {
        return [
            'cliente_id' => $clienteId,
            'rol_id'     => (int) $this->request->getPost('rol_id'),
            'nombre'     => trim((string) $this->request->getPost('nombre')),
            'email'      => strtolower(trim((string) $this->request->getPost('email'))),
            'telefono'   => $this->nullablePost('telefono'),
            'activo'     => $this->request->getPost('activo') ? 1 : 0,
        ];
    }

    private function validateUsuario(array $data, string $password, ?int $usuarioId = null, bool $isEdit = false): bool
    {
        if (! $this->roleBelongsToClienteRoles((int) $data['rol_id'])) {
            $this->validator = service('validation');
            $this->validator->setError('rol_id', 'Selecciona un rol valido para el cliente.');

            return false;
        }

        $emailRule = 'required|valid_email|max_length[191]|is_unique[usuarios.email';
        if ($usuarioId !== null) {
            $emailRule .= ',id,' . $usuarioId;
        }
        $emailRule .= ']';

        $rules = [
            'cliente_id' => 'required|is_natural_no_zero',
            'rol_id'     => 'required|is_natural_no_zero',
            'nombre'     => 'required|max_length[191]',
            'email'      => $emailRule,
            'telefono'   => 'permit_empty|max_length[50]',
            'activo'     => 'required|in_list[0,1]',
        ];

        if (! $this->validateData($data, $rules)) {
            return false;
        }

        if (! $isEdit && strlen($password) < 12) {
            $this->validator->setError('password', 'La contrasena debe tener minimo 12 caracteres.');

            return false;
        }

        if ($isEdit && $password !== '' && strlen($password) < 12) {
            $this->validator->setError('password', 'La nueva contrasena debe tener minimo 12 caracteres.');

            return false;
        }

        return true;
    }

    private function usersQuery(int $clienteId)
    {
        return db_connect()->table('usuarios u')
            ->select('u.id, u.nombre, u.email, u.telefono, u.activo, u.last_login, u.created_at, r.nombre AS rol')
            ->join('roles r', 'r.id = u.rol_id')
            ->where('u.cliente_id', $clienteId)
            ->where('u.deleted_at', null)
            ->whereIn('r.nombre', self::CLIENTE_ROLES);
    }

    private function findUsuario(int $clienteId, int $usuarioId): ?array
    {
        return (new UsuarioModel())
            ->where('cliente_id', $clienteId)
            ->where('id', $usuarioId)
            ->first();
    }

    private function findCliente(int $clienteId): ?array
    {
        return (new ClienteModel())->find($clienteId);
    }

    private function clienteRoles(): array
    {
        return (new RolModel())
            ->whereIn('nombre', self::CLIENTE_ROLES)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    private function roleBelongsToClienteRoles(int $roleId): bool
    {
        $role = (new RolModel())->find($roleId);

        return $role && in_array($role['nombre'], self::CLIENTE_ROLES, true);
    }

    private function nullablePost(string $key): ?string
    {
        $value = trim((string) $this->request->getPost($key));

        return $value === '' ? null : $value;
    }

    private function emptyUsuario(): array
    {
        return [
            'rol_id'   => '',
            'nombre'   => '',
            'email'    => '',
            'telefono' => '',
            'activo'   => 1,
        ];
    }
}
