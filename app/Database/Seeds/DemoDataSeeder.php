<?php

namespace App\Database\Seeds;

use App\Modules\Agenda\Models\AppointmentModel;
use App\Modules\Core\Models\ModuleModel;
use App\Modules\Core\Models\RoleModel;
use App\Modules\Core\Models\RoleUserModel;
use App\Modules\Core\Models\RoomModel;
use App\Modules\Core\Models\TenantModel;
use App\Modules\Core\Models\UnitModel;
use App\Modules\Core\Models\UserModel;
use App\Modules\Evolutions\Models\SessionEvolutionModel;
use App\Modules\Patients\Libraries\PatientColor;
use App\Modules\Patients\Models\PatientModel;
use CodeIgniter\Database\Seeder;
use DateTimeImmutable;
use Faker\Factory;
use Faker\Generator;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    private const DEMO_SLUG   = 'clinica-teste';
    private const ADMIN_EMAIL = 'admin@clinica-teste.com';

    /**
     * Tabelas de dominio que possuem a coluna tenant_id e devem ser
     * limpas ao resetar o tenant de demonstracao.
     *
     * @var list<string>
     */
    private const TENANT_TABLES = [
        'users',
        'roles',
        'module_tenant',
        'settings',
        'units',
        'health_insurances',
        'specialties',
        'rooms',
        'tags',
        'absence_reasons',
        'discharge_reasons',
        'note_templates',
        'patients',
        'guardians',
        'patient_guardians',
        'appointments',
        'professional_schedules',
        'clinic_schedules',
        'protocols',
        'protocol_levels',
        'protocol_skills',
        'protocol_items',
        'protocol_statuses',
        'patient_assessments',
        'patient_assessment_items',
        'therapeutic_plans',
        'plan_items',
        'session_evolutions',
        'patient_posts',
        'patient_milestones',
    ];

    public function run()
    {
        if (ENVIRONMENT === 'production' && !in_array('--force', $_SERVER['argv'] ?? [])) {
            echo "Bloqueado.\n";

            return;
        }

        if (in_array('--reset', $_SERVER['argv'] ?? [])) {
            $this->resetDemoTenant();
        }

        $tenantId = $this->demoTenantId();

        if ($tenantId === null) {
            $faker = Factory::create('pt_BR');
            $faker->seed(123456);

            $tenantId      = $this->seedTenant();
            $unitId        = $this->seedUnit($tenantId);
            $roomIds       = $this->seedRooms($tenantId, $unitId);
            $professionals = $this->seedProfessionals($tenantId, $unitId, $faker);
            $patientIds    = $this->seedPatients($tenantId, $unitId, $faker);
            $appointments  = $this->seedAppointments($tenantId, $unitId, $roomIds, $professionals, $patientIds, $faker);
            $this->seedEvolutions($tenantId, $appointments);

            $this->ensureAdmin($tenantId, $unitId);

            echo 'Dados de demonstração criados com sucesso.' . PHP_EOL;
        } else {
            $this->ensureAdmin($tenantId, $this->demoUnitId($tenantId));

            echo 'Dados de demonstração já existiam; usuário admin garantido.' . PHP_EOL;
        }

        echo 'Login admin: ' . self::ADMIN_EMAIL . ' / password' . PHP_EOL;
        echo 'Login profissional: prof1@mindtrack.com / password' . PHP_EOL;
    }

    private function resetDemoTenant(): void
    {
        $tenantId = $this->demoTenantId();

        if ($tenantId === null) {
            return;
        }

        // role_user nao possui tenant_id; remove antes de apagar os usuarios
        // para nao deixar vinculos orfaos quando as FKs estiverem desligadas.
        $userIds = array_column(
            $this->db->table('users')
                ->select('id')
                ->where('tenant_id', $tenantId)
                ->get()
                ->getResultArray(),
            'id'
        );

        if ($userIds !== []) {
            $this->db->table('role_user')->whereIn('user_id', $userIds)->delete();
        }

        // Desliga a checagem de FKs para contornar as restricoes RESTRICT
        // (ex: session_evolutions.appointment_id -> appointments).
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (self::TENANT_TABLES as $table) {
                $this->db->table($table)->where('tenant_id', $tenantId)->delete();
            }

            $this->db->table('tenants')->where('id', $tenantId)->delete();
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        }

        echo 'Tenant de demonstração removido.' . PHP_EOL;
    }

    private function demoTenantId(): ?int
    {
        $tenant = $this->db->table('tenants')->where('slug', self::DEMO_SLUG)->get()->getRowArray();

        return $tenant !== null ? (int) $tenant['id'] : null;
    }

    private function demoUnitId(int $tenantId): int
    {
        $unit = $this->db->table('units')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        if ($unit === null) {
            throw new RuntimeException('Unidade de demonstração não encontrada.');
        }

        return (int) $unit['id'];
    }

    private function seedTenant(): int
    {
        $moduleIds = array_map('intval', array_column((new ModuleModel())->findAll(), 'id'));

        $tenantId = (new TenantModel())->createTenantWithBaseModules([
            'name'      => 'Clínica Teste',
            'slug'      => self::DEMO_SLUG,
            'cnpj'      => null,
            'email'     => 'contato@clinica-teste.com',
            'phone'     => null,
            'is_active' => 1,
        ], $moduleIds);

        if ($tenantId === false) {
            throw new RuntimeException('Falha ao criar o tenant de demonstração.');
        }

        return (int) $tenantId;
    }

    private function seedUnit(int $tenantId): int
    {
        $id = (new UnitModel())->insert([
            'tenant_id' => $tenantId,
            'name'      => 'Matriz - Clínica Teste',
            'cnpj'      => null,
            'is_active' => 1,
        ]);

        if ($id === false) {
            throw new RuntimeException('Falha ao criar a unidade de demonstração.');
        }

        return (int) $id;
    }

    private function seedRooms(int $tenantId, int $unitId): array
    {
        $roomModel = new RoomModel();
        $ids       = [];

        foreach (['Sala 1', 'Sala 2'] as $name) {
            $id = $roomModel->insert([
                'tenant_id' => $tenantId,
                'name'      => $name,
                'unit_id'   => $unitId,
            ]);

            if ($id === false) {
                throw new RuntimeException('Falha ao criar a sala ' . $name . '.');
            }

            $ids[] = (int) $id;
        }

        return $ids;
    }

    private function seedProfessionals(int $tenantId, int $unitId, Generator $faker): array
    {
        $userModel     = new UserModel();
        $roleUserModel = new RoleUserModel();

        $professionalRoleId = $this->roleIdBySlug($tenantId, 'profissional');

        $professionalIds = [];

        for ($i = 1; $i <= 4; $i++) {
            $id = $userModel->insert([
                'tenant_id'      => $tenantId,
                'unit_id'        => $unitId,
                'name'           => $faker->name(),
                'email'          => 'prof' . $i . '@mindtrack.com',
                'password'       => 'password',
                'is_super_admin' => 0,
                'is_active'      => 1,
                'agenda_order'   => $i - 1,
            ]);

            if ($id === false) {
                throw new RuntimeException(
                    'Falha ao criar o profissional prof' . $i . ': ' . implode(' ', $userModel->errors())
                );
            }

            $roleUserModel->syncRoles((int) $id, [$professionalRoleId]);
            $professionalIds[] = (int) $id;
        }

        return $professionalIds;
    }

    private function roleIdBySlug(int $tenantId, string $slug): int
    {
        foreach ((new RoleModel())->rolesForTenant($tenantId) as $role) {
            if ($role['slug'] === $slug) {
                return (int) $role['id'];
            }
        }

        throw new RuntimeException('Cargo "' . $slug . '" não encontrado para o tenant de demonstração.');
    }

    private function ensureAdmin(int $tenantId, int $unitId): void
    {
        $userModel = new UserModel();

        if ($userModel->emailExists(self::ADMIN_EMAIL)) {
            return;
        }

        $adminRoleId = $this->roleIdBySlug($tenantId, 'admin');

        $id = $userModel->insert([
            'tenant_id'      => $tenantId,
            'unit_id'        => $unitId,
            'name'           => 'Admin Teste',
            'email'          => self::ADMIN_EMAIL,
            'password'       => 'password',
            'is_super_admin' => 0,
            'is_active'      => 1,
            'agenda_order'   => $userModel->nextAgendaOrder($tenantId),
        ]);

        if ($id === false) {
            throw new RuntimeException('Falha ao criar o usuário admin: ' . implode(' ', $userModel->errors()));
        }

        (new RoleUserModel())->syncRoles((int) $id, [$adminRoleId]);
    }

    private function seedPatients(int $tenantId, int $unitId, Generator $faker): array
    {
        $patientModel = new PatientModel();
        $palette      = PatientColor::getPalette();

        $ids = [];

        for ($i = 0; $i < 30; $i++) {
            $id = $patientModel->insert([
                'tenant_id'     => $tenantId,
                'unit_id'       => $unitId,
                'name'          => $faker->name(),
                'birth_date'    => $faker->dateTimeBetween('-18 years', '-3 years')->format('Y-m-d'),
                'cpf'           => $faker->cpf(false),
                'sex'           => $faker->randomElement(['M', 'F']),
                'is_active'     => 1,
                'diagnosis'     => null,
                'support_level' => null,
                'color'         => $palette[$i % count($palette)],
            ]);

            if ($id === false) {
                throw new RuntimeException(
                    'Falha ao criar o paciente #' . $i . ': ' . implode(' ', $patientModel->errors())
                );
            }

            $ids[] = (int) $id;
        }

        return $ids;
    }

    private function seedAppointments(
        int $tenantId,
        int $unitId,
        array $roomIds,
        array $professionalIds,
        array $patientIds,
        Generator $faker
    ): array {
        $appointmentModel = new AppointmentModel();

        // Distribuicao fixa garante pelo menos 20 'realizado' para as evolucoes.
        $statusPool = array_merge(
            array_fill(0, 30, 'realizado'),
            array_fill(0, 45, 'agendado'),
            array_fill(0, 25, 'falta_paciente')
        );
        $statusPool = $faker->shuffle($statusPool);

        $appointments = [];
        $usedSlots    = [];
        $today        = new DateTimeImmutable('today');

        for ($i = 0; $i < 100; $i++) {
            // Evita dois agendamentos do mesmo profissional no mesmo dia/horario.
            do {
                $professionalId = $faker->randomElement($professionalIds);
                $dayOffset      = $faker->numberBetween(0, 15);
                $hour           = $faker->numberBetween(8, 18);
                $minute         = $hour === 18 ? 0 : $faker->randomElement([0, 30]);
                $slotKey        = $professionalId . '|' . $dayOffset . '|' . $hour . ':' . $minute;
            } while (isset($usedSlots[$slotKey]));

            $usedSlots[$slotKey] = true;

            $patientId = $faker->randomElement($patientIds);
            $start     = $today->modify('+' . $dayOffset . ' days')->setTime($hour, $minute, 0);
            $end       = $start->modify('+50 minutes');
            $status    = $statusPool[$i];

            $id = $appointmentModel->insert([
                'tenant_id'       => $tenantId,
                'patient_id'      => $patientId,
                'professional_id' => $professionalId,
                'room_id'         => $faker->randomElement($roomIds),
                'unit_id'         => $unitId,
                'start_time'      => $start->format('Y-m-d H:i:s'),
                'end_time'        => $end->format('Y-m-d H:i:s'),
                'status'          => $status,
                'is_recurring'    => 0,
                'notes'           => null,
            ]);

            if ($id === false) {
                throw new RuntimeException(
                    'Falha ao criar o agendamento #' . $i . ': ' . implode(' ', $appointmentModel->errors())
                );
            }

            $appointments[] = [
                'id'              => (int) $id,
                'status'          => $status,
                'patient_id'      => (int) $patientId,
                'professional_id' => (int) $professionalId,
            ];
        }

        return $appointments;
    }

    private function seedEvolutions(int $tenantId, array $appointments): void
    {
        $evolutionModel = new SessionEvolutionModel();

        $realized = array_slice(array_values(array_filter(
            $appointments,
            static fn (array $appointment): bool => $appointment['status'] === 'realizado'
        )), 0, 20);

        $texts = [
            'Paciente participativo e engajado durante toda a sessão.',
            'Evolução satisfatória nos objetivos trabalhados hoje.',
            'Apresentou boa resposta aos estímulos propostos.',
            'Pequena resistência no início, mas concluiu as atividades.',
            'Bom contato visual e interação social durante a sessão.',
        ];

        foreach ($realized as $index => $appointment) {
            $id = $evolutionModel->insert([
                'tenant_id'       => $tenantId,
                'appointment_id'  => $appointment['id'],
                'patient_id'      => $appointment['patient_id'],
                'professional_id' => $appointment['professional_id'],
                'evolution_text'  => $texts[$index % count($texts)],
            ]);

            if ($id === false) {
                throw new RuntimeException(
                    'Falha ao criar a evolução: ' . implode(' ', $evolutionModel->errors())
                );
            }
        }
    }
}
