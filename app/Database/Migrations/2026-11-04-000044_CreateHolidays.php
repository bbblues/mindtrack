<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHolidays extends Migration
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
            'city_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'null'       => true,
            ],
            'state' => [
                'type'       => 'VARCHAR',
                'constraint' => 2,
                'null'       => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'scope' => [
                'type'       => 'ENUM',
                'constraint' => ['national', 'state', 'municipal', 'closure'],
                'default'    => 'municipal',
            ],
            'badge' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'Feriado Municipal',
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'default'    => '#f59e0b',
            ],
            'source' => [
                'type'       => 'ENUM',
                'constraint' => ['brasilapi', 'manual'],
                'default'    => 'manual',
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
        $this->forge->addKey('date');
        $this->forge->addKey('city_code');
        $this->forge->addKey('state');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('holidays', true);
    }

    public function down()
    {
        $this->forge->dropTable('holidays', true);
    }
}
