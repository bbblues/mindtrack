<?php

namespace App\Database\Seeds;

use App\Libraries\RbacSynchronizer;
use CodeIgniter\Database\Seeder;

/**
 * Semeia o catálogo de permissões (idempotente) e aplica os padrões aos
 * papéis padrão de todos os tenants.
 */
class PermissionSeeder extends Seeder
{
    public function run()
    {
        $synchronizer = new RbacSynchronizer();
        $count        = $synchronizer->syncCatalog();
        $synchronizer->syncAllTenants();

        echo 'Catálogo sincronizado: ' . $count . ' permissões.' . PHP_EOL;
        echo 'Permissões padrão aplicadas aos papéis de todos os tenants.' . PHP_EOL;
    }
}
