<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusAndConfidentialToSessionEvolutions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('session_evolutions', [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'finalized'],
                'default'    => 'draft',
                'after'      => 'evolution_text',
            ],
            'finalized_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'status',
            ],
            'finalized_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'finalized_at',
            ],
            'is_confidential' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'finalized_by',
            ],
        ]);

        $this->forge->addForeignKey('finalized_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_session_evolutions_finalized_by');
        $this->forge->processIndexes('session_evolutions');
    }

    public function down()
    {
        $this->forge->dropForeignKey('session_evolutions', 'fk_session_evolutions_finalized_by');
        $this->forge->dropColumn('session_evolutions', 'status');
        $this->forge->dropColumn('session_evolutions', 'finalized_at');
        $this->forge->dropColumn('session_evolutions', 'finalized_by');
        $this->forge->dropColumn('session_evolutions', 'is_confidential');
    }
}
