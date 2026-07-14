<?php

namespace App\Commands;

use App\Libraries\ClientInstrumentAccess;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class DemoPreflight extends BaseCommand
{
    protected $group = 'Demo';
    protected $name = 'demo:preflight';
    protected $description = 'Verifica que el cliente demo de produccion este listo para una reunion.';

    public function run(array $params)
    {
        $slug = trim((string) ($params[0] ?? 'demo-muestra'));
        $db = db_connect();
        $client = $db->table('clientes')->where('slug', $slug)->where('deleted_at', null)->get()->getRowArray();
        if (! $client) {
            CLI::error('DEMO NO LISTO: no existe el cliente ' . $slug);
            return EXIT_ERROR;
        }
        $id = (int) $client['id'];
        $checks = [
            'instrumentos' => count(array_filter((new ClientInstrumentAccess())->enabledMap($id))) === 3,
            'unidades' => $db->table('inmuebles')->where('cliente_id', $id)->where('deleted_at', null)->countAllResults() >= 1,
            'programa' => $db->table('dp_programas')->where('cliente_id', $id)->where('estado', 'activo')->countAllResults() === 1,
            'bases' => $db->table('dp_bases_datos')->where('cliente_id', $id)->where('activo', 1)->countAllResults() >= 5,
            'documentos' => $db->table('dp_documentos')->where('cliente_id', $id)->where('estado', 'publicado')->countAllResults() >= 7,
            'decisiones' => $db->table('dp_consentimientos')->where('cliente_id', $id)->where('canal', 'demo_preparado')->countAllResults() >= 3,
            'usuario cliente con correo real' => $db->table('usuarios')->where('cliente_id', $id)
                ->where('email', 'sistemasdegestionpropiedadhori@gmail.com')->where('activo', 1)->countAllResults() === 1,
        ];
        foreach ($checks as $name => $ok) {
            CLI::write(($ok ? '[OK] ' : '[FALTA] ') . $name, $ok ? 'green' : 'red');
        }
        if (in_array(false, $checks, true)) {
            CLI::error('DEMO NO LISTO');
            return EXIT_ERROR;
        }
        CLI::write('DEMO LISTO: ' . $client['nombre_tercero'] . ' (id ' . $id . ')', 'green');
        return EXIT_SUCCESS;
    }
}
