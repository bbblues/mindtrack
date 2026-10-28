<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;

class SettingsController extends BaseController
{
    /**
     * Tela central de configuracoes com o grid de cards.
     */
    public function index(): string
    {
        return view('App\Modules\Core\Views\settings\index', [
            'title' => 'Configurações',
        ]);
    }
}
