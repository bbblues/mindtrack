<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Garante que apenas usuarios com is_super_admin = 1 acessem
 * as rotas de Gestao do SaaS.
 */
class SuperAdminFilter implements FilterInterface
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

        if ((int) session('is_super_admin') !== 1) {
            return redirect()->to('/dashboard')->with('error', 'Acesso restrito ao Super Admin.');
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
