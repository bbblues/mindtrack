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
use App\Modules\Patients\Models\PatientModel;
use App\Modules\Protocols\Models\ProtocolItemModel;
use App\Modules\Protocols\Models\ProtocolLevelModel;
use App\Modules\Protocols\Models\ProtocolModel;
use App\Modules\Protocols\Models\ProtocolSkillModel;
use CodeIgniter\Database\Seeder;
use DateTimeImmutable;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    private const DEMO_SLUG = 'clinica-teste';

    public function run()
    {
        if ($this->demoTenantExists()) {
            echo "Demo data já existe (tenant '" . self::DEMO_SLUG . "'). Nada a fazer." . PHP_EOL;

            return;
        }

        $tenantId     = $this->seedTenant();
        $unitId       = $this->seedUnit($tenantId);
        $roomIds      = $this->seedRooms($tenantId, $unitId);
        $users        = $this->seedUsers($tenantId, $unitId);
        $patientIds   = $this->seedPatients($tenantId, $unitId);
        $this->seedProtocol($tenantId);
        $appointments = $this->seedAppointments($tenantId, $unitId, $roomIds, $users['professionals'], $patientIds);
        $this->seedEvolutions($tenantId, $appointments);

        echo 'Demo data criada com sucesso.' . PHP_EOL;
        echo 'Login: admin@clinica-teste.com / password' . PHP_EOL;
    }

    private function demoTenantExists(): bool
    {
        return $this->db->table('tenants')->where('slug', self::DEMO_SLUG)->get()->getRowArray() !== null;
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

    private function seedUsers(int $tenantId, int $unitId): array
    {
        $userModel     = new UserModel();
        $roleUserModel = new RoleUserModel();

        $roleBySlug = [];
        foreach ((new RoleModel())->rolesForTenant($tenantId) as $role) {
            $roleBySlug[$role['slug']] = (int) $role['id'];
        }

        $adminId = $this->createUser($userModel, $tenantId, $unitId, 'Admin Clínica Teste', 'admin@clinica-teste.com');
        $roleUserModel->syncRoles($adminId, [$roleBySlug['admin'] ?? 0]);

        $professionals = [
            ['Camila Nogueira', 'camila.nogueira@clinica-teste.com'],
            ['Rafael Barros', 'rafael.barros@clinica-teste.com'],
            ['Juliana Castro', 'juliana.castro@clinica-teste.com'],
            ['Bruno Mendes', 'bruno.mendes@clinica-teste.com'],
            ['Patrícia Araújo', 'patricia.araujo@clinica-teste.com'],
        ];

        $professionalIds = [];
        foreach ($professionals as [$name, $email]) {
            $id = $this->createUser($userModel, $tenantId, $unitId, $name, $email);
            $roleUserModel->syncRoles($id, [$roleBySlug['profissional'] ?? 0]);
            $professionalIds[] = $id;
        }

        return ['admin' => $adminId, 'professionals' => $professionalIds];
    }

    private function createUser(UserModel $userModel, int $tenantId, int $unitId, string $name, string $email): int
    {
        $id = $userModel->insert([
            'tenant_id'      => $tenantId,
            'unit_id'        => $unitId,
            'name'           => $name,
            'email'          => $email,
            'password'       => 'password',
            'is_super_admin' => 0,
            'is_active'      => 1,
        ]);

        if ($id === false) {
            throw new RuntimeException('Falha ao criar o usuário ' . $email . ': ' . implode(' ', $userModel->errors()));
        }

        return (int) $id;
    }

    private function seedPatients(int $tenantId, int $unitId): array
    {
        $patientModel = new PatientModel();

        $patients = [
            ['João Pedro Almeida', 'M', 'Nível 1', '2018-05-14'],
            ['Maria Clara Souza', 'F', 'Nível 2', '2016-09-02'],
            ['Enzo Gabriel Oliveira', 'M', 'Nível 1', '2019-01-23'],
            ['Valentina Lima', 'F', 'Nível 3', '2017-11-30'],
            ['Davi Lucas Santos', 'M', 'Nível 2', '2020-03-18'],
            ['Alice Ferreira', 'F', 'Nível 1', '2018-07-09'],
            ['Miguel Rocha', 'M', 'Nível 2', '2015-12-01'],
            ['Sophia Cardoso', 'F', 'Nível 1', '2021-06-25'],
            ['Arthur Henrique Ribeiro', 'M', 'Nível 3', '2016-04-11'],
            ['Helena Martins', 'F', 'Nível 1', '2019-10-07'],
        ];

        $ids = [];
        foreach ($patients as $i => [$name, $sex, $supportLevel, $birthDate]) {
            $id = $patientModel->insert([
                'tenant_id'           => $tenantId,
                'name'                => $name,
                'birth_date'          => $birthDate,
                'cpf'                 => str_pad((string) (10000000000 + $i), 11, '0', STR_PAD_LEFT),
                'sex'                 => $sex,
                'is_active'           => 1,
                'diagnosis'           => 'TEA',
                'support_level'       => $supportLevel,
                'unit_id'             => $unitId,
                'health_insurance_id' => null,
            ]);

            if ($id === false) {
                throw new RuntimeException('Falha ao criar o paciente ' . $name . ': ' . implode(' ', $patientModel->errors()));
            }

            $ids[] = (int) $id;
        }

        return $ids;
    }

    private function seedProtocol(int $tenantId): int
    {
        $protocolId = (new ProtocolModel())->insert([
            'tenant_id'   => $tenantId,
            'name'        => 'Protocolo ABA - Desenvolvimento Infantil',
            'description' => 'Protocolo completo de demonstração (2 níveis, 4 habilidades e 20 itens).',
            'is_active'   => 1,
        ]);

        if ($protocolId === false) {
            throw new RuntimeException('Falha ao criar o protocolo de demonstração.');
        }

        $structure = [
            ['Nível 1 - Fundamentos', [
                ['Atenção e Imitação', [
                    'Mantém contato visual por 3 segundos',
                    'Imita movimentos motores simples',
                    'Imita sons e vocalizações',
                    'Responde ao próprio nome',
                    'Segue instrução de um passo',
                ]],
                ['Comunicação Receptiva', [
                    'Aponta para objeto nomeado',
                    'Identifica partes do corpo',
                    'Segue instrução de dois passos',
                    'Pareia objetos idênticos',
                    'Categoriza objetos por cor',
                ]],
            ]],
            ['Nível 2 - Avançado', [
                ['Comunicação Expressiva', [
                    'Pede item desejado com gesto',
                    'Vocaliza para chamar atenção',
                    'Nomeia figuras simples',
                    'Usa frases de duas palavras',
                    'Responde perguntas simples',
                ]],
                ['Habilidades Sociais', [
                    'Compartilha brinquedo com adulto',
                    'Aguarda a vez em atividade',
                    'Cumprimenta ao chegar',
                    'Brinca de faz de conta',
                    'Mantém interação por 2 minutos',
                ]],
            ]],
        ];

        $levelModel = new ProtocolLevelModel();
        $skillModel = new ProtocolSkillModel();
        $itemModel  = new ProtocolItemModel();

        $levelOrder = 1;
        foreach ($structure as [$levelName, $skills]) {
            $levelId = $levelModel->insert([
                'tenant_id'   => $tenantId,
                'protocol_id' => (int) $protocolId,
                'name'        => $levelName,
                'sort_order'  => $levelOrder,
            ]);

            if ($levelId === false) {
                throw new RuntimeException('Falha ao criar o nível ' . $levelName . '.');
            }

            $skillOrder = 1;
            foreach ($skills as [$skillName, $items]) {
                $skillId = $skillModel->insert([
                    'tenant_id'  => $tenantId,
                    'level_id'   => (int) $levelId,
                    'name'       => $skillName,
                    'sort_order' => $skillOrder,
                ]);

                if ($skillId === false) {
                    throw new RuntimeException('Falha ao criar a habilidade ' . $skillName . '.');
                }

                $itemOrder = 1;
                foreach ($items as $itemName) {
                    $itemId = $itemModel->insert([
                        'tenant_id'  => $tenantId,
                        'skill_id'   => (int) $skillId,
                        'name'       => $itemName,
                        'sort_order' => $itemOrder,
                    ]);

                    if ($itemId === false) {
                        throw new RuntimeException('Falha ao criar o item ' . $itemName . '.');
                    }

                    $itemOrder++;
                }

                $skillOrder++;
            }

            $levelOrder++;
        }

        return (int) $protocolId;
    }

    private function seedAppointments(int $tenantId, int $unitId, array $roomIds, array $professionalIds, array $patientIds): array
    {
        $appointmentModel = new AppointmentModel();
        $statuses         = ['realizado', 'falta_paciente', 'agendado'];
        $appointments     = [];

        for ($i = 0; $i < 50; $i++) {
            $status     = $statuses[$i % 3];
            $offsetDays = ($i * 11) % 90;
            $hour       = 8 + ($i % 8);

            $start = (new DateTimeImmutable('today'))
                ->modify('-' . $offsetDays . ' days')
                ->setTime($hour, 0, 0);
            $end   = $start->modify('+50 minutes');

            $id = $appointmentModel->insert([
                'tenant_id'       => $tenantId,
                'patient_id'      => $patientIds[$i % count($patientIds)],
                'professional_id' => $professionalIds[$i % count($professionalIds)],
                'room_id'         => $roomIds[$i % count($roomIds)],
                'unit_id'         => $unitId,
                'start_time'      => $start->format('Y-m-d H:i:s'),
                'end_time'        => $end->format('Y-m-d H:i:s'),
                'status'          => $status,
                'is_recurring'    => 0,
                'notes'           => null,
            ]);

            if ($id === false) {
                throw new RuntimeException('Falha ao criar o agendamento #' . $i . ': ' . implode(' ', $appointmentModel->errors()));
            }

            $appointments[] = [
                'id'              => (int) $id,
                'status'          => $status,
                'patient_id'      => $patientIds[$i % count($patientIds)],
                'professional_id' => $professionalIds[$i % count($professionalIds)],
            ];
        }

        return $appointments;
    }

    private function seedEvolutions(int $tenantId, array $appointments): void
    {
        $evolutionModel = new SessionEvolutionModel();
        $texts = [
            'Paciente participativo e engajado durante toda a sessão.',
            'Evolução satisfatória nos objetivos trabalhados hoje.',
            'Apresentou boa resposta aos estímulos propostos.',
            'Pequena resistência no início, mas concluiu as atividades.',
        ];

        $created = 0;
        foreach ($appointments as $appointment) {
            if ($appointment['status'] !== 'realizado') {
                continue;
            }

            $id = $evolutionModel->insert([
                'tenant_id'       => $tenantId,
                'appointment_id'  => $appointment['id'],
                'patient_id'      => $appointment['patient_id'],
                'professional_id' => $appointment['professional_id'],
                'evolution_text'  => $texts[$created % count($texts)],
            ]);

            if ($id === false) {
                throw new RuntimeException('Falha ao criar a evolução: ' . implode(' ', $evolutionModel->errors()));
            }

            $created++;
        }
    }
}
