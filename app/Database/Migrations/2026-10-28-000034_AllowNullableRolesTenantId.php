<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AllowNullableRolesTenantId extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('roles', [
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropForeignKey('roles', 'roles_tenant_id_foreign');
        $this->forge->modifyColumn('roles', [
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
    }
}
