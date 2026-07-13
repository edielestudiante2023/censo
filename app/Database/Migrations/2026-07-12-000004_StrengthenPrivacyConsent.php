<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StrengthenPrivacyConsent extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('dp_finalidades', [
            'base_juridica_tipo' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'autorizacion', 'after' => 'descripcion'],
            'base_juridica_detalle' => ['type' => 'TEXT', 'null' => true, 'after' => 'base_juridica_tipo'],
            'categorias_datos_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'base_juridica_detalle'],
            'consecuencia_negativa' => ['type' => 'TEXT', 'null' => true, 'after' => 'es_opcional'],
        ]);
        $baseCategories = [];
        foreach ($this->db->table('dp_bases_datos')->select('id, categorias_datos_json')->get()->getResultArray() as $base) {
            $baseCategories[(int) $base['id']] = $base['categorias_datos_json'];
        }
        foreach ($this->db->table('dp_finalidades')->select('id, base_id')->get()->getResultArray() as $purpose) {
            $this->db->table('dp_finalidades')->where('id', $purpose['id'])->update([
                'categorias_datos_json' => $baseCategories[(int) $purpose['base_id']] ?? '[]',
            ]);
        }

        $this->forge->addColumn('dp_consentimientos', [
            'documento_version' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'documento_id'],
            'documento_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'documento_version'],
            'instancia_html' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'documento_hash'],
            'instancia_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'instancia_html'],
            'decision_vector_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'decision'],
            'calidad_otorgante' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'titular', 'after' => 'representante_nombre'],
            'representante_documento' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'calidad_otorgante'],
            'calidad_representacion' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'representante_documento'],
            'opinion_menor' => ['type' => 'TEXT', 'null' => true, 'after' => 'calidad_representacion'],
            'canal' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'portal_web', 'after' => 'evidencia_hash'],
            'tipo_evidencia' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'firma_electronica', 'after' => 'canal'],
            'verificacion_identidad' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'tipo_evidencia'],
            'zona_horaria' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'America/Bogota', 'after' => 'user_agent'],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'consentimiento_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 30],
            'alcance_json' => ['type' => 'LONGTEXT'],
            'instancia_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'evidencia_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'canal' => ['type' => 'VARCHAR', 'constraint' => 30],
            'ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ocurrido_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'consentimiento_id']);
        $this->forge->createTable('dp_consentimiento_eventos', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cliente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'documento_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'email' => ['type' => 'VARCHAR', 'constraint' => 191],
            'datos_json' => ['type' => 'LONGTEXT'],
            'codigo_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'intentos' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'expira_at' => ['type' => 'DATETIME'],
            'verificado_at' => ['type' => 'DATETIME', 'null' => true],
            'consumido_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['cliente_id', 'email']);
        $this->forge->createTable('dp_consentimiento_verificaciones', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('dp_consentimiento_verificaciones', true);
        $this->forge->dropTable('dp_consentimiento_eventos', true);
        $this->forge->dropColumn('dp_consentimientos', [
            'documento_version', 'documento_hash', 'instancia_html', 'instancia_hash', 'decision_vector_json',
            'calidad_otorgante', 'representante_documento', 'calidad_representacion', 'opinion_menor',
            'canal', 'tipo_evidencia', 'verificacion_identidad', 'zona_horaria',
        ]);
        $this->forge->dropColumn('dp_finalidades', [
            'base_juridica_tipo', 'base_juridica_detalle', 'categorias_datos_json', 'consecuencia_negativa',
        ]);
    }
}
