<?php

namespace App\Database\Migrations;

use App\Libraries\PrivacyAudit;
use CodeIgniter\Database\Migration;

/**
 * C-1: convierte dp_auditoria en un registro append-only y encadenado por hash.
 * Antes de esta migracion la bitacora maestra era completamente alterable
 * (UPDATE/DELETE sin restriccion y sin sello de integridad).
 */
class ProtectPrivacyAuditTrail extends Migration
{
    public function up(): void
    {
        $fields = [];
        if (! $this->db->fieldExists('hash_anterior', 'dp_auditoria')) {
            $fields['hash_anterior'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'created_at'];
        }
        if (! $this->db->fieldExists('evento_hash', 'dp_auditoria')) {
            $fields['evento_hash'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'hash_anterior'];
        }
        if ($fields !== []) {
            $this->forge->addColumn('dp_auditoria', $fields);
        }

        // Sella la cadena de las filas historicas (por cliente, en orden de id)
        // ANTES de instalar los triggers, ya que despues el UPDATE queda bloqueado.
        $clientes = $this->db->table('dp_auditoria')->select('cliente_id')->distinct()->get()->getResultArray();
        foreach ($clientes as $cliente) {
            $previousHash = '';
            foreach ($this->db->table('dp_auditoria')->where('cliente_id', $cliente['cliente_id'])->orderBy('id', 'ASC')->get()->getResultArray() as $row) {
                $hash = PrivacyAudit::chain($previousHash, PrivacyAudit::canonical($row));
                $this->db->table('dp_auditoria')->where('id', $row['id'])->update(['hash_anterior' => $previousHash, 'evento_hash' => $hash]);
                $previousHash = $hash;
            }
        }

        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query("CREATE TRIGGER dp_auditoria_bu BEFORE UPDATE ON dp_auditoria FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La bitacora de auditoria es append-only'");
            $this->db->query("CREATE TRIGGER dp_auditoria_bd BEFORE DELETE ON dp_auditoria FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La bitacora de auditoria es append-only'");
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('DROP TRIGGER IF EXISTS dp_auditoria_bu');
            $this->db->query('DROP TRIGGER IF EXISTS dp_auditoria_bd');
        }
        foreach (['evento_hash', 'hash_anterior'] as $column) {
            if ($this->db->fieldExists($column, 'dp_auditoria')) {
                $this->forge->dropColumn('dp_auditoria', $column);
            }
        }
    }
}
