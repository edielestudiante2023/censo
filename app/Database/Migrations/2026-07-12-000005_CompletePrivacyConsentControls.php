<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CompletePrivacyConsentControls extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('dp_finalidades', [
            'version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1, 'after' => 'descripcion'],
            'contenido_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'version'],
        ]);
        foreach ($this->db->table('dp_finalidades')->get()->getResultArray() as $purpose) {
            $this->db->table('dp_finalidades')->where('id', $purpose['id'])->update([
                'contenido_hash' => $this->purposeHash($purpose),
            ]);
        }

        $this->forge->addColumn('dp_consentimientos', [
            'soporte_representacion_ruta' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'calidad_representacion'],
            'soporte_representacion_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'soporte_representacion_ruta'],
        ]);
        $this->forge->addColumn('dp_consentimiento_verificaciones', [
            'soporte_representacion_ruta' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'datos_json'],
            'soporte_representacion_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'soporte_representacion_ruta'],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'base_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'finalidad_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'dato' => ['type' => 'VARCHAR', 'constraint' => 191],
            'finalidad_exclusiva' => ['type' => 'TEXT'],
            'activo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'finalidad_id']);
        $this->forge->addUniqueKey(['finalidad_id', 'dato']);
        $this->forge->createTable('dp_finalidad_datos_sensibles', true);

        if ($this->db->DBDriver === 'MySQLi') {
            foreach ([
                'dp_consentimientos_bu' => 'BEFORE UPDATE ON dp_consentimientos',
                'dp_consentimientos_bd' => 'BEFORE DELETE ON dp_consentimientos',
                'dp_consentimiento_eventos_bu' => 'BEFORE UPDATE ON dp_consentimiento_eventos',
                'dp_consentimiento_eventos_bd' => 'BEFORE DELETE ON dp_consentimiento_eventos',
            ] as $name => $timing) {
                $this->db->query("CREATE TRIGGER {$name} {$timing} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El expediente de consentimiento es append-only'");
            }
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            foreach (['dp_consentimientos_bu', 'dp_consentimientos_bd', 'dp_consentimiento_eventos_bu', 'dp_consentimiento_eventos_bd'] as $trigger) {
                $this->db->query('DROP TRIGGER IF EXISTS ' . $trigger);
            }
        }
        $this->forge->dropTable('dp_finalidad_datos_sensibles', true);
        $this->forge->dropColumn('dp_consentimiento_verificaciones', ['soporte_representacion_ruta', 'soporte_representacion_hash']);
        $this->forge->dropColumn('dp_consentimientos', ['soporte_representacion_ruta', 'soporte_representacion_hash']);
        $this->forge->dropColumn('dp_finalidades', ['version', 'contenido_hash']);
    }

    private function purposeHash(array $purpose): string
    {
        return hash('sha256', json_encode([
            'descripcion' => $purpose['descripcion'], 'base_juridica_tipo' => $purpose['base_juridica_tipo'],
            'base_juridica_detalle' => $purpose['base_juridica_detalle'], 'categorias' => $purpose['categorias_datos_json'],
            'opcional' => (int) $purpose['es_opcional'], 'consecuencia' => $purpose['consecuencia_negativa'],
            'explicito' => (int) $purpose['requiere_consentimiento_explicito'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
