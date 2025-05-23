<?php

namespace App\Controllers;

use App\Models\GradeModel;

class AdminTimer extends BaseController
{
    public function index()
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin') {
            return redirect()->to(base_url('/'));
        }

        $gradeModel = new GradeModel();

        $grades = $gradeModel->findAll();

        return view('admin/timer', [
            'pageTitle' => 'Timer Settings',
            'grades' => $grades,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }

    public function save()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        
        $timers = $this->request->getPost('timers');
        $timers = json_decode($timers, true);

        $gradeModel = new GradeModel();

        foreach ($timers as $timer) {
            $gradeModel->set('timer_minutes', $timer['timer'])->update($timer['id']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Timer settings saved successfully']);
    }
}
