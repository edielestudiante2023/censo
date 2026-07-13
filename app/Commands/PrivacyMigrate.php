<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Config as DatabaseConfig;
use CodeIgniter\Database\MigrationRunner;
use Config\Migrations;

final class PrivacyMigrate extends BaseCommand
{
    private const BASE_VERSION = '2026-07-11-000001';
    private const VERSION = '2026-07-13-000021';
    private const TABLES = [
        'dp_programas', 'dp_bases_datos', 'dp_finalidades', 'dp_terceros', 'dp_plantillas',
        'dp_documentos', 'dp_consentimientos', 'dp_exclusiones', 'dp_solicitudes',
        'dp_solicitud_bases', 'dp_solicitud_terceros', 'dp_notificaciones',
        'dp_notificacion_eventos', 'dp_ai_runs', 'dp_auditoria', 'dp_aviso_variantes',
        'dp_aviso_publicaciones', 'dp_consentimiento_eventos', 'dp_consentimiento_verificaciones',
        'dp_finalidad_datos_sensibles',
        'dp_solicitud_eventos', 'dp_calendario_festivos', 'dp_restauraciones_privacidad', 'dp_incidentes_privacidad',
        'dp_asignaciones_seguridad', 'dp_controles_seguridad', 'dp_usuario_privacidad', 'dp_incidente_eventos',
        'usuario_mfa_desafios',
        'dp_compromisos_confidencialidad', 'dp_compromiso_eventos',
        'dp_subencargados', 'dp_acuerdos_encargado', 'dp_acuerdo_encargado_eventos', 'dp_encargado_instrucciones', 'dp_encargado_certificaciones',
        'dp_cifrado_estado',
    ];

    protected $group = 'Database';
    protected $name = 'privacy:migrate';
    protected $description = 'Verifica la migracion local y aplica el modulo de datos personales en produccion con SSL.';
    protected $usage = 'privacy:migrate [credentials_file] production';
    protected $arguments = ['credentials_file' => 'Archivo con host, port, database, username y password.', 'production' => 'Confirmacion literal requerida.'];

    public function run(array $params)
    {
        $path = $params[0] ?? '';
        if (($params[1] ?? '') !== 'production' || $path === '' || ! is_file($path)) {
            CLI::error('Uso: php spark privacy:migrate <archivo-credenciales> production');
            return EXIT_ERROR;
        }

        CLI::write('1/3 Verificando migracion local...', 'yellow');
        $local = db_connect();
        if (! $this->migrationRecorded($local, self::VERSION) || ! $this->schemaHealthy($local)) {
            CLI::error('Local no esta al dia. Ejecuta primero php spark migrate.');
            return EXIT_ERROR;
        }
        CLI::write('Local OK: migracion y ' . count(self::TABLES) . ' tablas verificadas.', 'green');

        $credentials = $this->credentials($path);
        if (! str_ends_with($credentials['host'], '.ondigitalocean.com') || $credentials['database'] !== 'censo' || (int) $credentials['port'] !== 25060) {
            CLI::error('El destino no coincide con el host, base y puerto de produccion autorizados.');
            return EXIT_ERROR;
        }

        CLI::write('2/3 Conectando a produccion por SSL: ' . $credentials['host'] . ':' . $credentials['port'] . '/' . $credentials['database'], 'yellow');
        $remoteConfig = [
            'DSN' => '', 'hostname' => $credentials['host'], 'username' => $credentials['username'],
            'password' => $credentials['password'], 'database' => $credentials['database'],
            'DBDriver' => 'MySQLi', 'DBPrefix' => '', 'pConnect' => false, 'DBDebug' => true,
            'charset' => 'utf8mb4', 'DBCollat' => 'utf8mb4_general_ci', 'swapPre' => '',
            'encrypt' => ['ssl_verify' => false], 'compress' => false, 'strictOn' => false,
            'failover' => [], 'port' => (int) $credentials['port'], 'numberNative' => false, 'foundRows' => false,
        ];
        $remote = DatabaseConfig::connect($remoteConfig, false);
        $remote->initialize();
        if (! $remote->tableExists('migrations')) {
            CLI::error('Produccion no contiene la tabla de migraciones esperada. Operacion cancelada.');
            return EXIT_ERROR;
        }

        if ($this->migrationRecorded($remote, self::VERSION)) {
            if (! $this->schemaHealthy($remote)) {
                CLI::error('La migracion figura aplicada pero faltan tablas. Se requiere revision manual.');
                return EXIT_ERROR;
            }
            $this->writePrivacyCounts($remote);
            CLI::write('Produccion ya estaba al dia; no se realizaron cambios.', 'green');
            return EXIT_SUCCESS;
        }

        if (! $this->migrationRecorded($remote, self::BASE_VERSION)) {
            foreach (self::TABLES as $table) {
                if ($remote->tableExists($table)) {
                    CLI::error('Se encontro un esquema parcial (' . $table . ') sin historial. Operacion cancelada.');
                    return EXIT_ERROR;
                }
            }
        }

        CLI::write('3/3 Aplicando migraciones pendientes en produccion...', 'yellow');
        $runner = new MigrationRunner(config(Migrations::class), $remoteConfig);
        if (! $runner->latest()) {
            CLI::error('El ejecutor de migraciones reporto un fallo.');
            return EXIT_ERROR;
        }
        $remote->resetDataCache();
        if (! $this->migrationRecorded($remote, self::VERSION) || ! $this->schemaHealthy($remote)) {
            CLI::error('La verificacion posterior no fue satisfactoria.');
            return EXIT_ERROR;
        }
        CLI::write('Produccion OK: migracion registrada y ' . count(self::TABLES) . ' tablas verificadas.', 'green');
        $this->writePrivacyCounts($remote);
        return EXIT_SUCCESS;
    }

    private function credentials(string $path): array
    {
        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (preg_match('/^(host|port|database|username|password)\s*=\s*(.+)$/', trim($line), $match)) {
                $values[$match[1]] = trim($match[2]);
            }
        }
        foreach (['host', 'port', 'database', 'username', 'password'] as $required) {
            if (($values[$required] ?? '') === '') {
                throw new \RuntimeException('Falta ' . $required . ' en el archivo de credenciales.');
            }
        }
        return $values;
    }

    private function migrationRecorded($db, string $version): bool
    {
        return $db->table('migrations')->where('version', $version)->countAllResults() === 1;
    }

    private function tablesExist($db): bool
    {
        foreach (self::TABLES as $table) {
            if (! $db->tableExists($table)) {
                return false;
            }
        }
        return true;
    }

    private function schemaHealthy($db): bool
    {
        if (! $this->tablesExist($db) || ! $db->fieldExists('identidad_estado', 'dp_solicitudes')
            || ! $db->fieldExists('titular_documento_bidx', 'dp_solicitudes')
            || ! $db->fieldExists('titular_documento_bidx', 'dp_consentimientos')) {
            return false;
        }
        if ($db->DBDriver === 'MySQLi') {
            $row = $db->query(
                "SELECT COUNT(*) AS total FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = ? AND DELETE_RULE = 'CASCADE' AND LEFT(TABLE_NAME, 3) = 'dp_'",
                [$db->getDatabase()]
            )->getRowArray();
            return (int) ($row['total'] ?? 0) === 0;
        }
        return true;
    }

    private function writePrivacyCounts($db): void
    {
        $tables = ['dp_programas', 'dp_bases_datos', 'dp_solicitudes', 'dp_consentimientos', 'dp_auditoria'];
        $parts = [];
        foreach ($tables as $table) {
            $parts[] = $table . '=' . $db->table($table)->countAllResults();
        }
        CLI::write('Conteos remotos: ' . implode(', ', $parts) . '.', 'yellow');
    }
}
