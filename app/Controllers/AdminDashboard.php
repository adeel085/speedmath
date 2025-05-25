<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AdminDashboard extends BaseController
{
    public function index()
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/'));
        }
        
        return view('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }
}
