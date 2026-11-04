<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddScopeAndSortToPermissions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('permissions', [
            'scope' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'after'      => 'module',
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'scope',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('permissions', 'scope');
        $this->forge->dropColumn('permissions', 'sort_order');
    }
}
