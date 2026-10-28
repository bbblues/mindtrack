<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUnitIdToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'unit_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('unit_id');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'CASCADE', 'CASCADE', 'fk_users_unit_id');
    }

    public function down()
    {
        $this->forge->dropForeignKey('users', 'fk_users_unit_id');
        $this->forge->dropKey('users', 'unit_id');
        $this->forge->dropColumn('users', 'unit_id');
    }
}
