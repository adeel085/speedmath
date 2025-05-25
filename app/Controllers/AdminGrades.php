<?php

namespace App\Controllers;

use App\Models\GradeModel;
use App\Models\TopicModel;

class AdminGrades extends BaseController
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
        $topicModel = new TopicModel();

        $grades = $gradeModel->select('name')->groupBy('name')->findAll();

        foreach ($grades as &$grade) {
            $grade['grade_levels'] = $gradeModel->where('name', $grade['name'])->findAll();
        }

        $topics = $topicModel->findAll();

        return view('admin/grades', [
            'pageTitle' => 'Grades',
            'grades' => $grades,
            'topics' => $topics,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }

    public function saveNew()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $name = $this->request->getPost('name');

        if (!$name || trim($name) == '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $gradeModel = new GradeModel();

        if ($gradeModel->where('name', $name)->first()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Grade already exists']);
        }

        $negativeLevelGrade = "- " . $name;
        $positiveLevelGrade = "+ " . $name;

        $gradeModel->insert(['name' => $name, 'grade_level' => $negativeLevelGrade]);
        $gradeModel->insert(['name' => $name, 'grade_level' => $name]);
        $gradeModel->insert(['name' => $name, 'grade_level' => $positiveLevelGrade]);

        $this->session->setFlashdata('status', 'grade_created');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Grade created successfully']);
    }

    public function update()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $gradeName = $this->request->getPost('grade_name');
        $name = $this->request->getPost('new_name');

        if (!$gradeName || !$name || trim($name) == '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $gradeModel = new GradeModel();

        $grade = $gradeModel->where('name', $name)->first();

        if ($grade && $grade['name'] != $gradeName) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Grade with this name already exists']);
        }

        $positiveLevelGrade = "+ " . $name;
        $negativeLevelGrade = "- " . $name;

        $gradeModel->set(['name' => $name, 'grade_level' => $positiveLevelGrade])->where('grade_level', '+ ' . $gradeName)->update();
        $gradeModel->set(['name' => $name, 'grade_level' => $name])->where('grade_level', $gradeName)->update();
        $gradeModel->set(['name' => $name, 'grade_level' => $negativeLevelGrade])->where('grade_level', '- ' . $gradeName)->update();

        $this->session->setFlashdata('status', 'grade_updated');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Grade updated successfully']);
    }

    public function saveSettings()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $gradeLevelId = $this->request->getPost('grade_level_id');
        $numberOfQuestions = $this->request->getPost('number_of_questions');
        $topicId = $this->request->getPost('topic_id');

        if (!$gradeLevelId || !$topicId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $gradeModel = new GradeModel();

        $gradeModel->where('id', $gradeLevelId)->set([
            'topic_id' => $topicId,
            'number_of_questions' => $numberOfQuestions
        ])->update();

        return $this->response->setJSON(['status' => 'success', 'message' => 'Grade settings successfully']);
    }

    public function delete()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $name = $this->request->getPost('name');

        if (!$name) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $gradeModel = new GradeModel();

        $gradeModel->where('name', $name)->delete();

        $this->session->setFlashdata('status', 'grade_deleted');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Grade deleted successfully']);
    }
}
