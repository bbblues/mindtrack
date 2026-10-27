<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $data = [
            'title' => 'Dashboard',
            'user'  => [
                'name'           => (string) (session('user_name') ?? 'Usuario'),
                'is_super_admin' => (bool) session('is_super_admin'),
            ],
        ];

        return view('dashboard', $data);
    }
}
