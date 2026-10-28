<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class AuthController extends BaseController
{
    /**
     * Exibe a tela de login.
     */
    public function index(): string
    {
        return view('App\Modules\Core\Views\auth\login');
    }

    /**
     * Autentica o usuario (email/senha), cria a sessao e redireciona
     * para o Dashboard.
     */
    public function authenticate(): RedirectResponse
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email    = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();

        if ($user === null || ! isset($user['password']) || ! password_verify($password, $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Credenciais invalidas.');
        }

        if ((int) ($user['is_active'] ?? 0) !== 1) {
            return redirect()->back()->withInput()->with('error', 'Usuario inativo. Contate o administrador.');
        }

        session()->set([
            'user_id'        => (int) $user['id'],
            'tenant_id'      => $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null,
            'is_super_admin' => (int) $user['is_super_admin'],
            'user_name'      => (string) $user['name'],
            'user_role_slugs' => $userModel->roleSlugsForUser((int) $user['id']),
        ]);
        session()->regenerate();

        return redirect()->to('/dashboard');
    }

    /**
     * Destroi a sessao e volta para a tela de login.
     */
    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('/login');
    }
}
