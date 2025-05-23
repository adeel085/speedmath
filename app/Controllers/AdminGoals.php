<?php

namespace App\Controllers;

use App\Models\GradeModel;
use App\Models\GradeWeeklyGoalModel;

class AdminGoals extends BaseController
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

        return view('admin/goals', [
            'pageTitle' => 'Goals',
            'grades' => $grades,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }

    public function fetchWeeklyGoals() {

        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $gradeId = $this->request->getPost('grade_id');

        $gradeWeeklyGoalModel = new GradeWeeklyGoalModel();
        $goals = $gradeWeeklyGoalModel->where('grade_id', $gradeId)->orderBy('week_start_date', 'ASC')->findAll();
        
        return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'data' => $goals]);
    }

    public function addWeeklyGoal() {

        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $gradeId = $this->request->getPost('grade_id');
        $week = $this->request->getPost('week');
        $goalPoints = (int)$this->request->getPost('goal_points');

        if (!$gradeId || !$week || !$goalPoints) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing required fields']);
        }

        if ($goalPoints < 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Goal points must be positive']);
        }        

        $gradeWeeklyGoalModel = new GradeWeeklyGoalModel();
        $gradeWeeklyGoalModel->insert([
            'grade_id' => $gradeId,
            'week_start_date' => $week,
            'goal_points' => $goalPoints
        ]);

        return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Weekly goal added successfully']);
    }

    public function deleteWeeklyGoal() {

        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $gradeId = $this->request->getPost('grade_id');
        $week = $this->request->getPost('week');

        if (!$gradeId || !$week) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing required fields']);
        }

        $gradeWeeklyGoalModel = new GradeWeeklyGoalModel();
        $gradeWeeklyGoalModel->where([
            'grade_id' => $gradeId,
            'week_start_date' => $week
        ])->delete();

        return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Weekly goal deleted successfully']);
    }
}
