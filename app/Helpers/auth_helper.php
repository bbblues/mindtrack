<?php

/**
 * Atalhos globais para verificação de permissões.
 *
 * Disponíveis em controllers e views (o helper é carregado no
 * BaseController). Exemplos:
 *
 *   can('patients.view.any')
 *   can_own('patients.edit', $patientId)
 */

if (! function_exists('can')) {
    function can(string $permission): bool
    {
        return service('authorization')->can($permission);
    }
}

if (! function_exists('can_own')) {
    function can_own(string $basePermission, int $patientId): bool
    {
        return service('authorization')->canOwn($basePermission, $patientId);
    }
}

if (! function_exists('can_own_primary')) {
    function can_own_primary(string $basePermission, int $patientId): bool
    {
        return service('authorization')->canOwnPrimary($basePermission, $patientId);
    }
}

if (! function_exists('can_access_appointment')) {
    function can_access_appointment(string $basePermission, array $appointment): bool
    {
        return service('authorization')->canAccessAppointment($basePermission, $appointment);
    }
}
