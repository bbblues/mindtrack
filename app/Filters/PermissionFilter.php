<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restringe o acesso de uma rota a uma ou mais permissões (slugs).
 *
 * Uso na rota: ['filter' => 'permission:patients.view.any,patients.view.own']
 *
 * A checagem é feita com OR (basta possuir uma das permissões informadas).
 * O Super Admin sempre passa. O escopo "own" NÃO é resolvido aqui (ele
 * precisa do registro concreto), sendo tratado no controller via can_own().
 */
class PermissionFilter implements FilterInterface
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

        if ((int) session('is_super_admin') === 1) {
            return null;
        }

        $permissions = is_array($arguments) ? $arguments : [];

        foreach ($permissions as $permission) {
            if (service('authorization')->can($permission)) {
                return null;
            }
        }

        return redirect()->to('/dashboard')->with('error', 'Acesso negado. Você não tem permissão para acessar esta área.');
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
