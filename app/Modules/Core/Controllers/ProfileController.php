<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class ProfileController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Exibe o formulario de perfil do usuario logado.
     */
    public function index(): string|RedirectResponse
    {
        $userId = session('user_id');

        if (! is_numeric($userId)) {
            return redirect()->to(site_url('login'));
        }

        $user = $this->userModel->find((int) $userId);

        if ($user === null) {
            return redirect()->to(site_url('login'))->with('error', 'Usuário não encontrado.');
        }

        return view('App\Modules\Core\Views\profile\index', [
            'title' => 'Meu Perfil',
            'user'  => $user,
        ]);
    }

    /**
     * Atualiza nome, e-mail e (opcionalmente) senha do usuario logado.
     */
    public function update(): RedirectResponse
    {
        $userId = (int) session('user_id');

        $user = $this->userModel->find($userId);

        if ($user === null) {
            return redirect()->to(site_url('login'))->with('error', 'Usuário não encontrado.');
        }

        $data = $this->request->getPost(['name', 'email', 'current_password', 'password', 'password_confirm']);

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value      = trim($value);
                $data[$key] = $value === '' ? null : $value;
            }
        }

        // O {id} do is_unique usa o valor de "id" presente em $data.
        $data['id'] = $userId;

        $rules = [
            'id'               => 'permit_empty|integer',
            'name'             => 'required|max_length[255]',
            'email'            => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
            'current_password' => 'required',
            'password'         => 'permit_empty|min_length[8]',
            'password_confirm' => 'matches[password]',
        ];

        $messages = [
            'name' => [
                'required' => 'Informe o seu nome.',
            ],
            'email' => [
                'required'    => 'Informe o seu e-mail.',
                'valid_email' => 'Informe um e-mail válido.',
                'is_unique'   => 'Este e-mail já está em uso.',
            ],
            'current_password' => [
                'required' => 'Informe a senha atual para confirmar as alterações.',
            ],
            'password' => [
                'min_length' => 'A nova senha deve ter no mínimo 8 caracteres.',
            ],
            'password_confirm' => [
                'matches' => 'A confirmação da nova senha não confere.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (! isset($user['password']) || ! password_verify((string) $data['current_password'], $user['password'])) {
            return redirect()->back()->withInput()->with('errors', ['current_password' => 'A senha atual informada está incorreta.']);
        }

        $payload = [
            'name'  => (string) $data['name'],
            'email' => (string) $data['email'],
        ];

        if ($data['password'] !== null && $data['password'] !== '') {
            $payload['password'] = $data['password'];
        }

        // A unicidade do e-mail ja foi validada acima com o {id} resolvido.
        // Removemos o is_unique da validacao interna do Model para que o
        // update nao rode a regra sem o id (o que reprovaria o proprio e-mail).
        $this->userModel->setValidationRule('email', 'required|valid_email|max_length[255]');

        if (! $this->userModel->update($userId, $payload)) {
            return redirect()->back()->withInput()->with('errors', $this->userModel->errors());
        }

        session()->set('user_name', (string) $data['name']);

        return redirect()->to(site_url('profile'))->with('success', 'Perfil atualizado com sucesso.');
    }
}
