<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Usuarios de prueba (admin/cliente/consejo/comite) sobre el cliente demo.
 * Uso: php spark demo:users        -> crea/actualiza los usuarios (pass Demo2026*)
 *      php spark demo:users clean  -> borra los usuarios de prueba
 */
class DemoUsers extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'demo:users';
    protected $description = 'Crea/borra usuarios de prueba por rol sobre el cliente demo.';

    private array $users = [
        ['email' => 'admin@demo.test',   'rol' => 'admin',   'nombre' => 'Admin Demo',   'tenant' => false],
        ['email' => 'cliente@demo.test', 'rol' => 'cliente', 'nombre' => 'Cliente Demo', 'tenant' => true],
        ['email' => 'consejo@demo.test', 'rol' => 'consejo', 'nombre' => 'Consejo Demo', 'tenant' => true],
        ['email' => 'comite@demo.test',  'rol' => 'comite',  'nombre' => 'Comite Demo',  'tenant' => true],
    ];

    public function run(array $params)
    {
        $db = db_connect();

        if (($params[0] ?? '') === 'clean') {
            $db->table('usuarios')->whereIn('email', array_column($this->users, 'email'))->delete();
            CLI::write('Usuarios de prueba eliminados.', 'yellow');

            return;
        }

        $cliente = $db->table('clientes')->where('slug', 'demo-muestra')->get()->getRowArray();
        if (! $cliente) {
            CLI::error('Falta el cliente demo. Ejecuta primero: php spark demo:seed');

            return;
        }
        $clienteId = (int) $cliente['id'];
        $roles     = array_column($db->table('roles')->select('id, nombre')->get()->getResultArray(), 'id', 'nombre');
        $now       = date('Y-m-d H:i:s');
        $hash      = password_hash('Demo2026*', PASSWORD_DEFAULT);

        foreach ($this->users as $u) {
            $data = [
                'cliente_id'    => $u['tenant'] ? $clienteId : null,
                'rol_id'        => $roles[$u['rol']] ?? null,
                'nombre'        => $u['nombre'],
                'email'         => $u['email'],
                'password_hash' => $hash,
                'activo'        => 1,
                'updated_at'    => $now,
            ];
            $existing = $db->table('usuarios')->where('email', $u['email'])->get()->getRowArray();
            if ($existing) {
                $db->table('usuarios')->where('id', $existing['id'])->update($data);
            } else {
                $data['created_at'] = $now;
                $db->table('usuarios')->insert($data);
            }
            CLI::write('  ' . str_pad($u['rol'], 10) . ' ' . $u['email'] . ' / Demo2026*', 'green');
        }
    }
}
