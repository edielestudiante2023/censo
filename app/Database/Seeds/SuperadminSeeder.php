<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SuperadminSeeder extends Seeder
{
    public function run()
    {
        $email    = env('superadmin.email');
        $nombre   = env('superadmin.nombre') ?: 'Superadmin';
        $password = env('superadmin.password');

        if (empty($email) || empty($password)) {
            echo "  [SuperadminSeeder] Omitido: define superadmin.email y superadmin.password en .env\n";
            return;
        }

        $rol = $this->db->table('roles')->where('nombre', 'superadmin')->get()->getRow();
        if (! $rol) {
            echo "  [SuperadminSeeder] Falta el rol 'superadmin' (ejecuta RolesSeeder primero)\n";
            return;
        }

        $exists = $this->db->table('usuarios')->where('email', $email)->get()->getRow();
        if ($exists) {
            echo "  [SuperadminSeeder] Ya existe el usuario: {$email}\n";
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('usuarios')->insert([
            'cliente_id'    => null,
            'rol_id'        => $rol->id,
            'nombre'        => $nombre,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'activo'        => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        echo "  [SuperadminSeeder] Superadmin creado: {$email}\n";
    }
}
