<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeProfessionalScheduleUnitNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('professional_schedules', [
            'unit_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('professional_schedules', [
            'unit_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);
    }
}
