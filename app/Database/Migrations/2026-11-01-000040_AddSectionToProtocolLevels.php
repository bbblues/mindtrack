<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSectionToProtocolLevels extends Migration
{
    public function up()
    {
        $this->forge->addColumn('protocol_levels', [
            'section' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'name',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('protocol_levels', 'section');
    }
}
