<?php

namespace App\Modules\Patients\Models;

use App\Models\BaseModel;
use App\Modules\Patients\Libraries\PatientColor;

class PatientModel extends BaseModel
{
    protected $table         = 'patients';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'tenant_id',
        'name',
        'birth_date',
        'cpf',
        'sex',
        'is_active',
        'diagnosis',
        'support_level',
        'allergies',
        'unit_id',
        'health_insurance_id',
        'insurance_number',
        'tags',
        'zip_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'color',
    ];

    protected $validationRules = [
        'name'             => 'required|max_length[255]',
        'birth_date'       => 'required|valid_date',
        'unit_id'          => 'required|integer',
        'cpf'              => 'permit_empty|exact_length[11]',
        'sex'              => 'permit_empty|in_list[M,F,O]',
        'is_active'        => 'permit_empty|in_list[0,1]',
        'diagnosis'        => 'permit_empty|max_length[255]',
        'support_level'    => 'permit_empty|max_length[50]',
        'allergies'        => 'permit_empty',
        'health_insurance_id' => 'permit_empty|integer',
        'insurance_number' => 'permit_empty|max_length[50]',
        'tags'             => 'permit_empty|max_length[255]',
        'zip_code'         => 'permit_empty|exact_length[8]',
        'street'           => 'permit_empty|max_length[255]',
        'number'           => 'permit_empty|max_length[20]',
        'complement'       => 'permit_empty|max_length[255]',
        'neighborhood'     => 'permit_empty|max_length[255]',
        'city'             => 'permit_empty|max_length[255]',
        'state'            => 'permit_empty|exact_length[2]|alpha',
        'color'            => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'O nome do paciente e obrigatorio.',
        ],
        'birth_date' => [
            'required'    => 'A data de nascimento e obrigatoria.',
            'valid_date'  => 'Informe uma data de nascimento valida.',
        ],
        'unit_id' => [
            'required' => 'A unidade e obrigatoria.',
            'integer'  => 'Selecione uma unidade valida.',
        ],
        'health_insurance_id' => [
            'integer' => 'Selecione um convenio valido.',
        ],
        'cpf' => [
            'exact_length' => 'O CPF deve conter 11 digitos.',
        ],
        'sex' => [
            'in_list' => 'Sexo invalido.',
        ],
        'zip_code' => [
            'exact_length' => 'O CEP deve conter 8 digitos.',
        ],
        'state' => [
            'exact_length' => 'A UF deve conter 2 letras.',
            'alpha'        => 'A UF deve conter apenas letras.',
        ],
    ];

    /**
     * Lista os pacientes paginados incluindo o nome da unidade e do convenio
     * (JOIN nos catalogos) para exibicao na tabela.
     *
     * @param int|null $professionalId Quando null, lista TODOS os pacientes do
     *                                 tenant. Quando informado, filtra apenas os
     *                                 pacientes vinculados a esse profissional
     *                                 (modo "Meus Pacientes") via JOIN simples,
     *                                 sem query adicional.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paginateWithCatalogs(?string $search = null, bool $showInactive = false, ?int $professionalId = null): array
    {
        $this
            ->select('patients.*, units.name AS unit_name, health_insurances.name AS insurance_name')
            ->join('units', 'units.id = patients.unit_id', 'left')
            ->join('health_insurances', 'health_insurances.id = patients.health_insurance_id', 'left');

        if ($professionalId !== null) {
            $this
                ->join('patient_professionals', 'patient_professionals.patient_id = patients.id')
                ->where('patient_professionals.user_id', $professionalId);
        }

        $search = trim((string) $search);

        if ($search !== '') {
            // Busca Global: com termo ativo, ignora o filtro de inativos.
            $normalizedCpf = preg_replace('/\D/', '', $search) ?: $search;

            $this
                ->groupStart()
                ->like('patients.name', $search)
                ->orLike('patients.cpf', $normalizedCpf)
                ->groupEnd();
        } elseif (! $showInactive) {
            // Padrao: lista apenas pacientes ativos.
            $this->where('patients.is_active', 1);
        }

        return $this->orderBy('patients.name', 'ASC')->paginate(50);
    }

    /**
     * Busca rapida (type-ahead) de pacientes do tenant por nome ou CPF.
     *
     * Usada pela Busca Rapida global (Command Palette) do layout.
     * Segue o mesmo comportamento da listagem: com termo ativo, busca
     * tambem pacientes inativos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function quickSearch(string $query, int $limit = 8, ?int $professionalId = null): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $normalizedCpf = preg_replace('/\D/', '', $query) ?: $query;

        $this
            ->select('patients.id, patients.name, patients.cpf, patients.birth_date, patients.color, patients.is_active, units.name AS unit_name')
            ->join('units', 'units.id = patients.unit_id', 'left');

        if ($professionalId !== null) {
            $this
                ->join('patient_professionals', 'patient_professionals.patient_id = patients.id')
                ->where('patient_professionals.user_id', $professionalId);
        }

        return $this
            ->groupStart()
            ->like('patients.name', $query)
            ->orLike('patients.cpf', $normalizedCpf)
            ->groupEnd()
            ->orderBy('patients.name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Lista os pacientes vinculados a um profissional (para o filtro "own").
     *
     * @return array<int, array<string, mixed>>
     */
    public function forProfessional(int $userId): array
    {
        return $this
            ->select('patients.*')
            ->join('patient_professionals', 'patient_professionals.patient_id = patients.id')
            ->where('patient_professionals.user_id', $userId)
            ->where('patient_professionals.deleted_at', null)
            ->orderBy('patients.name', 'ASC')
            ->findAll();
    }

    /**
     * Atribui uma cor da paleta ao novo paciente do tenant.
     *
     * Prioriza cores ainda nao usadas por pacientes ATIVOS do tenant.
     * Quando todas as 30 estiverem em uso, reinicia o ciclo escolhendo
     * a cor menos usada (empate resolvido de forma aleatoria).
     */
    public function assignColorForTenant(?int $tenantId): string
    {
        $palette = PatientColor::getPalette();
        $usage   = [];

        if ($tenantId !== null) {
            $rows = $this->db->table($this->table)
                ->select('color')
                ->where('tenant_id', $tenantId)
                ->where('is_active', 1)
                ->where('color IS NOT NULL')
                ->where('deleted_at IS NULL')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $color = strtoupper((string) ($row['color'] ?? ''));
                if ($color === '') {
                    continue;
                }

                $usage[$color] = ($usage[$color] ?? 0) + 1;
            }
        }

        $available = array_values(array_filter(
            $palette,
            static fn (string $color): bool => ! array_key_exists(strtoupper($color), $usage)
        ));

        if ($available !== []) {
            return $available[array_rand($available)];
        }

        // Todas as cores estao em uso: reinicia o ciclo pela menos usada.
        $minCount  = min($usage);
        $leastUsed = array_values(array_filter(
            $palette,
            static fn (string $color): bool => ($usage[strtoupper($color)] ?? 0) === $minCount
        ));

        return $leastUsed[array_rand($leastUsed)];
    }
}
