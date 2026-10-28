<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restringe o acesso de uma rota aos cargos (slugs) permitidos.
 *
 * Uso na rota: ['filter' => 'role:admin,gestor']
 *
 * O usuario precisa possuir ao menos UM dos cargos informados em
 * session('user_role_slugs'). Caso contrario, e redirecionado para o
 * Dashboard com uma flash message de erro.
 *
 * O Super Admin (is_super_admin = 1) sempre passa, pois nao possui
 * cargos vinculados (user_role_slugs vazio) e gerencia o SaaS.
 */
class RoleFilter implements FilterInterface
{
    /**
     * @param list<string>|null $arguments
     *
     * @return RequestInterface|ResponseInterface|string|null
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session('user_id') === null) {
            return redirect()->to('/login');
        }

        // Super Admin gerencia o SaaS e nao possui cargos de tenant.
        if ((int) session('is_super_admin') === 1) {
            return null;
        }

        $allowedRoles = is_array($arguments) ? $arguments : [];
        $userRoles    = (array) session('user_role_slugs');

        if ($allowedRoles === [] || array_intersect($allowedRoles, $userRoles) === []) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado. Você não tem permissão para acessar esta área.');
        }

        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
