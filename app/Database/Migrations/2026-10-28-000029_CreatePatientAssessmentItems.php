<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePatientAssessmentItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'assessment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'protocol_item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'status_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'value_at_time' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('assessment_id');
        $this->forge->addKey('protocol_item_id');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assessment_id', 'patient_assessments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('protocol_item_id', 'protocol_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('status_id', 'protocol_statuses', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('patient_assessment_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('patient_assessment_items', true);
    }
}
