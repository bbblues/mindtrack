<?php

use App\Libraries\PermissionCatalog;
use CodeIgniter\Test\CIUnitTestCase;

final class PermissionCatalogTest extends CIUnitTestCase
{
    public function testProfessionalCanViewAllButOnlyEditOwn(): void
    {
        $permissions = PermissionCatalog::defaultRolePermissions()['profissional'];

        $this->assertContains('patients.view.any', $permissions);
        $this->assertContains('patients.edit.own', $permissions);
        $this->assertNotContains('patients.edit.any', $permissions);
    }

    public function testReceptionDoesNotEditPatients(): void
    {
        $permissions = PermissionCatalog::defaultRolePermissions()['recepcao'];

        $this->assertNotContains('patients.edit.any', $permissions);
        $this->assertNotContains('patients.edit.own', $permissions);
        $this->assertContains('patients.view.any', $permissions);
    }
}
