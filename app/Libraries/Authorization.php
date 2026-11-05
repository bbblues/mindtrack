<?php

namespace App\Libraries;

use App\Modules\Core\Models\PermissionRoleModel;

/**
 * Camada central de autorização do MindTrack.
 *
 * Oferece `can()`, `canAny()` e `canOwn()` para aplicar as permissões
 * granulares (slug `<modulo>.<acao>[.<escopo>]`) em controllers, filters e
 * views. O Super Admin (dono do SaaS) sempre passa.
 */
class Authorization
{
    /**
     * Cache em memória dos slugs de permissão do usuário logado.
     *
     * @var list<string>|null
     */
    private ?array $permissionSlugs = null;

    /**
     * Verifica se o usuário possui determinada permissão.
     */
    public function can(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionSlugs(), true);
    }

    /**
     * Verifica se o usuário possui ao menos uma das permissões (OR).
     *
     * @param list<string> $permissions
     */
    public function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica uma permissão com escopo "any"/"own" para um paciente.
     *
     * 1. Se possui `<base>.any`, permite.
     * 2. Senão, se possui `<base>.own`, permite apenas quando o usuário
     *    logado está vinculado ao paciente em `patient_professionals`.
     */
    public function canOwn(string $basePermission, int $patientId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->can($basePermission . '.any')) {
            return true;
        }

        if (! $this->can($basePermission . '.own')) {
            return false;
        }

        $userId = session('user_id');

        if (! is_numeric($userId)) {
            return false;
        }

        return $this->isPatientLinkedToUser($patientId, (int) $userId);
    }

    /**
     * Verifica uma permissão com escopo "any"/"own" para um paciente, mas para o
     * escopo "own" exige que o usuário seja o RESPONSÁVEL PRINCIPAL (is_primary).
     *
     * Usado nas ações de gestão de avaliação (iniciar/editar/finalizar), que são
     * restritas ao responsável principal (ou a quem tem o escopo "any").
     */
    public function canOwnPrimary(string $basePermission, int $patientId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->can($basePermission . '.any')) {
            return true;
        }

        if (! $this->can($basePermission . '.own')) {
            return false;
        }

        $userId = session('user_id');

        if (! is_numeric($userId)) {
            return false;
        }

        return $this->isPrimaryProfessional($patientId, (int) $userId);
    }

    /**
     * Verifica se o usuário pode acessar um agendamento com uma permissão de
     * escopo "any"/"own". O "own" considera o profissional responsável OU o
     * vínculo do paciente em `patient_professionals`.
     *
     * @param array<string, mixed> $appointment
     */
    public function canAccessAppointment(string $basePermission, array $appointment): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->can($basePermission . '.any')) {
            return true;
        }

        if (! $this->can($basePermission . '.own')) {
            return false;
        }

        return $this->ownsAppointment($appointment);
    }

    /**
     * Indica se o usuário logado "possui" o agendamento (é o profissional
     * responsável OU está vinculado ao paciente).
     *
     * @param array<string, mixed> $appointment
     */
    public function ownsAppointment(array $appointment): bool
    {
        $userId = session('user_id');

        if (! is_numeric($userId)) {
            return false;
        }

        $userId = (int) $userId;

        if ((int) ($appointment['professional_id'] ?? 0) === $userId) {
            return true;
        }

        return $this->isPatientLinkedToUser((int) ($appointment['patient_id'] ?? 0), $userId);
    }

    /**
     * Indica se um paciente está vinculado a um usuário (profissional).
     */
    public function isPatientLinkedToUser(int $patientId, int $userId): bool
    {
        return db_connect()->table('patient_professionals')
            ->where('patient_id', $patientId)
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    /**
     * Indica se um usuário é o responsável principal (is_primary = true) do paciente.
     */
    public function isPrimaryProfessional(int $patientId, int $userId): bool
    {
        return db_connect()->table('patient_professionals')
            ->where('patient_id', $patientId)
            ->where('user_id', $userId)
            ->where('is_primary', 1)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    /**
     * Slugs de permissão do usuário logado (com cache em memória).
     *
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        if ($this->permissionSlugs !== null) {
            return $this->permissionSlugs;
        }

        $slugs = session('user_permission_slugs');

        if (is_array($slugs) && $slugs !== []) {
            return $this->permissionSlugs = array_values(array_filter(array_map('strval', $slugs)));
        }

        return $this->permissionSlugs = $this->loadPermissionSlugs();
    }

    /**
     * Fallback: carrega as permissões do usuário diretamente do banco
     * (usado quando a sessão ainda não possui `user_permission_slugs`).
     *
     * @return list<string>
     */
    private function loadPermissionSlugs(): array
    {
        $userId = session('user_id');

        if (! is_numeric($userId)) {
            return [];
        }

        return (new PermissionRoleModel())->slugsForUser((int) $userId);
    }

    private function isSuperAdmin(): bool
    {
        return (int) session('is_super_admin') === 1;
    }
}
