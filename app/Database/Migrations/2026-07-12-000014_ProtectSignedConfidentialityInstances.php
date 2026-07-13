<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProtectSignedConfidentialityInstances extends Migration
{
    public function up(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }
        $this->db->query(<<<'SQL'
CREATE TRIGGER dp_compromisos_confidencialidad_bu
BEFORE UPDATE ON dp_compromisos_confidencialidad
FOR EACH ROW
BEGIN
    IF OLD.aceptado_at IS NOT NULL AND NOT (
        OLD.cliente_id <=> NEW.cliente_id AND OLD.usuario_id <=> NEW.usuario_id AND
        OLD.documento_id <=> NEW.documento_id AND OLD.documento_version <=> NEW.documento_version AND
        OLD.documento_hash <=> NEW.documento_hash AND OLD.token <=> NEW.token AND
        OLD.firmante_nombre <=> NEW.firmante_nombre AND OLD.tipo_documento <=> NEW.tipo_documento AND
        OLD.numero_documento <=> NEW.numero_documento AND OLD.tipo_vinculo <=> NEW.tipo_vinculo AND
        OLD.rol <=> NEW.rol AND OLD.autorizador_usuario_id <=> NEW.autorizador_usuario_id AND
        OLD.autorizador_nombre <=> NEW.autorizador_nombre AND OLD.bases_json <=> NEW.bases_json AND
        OLD.finalidades_json <=> NEW.finalidades_json AND OLD.operaciones_json <=> NEW.operaciones_json AND
        OLD.alcance_total_justificacion <=> NEW.alcance_total_justificacion AND
        OLD.vigencia_desde <=> NEW.vigencia_desde AND OLD.vigencia_hasta <=> NEW.vigencia_hasta AND
        OLD.canal_aceptacion <=> NEW.canal_aceptacion AND OLD.instancia_html <=> NEW.instancia_html AND
        OLD.instancia_hash <=> NEW.instancia_hash AND OLD.vista_at <=> NEW.vista_at AND
        OLD.codigo_verificado_at <=> NEW.codigo_verificado_at AND OLD.firma_imagen <=> NEW.firma_imagen AND
        OLD.firma_hash <=> NEW.firma_hash AND OLD.aceptado_at <=> NEW.aceptado_at AND
        OLD.ip <=> NEW.ip AND OLD.user_agent <=> NEW.user_agent
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La instancia firmada y su evidencia son inmutables';
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('DROP TRIGGER IF EXISTS dp_compromisos_confidencialidad_bu');
        }
    }
}
