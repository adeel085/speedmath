<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\GradeModel;
use App\Models\ClassModel;

class AdminOnboarding extends BaseController
{
    public function index()
    {
        if (!$this->user) {
            return redirect()->to(base_url('/'));
        }

        if ($this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/admin'));
        }

        $userModel = new UserModel();
        $gradeModel = new GradeModel();
        $classModel = new ClassModel();

        $students = $userModel->filterByStudentOf($this->user['id'])->countAllResults();

        if ($students > 0) {
            $userModel->updateUserMeta('onboarding_completed', 1, $this->user['id']);
            return redirect()->to(base_url('/admin'));
        }

        $grades = $gradeModel->findAll();
        $classes = $classModel->findAll();

        $onBoardingCompleted = $userModel->getUserMeta('onboarding_completed', $this->user['id'], true);

        if ($onBoardingCompleted == 1) {
            return redirect()->to(base_url('/admin'));
        }

        return view('admin/onboarding', [
            'pageTitle' => 'Admin Onboarding',
            'grades' => $grades,
            'classes' => $classes,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user,
        ]);
    }
}
