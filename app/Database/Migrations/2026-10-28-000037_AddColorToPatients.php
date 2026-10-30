<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColorToPatients extends Migration
{
    public function up()
    {
        $this->forge->addColumn('patients', [
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('patients', 'color');
    }
}