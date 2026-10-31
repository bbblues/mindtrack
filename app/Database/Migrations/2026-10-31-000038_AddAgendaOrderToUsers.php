<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAgendaOrderToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'agenda_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'agenda_order');
    }
}
