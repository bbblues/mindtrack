<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Garante o isolamento "ver todos, editar apenas os próprios" para o
 * profissional (terapeuta), sem regredir o acesso da recepção/gestor.
 *
 * Roda contra o banco de testes MySQL (group `tests`), migrando o schema
 * completo da aplicação.
 */
final class PatientAccessTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrateOnce = false; // garante o schema mesmo se outra classe de teste migrar antes
    protected $refresh     = false;
    protected $namespace   = null; // migra App + Tests\Support

    private const TENANT_ID = 1;
    private const TERAPEUTA = 100;
    private const GESTOR    = 101;
    private const PATIENT_X = 500; // não vinculado ao terapeuta
    private const PATIENT_Y = 501; // vinculado ao terapeuta

    protected function setUp(): void
    {
        parent::setUp();

        // O serviço de autorização é compartilhado entre os testes e cacheia
        // os slugs de permissão da sessão. Reinjetamos uma instância limpa para
        // que cada teste use as permissões da própria sessão.
        \Config\Services::injectMock('authorization', new \App\Libraries\Authorization());

        $this->seedFixtures();
    }

    private function seedFixtures(): void
    {
        $this->hasInDatabase('tenants', [
            'id'        => self::TENANT_ID,
            'name'      => 'Clínica Teste',
            'slug'      => 'clinica-teste',
            'is_active' => 1,
        ]);

        $this->hasInDatabase('units', [
            'id'        => 1,
            'tenant_id' => self::TENANT_ID,
            'name'      => 'Matriz',
            'is_active' => 1,
        ]);

        $this->hasInDatabase('health_insurances', [
            'id'        => 1,
            'tenant_id' => self::TENANT_ID,
            'name'      => 'Convênio Teste',
        ]);

        $this->hasInDatabase('users', [
            'id'             => self::TERAPEUTA,
            'tenant_id'      => self::TENANT_ID,
            'name'           => 'Dra. Lidiane',
            'email'          => 'lidiane@teste.com',
            'password'       => 'secret',
            'is_super_admin' => 0,
            'is_active'      => 1,
            'agenda_order'   => 0,
        ]);

        $this->hasInDatabase('users', [
            'id'             => self::GESTOR,
            'tenant_id'      => self::TENANT_ID,
            'name'           => 'Gestor Teste',
            'email'          => 'gestor@teste.com',
            'password'       => 'secret',
            'is_super_admin' => 0,
            'is_active'      => 1,
            'agenda_order'   => 0,
        ]);

        $this->hasInDatabase('patients', [
            'id'         => self::PATIENT_X,
            'tenant_id'  => self::TENANT_ID,
            'name'       => 'Paciente Desvinculado',
            'birth_date' => '2015-01-01',
            'unit_id'    => 1,
            'is_active'  => 1,
            'color'      => '#3b82f6',
        ]);

        $this->hasInDatabase('patients', [
            'id'         => self::PATIENT_Y,
            'tenant_id'  => self::TENANT_ID,
            'name'       => 'Paciente Vinculado',
            'birth_date' => '2016-01-01',
            'unit_id'    => 1,
            'is_active'  => 1,
            'color'      => '#22c55e',
        ]);

        $this->hasInDatabase('patient_professionals', [
            'tenant_id'  => self::TENANT_ID,
            'patient_id' => self::PATIENT_Y,
            'user_id'    => self::TERAPEUTA,
            'is_primary' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionFor(string $role): array
    {
        if ($role === 'profissional') {
            return [
                'user_id'               => self::TERAPEUTA,
                'tenant_id'             => self::TENANT_ID,
                'is_super_admin'        => 0,
                'user_name'             => 'Dra. Lidiane',
                'user_role_slugs'       => ['profissional'],
                'user_permission_slugs' => [
                    'patients.view.any',
                    'patients.view.own',
                    'patients.edit.own',
                    'patients.deactivate.own',
                    'agenda.view.own',
                    'agenda.edit.own',
                    'agenda.confirm.own',
                    'assessments.start',
                    'assessments.edit.own',
                    'assessments.finalize.own',
                    'assessments.export_pdf',
                ],
            ];
        }

        return [
            'user_id'               => self::GESTOR,
            'tenant_id'             => self::TENANT_ID,
            'is_super_admin'        => 0,
            'user_name'             => 'Gestor Teste',
            'user_role_slugs'       => ['gestor'],
            'user_permission_slugs' => [
                'patients.create',
                'patients.view.any',
                'patients.edit.any',
                'patients.deactivate.any',
                'assessments.start',
                'assessments.edit.any',
                'assessments.finalize.any',
                'assessments.export_pdf',
            ],
        ];
    }

    public function testProfessionalSeesAllPatientsWhenViewingAll(): void
    {
        $result = $this
            ->withSession($this->sessionFor('profissional'))
            ->get('patients?view=all');

        $result->assertStatus(200);
        $result->assertSee('Paciente Desvinculado');
        $result->assertSee('Paciente Vinculado');
    }

    public function testProfessionalDefaultViewShowsOnlyLinkedPatients(): void
    {
        $result = $this
            ->withSession($this->sessionFor('profissional'))
            ->get('patients');

        $result->assertStatus(200);
        $result->assertSee('Paciente Vinculado');
        $result->assertDontSee('Paciente Desvinculado');
    }

    public function testProfessionalDoesNotSeeEditLinkForUnlinkedPatientInList(): void
    {
        $result = $this
            ->withSession($this->sessionFor('profissional'))
            ->get('patients?view=all');

        $result->assertStatus(200);
        $result->assertSee('Paciente Desvinculado');
        $result->assertDontSee('patients/edit/' . self::PATIENT_X);
        $result->assertSee('patients/edit/' . self::PATIENT_Y);
    }

    public function testProfessionalCannotEditUnlinkedPatient(): void
    {
        $result = $this
            ->withSession($this->sessionFor('profissional'))
            ->get('patients/edit/' . self::PATIENT_X);

        $result->assertRedirect();
    }

    public function testProfessionalCanEditLinkedPatient(): void
    {
        $result = $this
            ->withSession($this->sessionFor('profissional'))
            ->get('patients/edit/' . self::PATIENT_Y);

        $result->assertStatus(200);
    }

    public function testManagerCanEditUnlinkedPatient(): void
    {
        $result = $this
            ->withSession($this->sessionFor('gestor'))
            ->get('patients/edit/' . self::PATIENT_X);

        $result->assertStatus(200);
    }
}

