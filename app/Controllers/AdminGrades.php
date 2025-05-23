<?php

namespace App\Controllers;

use App\Models\GradeModel;
use App\Models\TopicModel;
use App\Models\GradeRouteModel;

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

        $grades = $gradeModel->select('name')->groupBy('name')->findAll();

        foreach ($grades as &$grade) {
            $grade['grade_levels'] = $gradeModel->where('name', $grade['name'])->findAll();
        }

        return view('admin/grades', [
            'pageTitle' => 'Grades',
            'grades' => $grades,
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

    public function setRoutePage($id)
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin') {
            return redirect()->to(base_url('/'));
        }

        $gradeModel = new GradeModel();
        $topicModel = new TopicModel();
        $gradeRouteModel = new GradeRouteModel();

        $grade = $gradeModel->find($id);
        $topics = $topicModel->findAll();

        if (!$grade) {
            return redirect()->to(base_url('/admin/grades'));
        }

        $gradeRoute = $gradeRouteModel->where('grade_id', $id)->findAll();

        foreach ($gradeRoute as &$routeTopic) {
            $routeTopic['topic'] = $topicModel->find($routeTopic['topic_id']);
        }

        return view('admin/grades_set_route', [
            'pageTitle' => 'Set Route',
            'grade' => $grade,
            'topics' => $topics,
            'gradeRoute' => $gradeRoute,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }

    public function updateRoute()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $gradeId = $this->request->getPost('grade_id');
        $topics = $this->request->getPost('topics');

        if (!$gradeId || !$topics) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $topics = explode(',', $topics);

        // Remove duplicates
        $topics = array_unique($topics);

        $gradeRouteModel = new GradeRouteModel();

        $gradeRouteModel->where('grade_id', $gradeId)->delete();

        foreach ($topics as $topic) {
            $gradeRouteModel->insert(['grade_id' => $gradeId, 'topic_id' => $topic]);
        }

        $this->session->setFlashdata('status', 'route_updated');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Route updated successfully']);
    }
}
