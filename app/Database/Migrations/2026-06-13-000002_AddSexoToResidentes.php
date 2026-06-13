<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSexoToResidentes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('censo_residentes', [
            'sexo' => ['type' => 'ENUM', 'constraint' => ['M', 'F', 'Otro'], 'null' => true, 'after' => 'documento'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('censo_residentes', 'sexo');
    }
}
