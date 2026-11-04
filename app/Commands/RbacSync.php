<?php

namespace App\Commands;

use App\Libraries\RbacSynchronizer;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sincroniza o catálogo de permissões e aplica os padrões aos papéis.
 *
 * Uso: php spark rbac:sync
 */
class RbacSync extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'rbac:sync';
    protected $usage       = 'rbac:sync';
    protected $description = 'Sincroniza o catálogo de permissões e aplica os padrões aos papéis de cada tenant.';

    /**
     * @param array<array-key, string|null> $params
     *
     * @return int
     */
    public function run(array $params)
    {
        $synchronizer = new RbacSynchronizer();
        $count        = $synchronizer->syncCatalog();
        $synchronizer->syncAllTenants();

        CLI::write('Catálogo sincronizado: ' . $count . ' permissões.', 'green');
        CLI::write('Permissões padrão aplicadas aos papéis de todos os tenants.', 'green');

        return EXIT_SUCCESS;
    }
}
