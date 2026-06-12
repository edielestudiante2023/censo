<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['nombre' => 'superadmin', 'descripcion' => 'Acceso total (proveedor)'],
            ['nombre' => 'admin',      'descripcion' => 'Administrador del proveedor'],
            ['nombre' => 'cliente',    'descripcion' => 'Administrador del conjunto residencial'],
            ['nombre' => 'consejo',    'descripcion' => 'Consejo de administracion'],
            ['nombre' => 'comite',     'descripcion' => 'Comite de convivencia'],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($roles as $rol) {
            $exists = $this->db->table('roles')->where('nombre', $rol['nombre'])->get()->getRow();
            if (! $exists) {
                $rol['created_at'] = $now;
                $rol['updated_at'] = $now;
                $this->db->table('roles')->insert($rol);
            }
        }
    }
}
