<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComplexProtocolFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('protocol_items', [
            'max_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
                'default'    => 1.0,
                'after'      => 'sort_order',
            ],
            'metadata' => [
                'type'  => 'JSON',
                'null'  => true,
                'after' => 'max_score',
            ],
        ]);

        $this->forge->addColumn('protocols', [
            'view_slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'description',
            ],
        ]);

        $this->forge->addColumn('patient_assessment_items', [
            'score_achieved' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
                'default'    => 0.0,
                'after'      => 'value_at_time',
            ],
        ]);

        $this->forge->modifyColumn('patient_assessments', [
            'score' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.0,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('protocol_items', 'max_score');
        $this->forge->dropColumn('protocol_items', 'metadata');
        $this->forge->dropColumn('protocols', 'view_slug');
        $this->forge->dropColumn('patient_assessment_items', 'score_achieved');

        $this->forge->modifyColumn('patient_assessments', [
            'score' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
    }
}
