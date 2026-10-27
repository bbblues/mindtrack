<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppointments extends Migration
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
            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'unit_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'start_time' => [
                'type' => 'DATETIME',
            ],
            'end_time' => [
                'type' => 'DATETIME',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['agendado', 'confirmado', 'realizado', 'falta_paciente', 'falta_profissional', 'cancelado'],
                'default'    => 'agendado',
            ],
            'is_recurring' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'recurrence_group_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'notes' => [
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
        $this->forge->addKey('patient_id');
        $this->forge->addKey('professional_id');
        $this->forge->addKey('room_id');
        $this->forge->addKey('unit_id');
        $this->forge->addKey('start_time');
        $this->forge->addKey('end_time');
        $this->forge->addKey('recurrence_group_id');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('patient_id', 'patients', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('professional_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('room_id', 'rooms', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('appointments', true);
    }

    public function down()
    {
        $this->forge->dropTable('appointments', true);
    }
}
