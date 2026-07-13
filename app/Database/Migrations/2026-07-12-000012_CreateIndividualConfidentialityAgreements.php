<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIndividualConfidentialityAgreements extends Migration
{
    public function up(): void
    {
        $this->createAgreements();
        $this->createEvents();
        if ($this->db->DBDriver === 'MySQLi') {
            foreach (['dp_compromiso_eventos_bu' => 'UPDATE', 'dp_compromiso_eventos_bd' => 'DELETE'] as $name => $event) {
                $this->db->query("CREATE TRIGGER {$name} BEFORE {$event} ON dp_compromiso_eventos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La evidencia del compromiso es append-only'");
            }
            $this->db->query("CREATE TRIGGER dp_compromisos_confidencialidad_bd BEFORE DELETE ON dp_compromisos_confidencialidad FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El compromiso individual no puede eliminarse'");
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            foreach (['dp_compromiso_eventos_bu', 'dp_compromiso_eventos_bd', 'dp_compromisos_confidencialidad_bd'] as $trigger) {
                $this->db->query('DROP TRIGGER IF EXISTS ' . $trigger);
            }
        }
        $this->forge->dropTable('dp_compromiso_eventos', true);
        $this->forge->dropTable('dp_compromisos_confidencialidad', true);
    }

    private function createAgreements(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true], 'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'documento_id' => ['type' => 'BIGINT', 'unsigned' => true], 'documento_version' => ['type' => 'INT', 'unsigned' => true],
            'documento_hash' => ['type' => 'CHAR', 'constraint' => 64], 'token' => ['type' => 'CHAR', 'constraint' => 64],
            'firmante_nombre' => ['type' => 'VARCHAR', 'constraint' => 191], 'tipo_documento' => ['type' => 'VARCHAR', 'constraint' => 20],
            'numero_documento' => ['type' => 'VARCHAR', 'constraint' => 80], 'tipo_vinculo' => ['type' => 'VARCHAR', 'constraint' => 40],
            'rol' => ['type' => 'VARCHAR', 'constraint' => 80], 'autorizador_usuario_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'autorizador_nombre' => ['type' => 'VARCHAR', 'constraint' => 191], 'bases_json' => ['type' => 'LONGTEXT'],
            'finalidades_json' => ['type' => 'LONGTEXT'], 'operaciones_json' => ['type' => 'LONGTEXT'],
            'alcance_total_justificacion' => ['type' => 'TEXT', 'null' => true], 'vigencia_desde' => ['type' => 'DATE'],
            'vigencia_hasta' => ['type' => 'DATE'], 'canal_aceptacion' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'firma_electronica'],
            'instancia_html' => ['type' => 'LONGTEXT'], 'instancia_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'vista_at' => ['type' => 'DATETIME', 'null' => true], 'codigo_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'codigo_expira_at' => ['type' => 'DATETIME', 'null' => true], 'codigo_intentos' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'codigo_verificado_at' => ['type' => 'DATETIME', 'null' => true], 'firma_imagen' => ['type' => 'LONGTEXT', 'null' => true],
            'firma_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true], 'aceptado_at' => ['type' => 'DATETIME', 'null' => true],
            'ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true], 'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'copia_enviada_at' => ['type' => 'DATETIME', 'null' => true], 'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pendiente'],
            'cerrado_at' => ['type' => 'DATETIME', 'null' => true], 'cierre_motivo' => ['type' => 'TEXT', 'null' => true],
            'devolucion_certificada_at' => ['type' => 'DATETIME', 'null' => true], 'devolucion_evidencia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('token');
        $this->forge->addKey(['cliente_id', 'usuario_id', 'estado']);
        $this->forge->createTable('dp_compromisos_confidencialidad', true);
    }

    private function createEvents(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'compromiso_id' => ['type' => 'BIGINT', 'unsigned' => true], 'tipo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'detalle_json' => ['type' => 'LONGTEXT'], 'evento_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'usuario_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true], 'ocurrido_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['compromiso_id', 'ocurrido_at']);
        $this->forge->createTable('dp_compromiso_eventos', true);
    }
}
