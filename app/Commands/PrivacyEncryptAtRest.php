<?php

namespace App\Commands;

use App\Libraries\PrivacyAudit;
use App\Libraries\PrivacyVault;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\PrivacyEncryption;

final class PrivacyEncryptAtRest extends BaseCommand
{
    protected $group = 'Privacy';
    protected $name = 'privacy:encrypt-at-rest';
    protected $description = 'Cifra datos personales heredados y verifica evidencia, indices y triggers.';
    protected $usage = 'privacy:encrypt-at-rest <backup_file> execute';
    protected $arguments = [
        'backup_file' => 'Respaldo SQL verificado creado antes de la ventana.',
        'execute' => 'Confirmacion literal requerida.',
    ];

    public function run(array $params)
    {
        $backup = (string) ($params[0] ?? '');
        if (($params[1] ?? '') !== 'execute' || ! is_file($backup) || filesize($backup) < 100) {
            CLI::error('Uso: php spark privacy:encrypt-at-rest <respaldo.sql> execute');
            return EXIT_ERROR;
        }

        $db = db_connect();
        if ($db->DBDriver !== 'MySQLi' || ! $db->tableExists('dp_cifrado_estado')) {
            CLI::error('La migracion estructural de cifrado no esta aplicada sobre MySQL/MariaDB.');
            return EXIT_ERROR;
        }

        $vault = new PrivacyVault();
        /** @var PrivacyEncryption $config */
        $config = config(PrivacyEncryption::class);
        $lock = $db->query("SELECT GET_LOCK('censo_privacy_encrypt_at_rest', 5) AS acquired")->getRowArray();
        if ((int) ($lock['acquired'] ?? 0) !== 1) {
            CLI::error('No fue posible obtener el bloqueo exclusivo de migracion.');
            return EXIT_ERROR;
        }

        $triggers = [];
        try {
            $this->verifyAuditChains($db, 'antes');
            $triggers = $this->suspendUpdateTriggers($db, array_keys($config->tables));
            foreach ($config->tables as $table => $spec) {
                $this->migrateTable($db, $vault, $config, $table, $spec);
            }
            $this->encryptLegacyFiles($db, $vault);
            $this->verifyAuditChains($db, 'despues');
            CLI::write('Cifrado en reposo completado y verificado.', 'green');
            return EXIT_SUCCESS;
        } catch (\Throwable $e) {
            CLI::error('Cifrado abortado: ' . $e->getMessage());
            return EXIT_ERROR;
        } finally {
            try {
                $this->restoreTriggers($db, $triggers);
            } finally {
                $db->query("SELECT RELEASE_LOCK('censo_privacy_encrypt_at_rest')");
            }
        }
    }

    private function migrateTable($db, PrivacyVault $vault, PrivacyEncryption $config, string $table, array $spec): void
    {
        if (! $db->fieldExists('id', $table)) {
            throw new \RuntimeException('La tabla ' . $table . ' no tiene llave id para migracion reanudable.');
        }
        $state = $db->table('dp_cifrado_estado')->where('tabla', $table)->get()->getRowArray();
        $lastId = ($state['estado'] ?? '') === 'completado' ? 0 : (int) ($state['ultimo_id'] ?? 0);
        $count = ($state['estado'] ?? '') === 'completado' ? 0 : (int) ($state['filas_cifradas'] ?? 0);
        $now = date('Y-m-d H:i:s');
        $db->query(
            'INSERT INTO dp_cifrado_estado (tabla, ultimo_id, filas_cifradas, estado, clave_version, iniciado_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE estado=VALUES(estado), clave_version=VALUES(clave_version), iniciado_at=COALESCE(iniciado_at, VALUES(iniciado_at)), updated_at=VALUES(updated_at)',
            [$table, $lastId, $count, 'en_proceso', $vault->cipher()->keyVersion(), $now, $now]
        );

        do {
            $rows = $db->table($table)->where('id >', $lastId)->orderBy('id', 'ASC')->limit(100)->get()->getResultArray();
            foreach ($rows as $stored) {
                $plain = $vault->decryptRow($table, $stored);
                if (! $vault->verifyHashInvariant($table, $plain)) {
                    throw new \RuntimeException('Hash probatorio invalido en ' . $table . ' id ' . $stored['id']);
                }
                $encrypted = $vault->encryptRow($table, $plain);
                $update = [];
                foreach ($spec['encrypt'] as $field) {
                    $update[$field] = $encrypted[$field] ?? null;
                }
                foreach ($config->blindFields($table) as $blind) {
                    $update[$blind['col']] = $encrypted[$blind['col']] ?? null;
                }
                $db->transException(true)->transStart();
                $db->table($table)->where('id', $stored['id'])->update($update);
                $roundTrip = $vault->decryptRow($table, $db->table($table)->where('id', $stored['id'])->get()->getRowArray());
                if (! $vault->verifyHashInvariant($table, $roundTrip)) {
                    throw new \RuntimeException('Verificacion posterior fallida en ' . $table . ' id ' . $stored['id']);
                }
                foreach ($spec['encrypt'] as $field) {
                    if (($plain[$field] ?? null) !== ($roundTrip[$field] ?? null)) {
                        throw new \RuntimeException('Round-trip fallido en ' . $table . '.' . $field . ' id ' . $stored['id']);
                    }
                }
                $db->transComplete();
                $lastId = (int) $stored['id'];
                $count++;
                $db->table('dp_cifrado_estado')->where('tabla', $table)->update([
                    'ultimo_id' => $lastId, 'filas_cifradas' => $count, 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } while ($rows !== []);

        $verification = hash('sha256', $table . '|' . $lastId . '|' . $count . '|' . $vault->cipher()->keyVersion());
        $db->table('dp_cifrado_estado')->where('tabla', $table)->update([
            'estado' => 'completado', 'verificacion_hash' => $verification,
            'completado_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CLI::write($table . ': ' . $count . ' filas verificadas.');
    }

    private function suspendUpdateTriggers($db, array $tables): array
    {
        if ($tables === []) {
            return [];
        }
        $rows = $db->table('information_schema.TRIGGERS')
            ->select('TRIGGER_NAME, EVENT_OBJECT_TABLE, ACTION_TIMING, EVENT_MANIPULATION, ACTION_STATEMENT')
            ->where('TRIGGER_SCHEMA', $db->getDatabase())->where('EVENT_MANIPULATION', 'UPDATE')
            ->whereIn('EVENT_OBJECT_TABLE', $tables)->get()->getResultArray();
        foreach ($rows as $trigger) {
            $name = (string) $trigger['TRIGGER_NAME'];
            if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                throw new \RuntimeException('Nombre de trigger no seguro.');
            }
            $db->query("DROP TRIGGER `{$name}`");
        }
        return $rows;
    }

    private function restoreTriggers($db, array $triggers): void
    {
        foreach ($triggers as $trigger) {
            $name = (string) $trigger['TRIGGER_NAME'];
            $table = (string) $trigger['EVENT_OBJECT_TABLE'];
            $timing = (string) $trigger['ACTION_TIMING'];
            $event = (string) $trigger['EVENT_MANIPULATION'];
            if (! preg_match('/^[A-Za-z0-9_]+$/', $name . $table . $timing . $event)) {
                throw new \RuntimeException('No fue posible validar la definicion del trigger ' . $name);
            }
            $db->query("DROP TRIGGER IF EXISTS `{$name}`");
            $db->query("CREATE TRIGGER `{$name}` {$timing} {$event} ON `{$table}` FOR EACH ROW {$trigger['ACTION_STATEMENT']}");
        }
    }

    private function encryptLegacyFiles($db, PrivacyVault $vault): void
    {
        $root = WRITEPATH . 'uploads/proteccion-datos';
        if (is_dir($root)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $path = str_replace('\\', '/', substr($file->getPathname(), strlen(WRITEPATH)));
                $blob = file_get_contents($file->getPathname());
                if ($blob !== false && ! str_starts_with($blob, "PRIVENC1\0")) {
                    file_put_contents($file->getPathname(), $vault->encryptFile($blob, 'pdf|' . $path), LOCK_EX);
                }
            }
        }
        foreach ($db->table('dp_consentimiento_verificaciones')->where('soporte_representacion_ruta IS NOT NULL', null, false)->get()->getResultArray() as $row) {
            $path = (string) $row['soporte_representacion_ruta'];
            $absolute = WRITEPATH . $path;
            if (! is_file($absolute)) {
                continue;
            }
            $blob = file_get_contents($absolute);
            if ($blob !== false && ! str_starts_with($blob, "PRIVENC1\0")) {
                file_put_contents($absolute, $vault->encryptFile($blob, 'representation|' . str_replace('\\', '/', $path)), LOCK_EX);
            }
        }
    }

    private function verifyAuditChains($db, string $moment): void
    {
        foreach ($db->table('dp_auditoria')->select('cliente_id')->distinct()->get()->getResultArray() as $row) {
            $invalidId = PrivacyAudit::verifyChain((int) $row['cliente_id']);
            if ($invalidId !== null) {
                throw new \RuntimeException('Cadena de auditoria invalida ' . $moment . ' del cifrado; primer id ' . $invalidId);
            }
        }
    }
}
