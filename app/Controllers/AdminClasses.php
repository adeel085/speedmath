<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\UserModel;
use App\Models\GradeModel;
use App\Models\StudentGradeModel;
use App\Models\StudentSessionResultModel;
use App\Models\TopicModel;

class AdminClasses extends BaseController
{
    public function index()
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/'));
        }

        $classModel = new ClassModel();

        $classes = $classModel->findAll();

        return view('admin/classes', [
            'pageTitle' => 'Classes',
            'flashData' => $this->session->getFlashdata(),
            'classes' => $classes,
            'user' => $this->user
        ]);
    }

    public function studentsPage($id)
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/'));
        }

        $classModel = new ClassModel();
        $userModel = new UserModel();
        $gradeModel = new GradeModel();
        $studentGradeModel = new StudentGradeModel();

        $class = $classModel->find($id);

        if (!$class) {
            return redirect()->to(base_url('/admin/classes'));
        }

        $students = $userModel->filterByStudentOf($this->user['id'])->findAll();
        $filteredStudents = [];

        foreach ($students as &$student) {
            $studentClassId = $userModel->getUserMeta('studentClassId', $student['id'], true);

            if ($studentClassId == $id) {
                $filteredStudents[] = $student;
            }
        }

        foreach ($filteredStudents as &$student) {
            $studentGrade = $studentGradeModel->where('student_id', $student['id'])->first();

            $grade = $gradeModel->find($studentGrade['grade_id']);
            $student['grade'] = $grade;
        }

        return view('admin/classes_students', [
            'pageTitle' => 'Students',
            'flashData' => $this->session->getFlashdata(),
            'class' => $class,
            'students' => $filteredStudents,
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

        $classModel = new ClassModel();

        if ($classModel->where('name', $name)->first()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Class with this name already exists']);
        }

        $classModel->insert(['name' => $name]);

        $this->session->setFlashdata('status', 'class_created');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Class created successfully']);
    }

    public function delete()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $classId = $this->request->getPost('class_id');

        $classModel = new ClassModel();

        $classModel->delete($classId);

        $this->session->setFlashdata('status', 'class_deleted');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Class deleted successfully']);
    }

    public function update()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $classId = $this->request->getPost('class_id');
        $name = $this->request->getPost('name');

        $classModel = new ClassModel();
        
        // Get class by name
        $class = $classModel->where('name', $name)->first();

        if ($class && $class['id'] != $classId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Class with this name already exists']);
        }

        $classModel->set(['name' => $name])->update($classId);

        $this->session->setFlashdata('status', 'class_updated');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Class updated successfully']);
    }

    public function sendEmailToParents()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $classId = $this->request->getPost('class_id');
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        $classModel = new ClassModel();

        $class = $classModel->find($classId);

        if (!$class) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Class not found']);
        }

        $userModel = new UserModel();

        $students = $userModel->filterByStudentOf($this->user['id'])->findAll();

        foreach ($students as $student) {
            if ($userModel->getUserMeta('studentClassId', $student['id'], true) === $classId) {
                $parentEmails = $userModel->getUserMeta('parentEmails', $student['id'], true);

                if (empty($parentEmails)) {
                    continue;
                }

                helper('student_reports');

                $missingQuestions = getMissingQuestions($student['id'], $startDate, $endDate);

                if (empty($missingQuestions)) {
                    continue;
                }

                $parentEmails = explode(',', $parentEmails);

                foreach ($parentEmails as &$parentEmail) {
                    $parentEmail = trim($parentEmail);
                }

                $this->sendMissingQuestionsEmail($parentEmails, $student, $startDate, $endDate);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Emails sent successfully']);
    }

    private function sendMissingQuestionsEmail($parentEmails, $student, $startDate, $endDate) {
        
        $subject = "Report for " . $student['full_name'];
        $message = "Please click on the following link to view the questions that <b>" . $student['full_name'] . "</b> needs to work on:<br><br>";

        $questionsUrl = base_url('/report-questions?st=' . $student['id'] . '&sd=' . $startDate . '&ed=' . $endDate);

        $message .= "<a href='" . $questionsUrl . "'>" . $questionsUrl . "</a>";
        
        $email = \Config\Services::email();
        $email->setTo(array_shift($parentEmails));

         // Add remaining emails as CC
        if (!empty($parentEmails)) {
            $email->setCC($parentEmails);
        }

        $email->setCC($student['email']);

        $email->setFrom(env('email.SMTPUser'), 'MyQuickMath');
        $email->setSubject($subject);
        $email->setMessage($message);
        
        return $email->send();
    }

    public function reportsPage($id)
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/'));
        }

        $classModel = new ClassModel();
        $userModel = new UserModel();
        $topicModel = new TopicModel();
        $studentSessionResultModel = new StudentSessionResultModel();

        $topics = $topicModel->findAll();

        $class = $classModel->find($id);

        if (!$class) {
            return redirect()->to(base_url('/admin/classes'));
        }

        $classModel = new ClassModel();
        $userModel = new UserModel();
        $topicModel = new TopicModel();
        $studentSessionResultModel = new StudentSessionResultModel();

        $class = $classModel->find($id);

        if (!$class) {
            return redirect()->to(base_url('/admin/classes'));
        }

        $filteredTopicId = $this->request->getGet('topic');
        $filteredTopic = NULL;

        $totalTimeTaken = 0;
        $timeCount = 0;
        $bestTimeTaken = PHP_INT_MAX;
        $worstTimeTaken = 0;
        $averageTimeTaken = 0;
        $performanceData = [];

        if (!empty($filteredTopicId)) {
            $filteredTopic = $topicModel->where('id', $filteredTopicId)->first();

            if (!$filteredTopic) {
                return redirect()->back()->with('error', 'Topic not found.');
            }

            // Get all students belonging to this class
            $students = $userModel->filterByStudentOf($this->user['id'])->findAll();
            $filteredStudents = [];

            foreach ($students as $student) {
                $studentClassId = $userModel->getUserMeta('studentClassId', $student['id'], true);
                if ($studentClassId == $id) {
                    $filteredStudents[] = $student;
                }
            }

            $studentIds = array_column($filteredStudents, 'id');
            $resultsByDate = [];

            // Step 2: Get session results per student for the given topic
            foreach ($studentIds as $studentId) {
                $results = $studentSessionResultModel
                    ->where('student_id', $studentId)
                    ->where('topic_id', $filteredTopicId)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();

                foreach ($results as $result) {
                    $date = date('Y-m-d', strtotime($result['created_at']));
                    $correct = (int)$result['correct_count'];
                    $total = (int)$result['total_questions'];
                    $time = (int)$result['time_taken'];

                    // Track time taken for stats
                    $totalTimeTaken += $time;
                    $bestTimeTaken = min($bestTimeTaken, $time);
                    $worstTimeTaken = max($worstTimeTaken, $time);
                    $timeCount++;

                    $accuracy = $total > 0 ? $correct / $total : 0;
                    $speed = $time > 0 ? 1 / $time : 0;

                    $score = ($accuracy < 0.05) ? 0 : pow($accuracy, 2) * $speed * 1000;

                    if (!isset($resultsByDate[$date])) {
                        $resultsByDate[$date] = [
                            'total_score' => 0,
                            'count' => 0
                        ];
                    }

                    $resultsByDate[$date]['total_score'] += $score;
                    $resultsByDate[$date]['count']++;
                }
            }

            if ($timeCount > 0) {
                $averageTimeTaken = round($totalTimeTaken / $timeCount, 2);
            } else {
                $averageTimeTaken = 0;
                $bestTimeTaken = 0;
                $worstTimeTaken = 0;
            }

            // Step 3: Prepare final average performance per date
            $performanceData = [];
            foreach ($resultsByDate as $date => $data) {
                $averageScore = $data['count'] > 0 ? $data['total_score'] / $data['count'] : 0;
                $performanceData[] = [
                    'date' => $date,
                    'average_score' => round($averageScore, 2)
                ];
            }

            // Sort by date ascending
            usort($performanceData, fn($a, $b) => strcmp($a['date'], $b['date']));
        }
        else {
            $bestTimeTaken = 0;
        }

        return view('admin/classes_report', [
            'pageTitle' => 'Class Topic Report',
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user,
            'class' => $class,
            'filteredTopic' => $filteredTopic,
            'performanceData' => $performanceData,
            'averageTimeTaken' => $averageTimeTaken,
            'bestTimeTaken' => $bestTimeTaken,
            'worstTimeTaken' => $worstTimeTaken,
            'topics' => $topics
        ]);
    }
}
