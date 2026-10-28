<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSessionEvolutions extends Migration
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
            'appointment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'professional_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'evolution_text' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('appointment_id');
        $this->forge->addKey('patient_id');
        $this->forge->addKey('professional_id');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('appointment_id', 'appointments', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('patient_id', 'patients', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('professional_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('session_evolutions', true);
    }

    public function down()
    {
        $this->forge->dropTable('session_evolutions', true);
    }
}
