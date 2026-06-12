<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CatalogosSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $catalogos = [
            'tipos_documento' => [
                'Cedula de ciudadania',
                'Cedula de extranjeria',
                'Tarjeta de identidad',
                'NIT',
                'Pasaporte',
                'Permiso por Proteccion Temporal',
            ],
            'parentescos' => [
                'Propietario(a)',
                'Esposo(a)',
                'Companero(a)',
                'Hijo(a)',
                'Padre',
                'Madre',
                'Hermano(a)',
                'Abuelo(a)',
                'Nieto(a)',
                'Tio(a)',
                'Sobrino(a)',
                'Otro familiar',
                'Empleado(a) del servicio',
                'Arrendatario(a)',
                'Otro',
            ],
            'tipos_vehiculo' => [
                'Automovil',
                'Camioneta',
                'Motocicleta',
                'Bicicleta',
                'Patineta electrica',
                'Otro',
            ],
            'tipos_mascota' => [
                'Perro',
                'Gato',
                'Ave',
                'Conejo',
                'Hamster',
                'Pez',
                'Tortuga',
                'Otro',
            ],
        ];

        foreach ($catalogos as $tabla => $items) {
            foreach ($items as $nombre) {
                $exists = $this->db->table($tabla)->where('nombre', $nombre)->get()->getRow();
                if (! $exists) {
                    $this->db->table($tabla)->insert([
                        'nombre'     => $nombre,
                        'activo'     => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
