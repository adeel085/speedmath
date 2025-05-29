<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\GradeModel;
use App\Models\StudentGradeModel;
use App\Models\ClassModel;
use App\Models\TopicModel;
use App\Models\StudentSessionResultModel;

class AdminStudents extends BaseController
{
    public function index()
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/'));
        }

        $search = $this->request->getGet('search');

        if (empty($search)) {
            $search = NULL;
        }

        $gradeModel = new GradeModel();
        $studentGradeModel = new StudentGradeModel();

        $grades = $gradeModel->findAll();

        $userModel = new UserModel();
        $students = $userModel->filterByStudentOf($this->user['id']);

        if ($search) {
            $students = $students->groupStart()->like('full_name', $search, 'both')->orLike('username', $search, 'both')->orLike('email', $search, 'both')->groupEnd();
        }

        $students = $students->paginate(10);

        foreach ($students as &$student) {
            $studentGrade = $studentGradeModel->where('student_id', $student['id'])->first();

            $grade = $gradeModel->find($studentGrade['grade_id']);
            $student['grade'] = $grade;
        }

        return view('admin/students', [
            'pageTitle' => 'Students',
            'grades' => $grades,
            'students' => $students,
            'pager' => $userModel->pager,
            'flashData' => $this->session->getFlashdata(),
            'search' => $search,
            'user' => $this->user
        ]);
    }

    public function newPage()
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/'));
        }

        $gradeModel = new GradeModel();
        $grades = $gradeModel->findAll();

        $classModel = new ClassModel();
        $classes = $classModel->findAll();
        
        return view('admin/students_new', [
            'pageTitle' => 'New Student',
            'grades' => $grades,
            'classes' => $classes,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }

    public function saveNew()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $name = $this->request->getPost('name');
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $gradeId = $this->request->getPost('grade');
        $classId = $this->request->getPost('classId');
        $parentEmails = $this->request->getPost('parentEmails');

        if (!$name || !$username || !$email || !$password || !$gradeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $userModel = new UserModel();
        $studentGradeModel = new StudentGradeModel();
        $gradeModel = new GradeModel();

        $user = $userModel->where('username', $username)->first();

        if ($user) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Username already exists']);
        }

        $userId = $userModel->insert([
            'full_name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'user_type' => 'student'
        ], true);

        $userModel->insertUserMeta('studentOf', $this->user['id'], $userId);

        if ($classId) {
            $userModel->insertUserMeta('studentClassId', $classId, $userId);
        }

        if (!empty($parentEmails)) {
            $userModel->insertUserMeta('parentEmails', $parentEmails, $userId);
        }

        $grade = $gradeModel->find($gradeId);

        if (!$grade) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $studentGradeModel->insert([
            'student_id' => $userId,
            'grade_id' => $gradeId
        ]);

        $this->session->setFlashdata('status', 'student_created');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Student created successfully']);
    }

    public function editPage($id)
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/'));
        }

        $userModel = new UserModel();
        $gradeModel = new GradeModel();
        $studentGradeModel = new StudentGradeModel();
        $classModel = new ClassModel();

        $student = $userModel->find($id);

        if (!$student) {
            return redirect()->to(base_url('/admin/students'));
        }

        $studentOf = $userModel->getUserMeta('studentOf', $id, true);

        if ($studentOf !== $this->user['id']) {
            return redirect()->to(base_url('/admin/students'));
        }

        $classId = $userModel->getUserMeta('studentClassId', $student['id'], true);

        if ($classId) {
            $student['class'] = $classModel->find($classId);
        }

        $student['parent_emails'] = $userModel->getUserMeta('parentEmails', $student['id'], true) ?? '';

        $student['grade'] = $gradeModel->find($studentGradeModel->where('student_id', $student['id'])->first()['grade_id']);

        $grades = $gradeModel->findAll();

        $classes = $classModel->findAll();

        return view('admin/students_edit', [
            'pageTitle' => 'Edit Student',
            'student' => $student,
            'grades' => $grades,
            'classes' => $classes,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }

    public function reportsPage($id)
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return redirect()->to(base_url('/'));
        }

        $studentGradeModel = new StudentGradeModel();
        $gradeModel = new GradeModel();
        $topicModel = new TopicModel();
        $studentSessionResultModel = new StudentSessionResultModel();

        $filteredTopicId = $this->request->getGet('topic');

        $studentGrade = $studentGradeModel->where('student_id', $id)->first();
        $currentTopicId = NULL;

        if ($studentGrade) {
            $grade = $gradeModel->where('id', $studentGrade['grade_id'])->first();
            if ($grade) {
                $currentTopicId = $grade['topic_id'];
            }
        }

        if (empty($filteredTopicId) && $currentTopicId) {
            $filteredTopicId = $currentTopicId;
        }

        if ($filteredTopicId == NULL) {
            // Get the most recent topic_id from the student_session_results table
            $mostRecentTopicId = $studentSessionResultModel->select('topic_id')->where('student_id', $id)->orderBy('created_at', 'DESC')->first();

            if ($mostRecentTopicId) {
                $filteredTopicId = $mostRecentTopicId['topic_id'];
            }
        }

        $studentSessionResults = [];
        $filteredTopic = NULL;

        if ($filteredTopicId) {
            $filteredTopic = $topicModel->where('id', $filteredTopicId)->first();
            $studentSessionResults = $studentSessionResultModel->where('student_id', $id)->where('topic_id', $filteredTopicId)->orderBy('created_at', 'DESC')->findAll();
        }

        // Now get all of the topics the student has ever attempted
        $distinctTopicIds = $studentSessionResultModel->select('topic_id')->where('student_id', $id)->distinct()->findAll();
        $distinctTopicIds = array_column($distinctTopicIds, 'topic_id');

        if (count($distinctTopicIds) == 0 && $currentTopicId) {
            $distinctTopicIds = [$currentTopicId];
        }

        if (count($distinctTopicIds) > 0) {
            $allStudentTopics = $topicModel->whereIn('id', $distinctTopicIds)->findAll();
        }
        else {
            $allStudentTopics = [];
        }

        if (!in_array($filteredTopicId, $distinctTopicIds)) {
            $filteredTopic = NULL;
        }

        if (count($studentSessionResults) > 0) {
            $averageTimeTaken = 0;
            $bestTimeTaken = PHP_INT_MAX;
            $worstTimeTaken = 0;

            foreach ($studentSessionResults as $studentSessionResult) {
                $averageTimeTaken += $studentSessionResult['time_taken'];

                if ($studentSessionResult['time_taken'] > $worstTimeTaken) {
                    $worstTimeTaken = $studentSessionResult['time_taken'];
                }

                if ($studentSessionResult['time_taken'] < $bestTimeTaken) {
                    $bestTimeTaken = $studentSessionResult['time_taken'];
                }
            }

            $averageTimeTaken = $averageTimeTaken / count($studentSessionResults);
        }
        else {
            $averageTimeTaken = 0;
            $bestTimeTaken = 0;
            $worstTimeTaken = 0;
        }

        return view('admin/students_report', [
            'pageTitle' => 'Reports',
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user,
            'filteredTopic' => $filteredTopic,
            'studentSessionResults' => $studentSessionResults,
            'allStudentTopics' => $allStudentTopics,
            'averageTimeTaken' => $averageTimeTaken,
            'bestTimeTaken' => $bestTimeTaken,
            'worstTimeTaken' => $worstTimeTaken
        ]);
    }

    public function update()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $id = $this->request->getPost('id');
        $name = $this->request->getPost('name');
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $gradeId = $this->request->getPost('grade');
        $classId = $this->request->getPost('classId');
        $parentEmails = $this->request->getPost('parentEmails');

        if (!$id || !$name || !$username || !$email || !$gradeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $userModel = new UserModel();
        $studentGradeModel = new StudentGradeModel();
        $gradeModel = new GradeModel();

        $user = $userModel->where('username', $username)->first();

        if ($user && $user['id'] != $id) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Username already exists']);
        }

        $studentOf = $userModel->getUserMeta('studentOf', $id, true);

        if ($studentOf !== $this->user['id']) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $userModel->update($id, [
            'full_name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password ? password_hash($password, PASSWORD_DEFAULT) : $userModel->find($id)['password']
        ]);

        $grade = $gradeModel->find($gradeId);

        if (!$grade) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $studentOldGrade = $studentGradeModel->where('student_id', $id)->first();

        $updated = $studentGradeModel->set([
            'grade_id' => $gradeId
        ])->where('student_id', $id)->update();

        if ($updated && $studentOldGrade['grade_id'] != $gradeId) {
            
            // Reset the progress and session records for the student
            
        }

        if ($classId) {
            $userModel->updateUserMeta('studentClassId', $classId, $id, true);
        }

        if (!empty($parentEmails)) {
            $userModel->updateUserMeta('parentEmails', $parentEmails, $id, true);
        }
        else {
            $userModel->deleteUserMeta('parentEmails', $id, true);
        }

        $this->session->setFlashdata('status', 'student_updated');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Student updated successfully']);
    }

    public function delete()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $id = $this->request->getPost('id');

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $userModel = new UserModel();

        $student = $userModel->find($id);

        if (!$student) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $studentOf = $userModel->getUserMeta('studentOf', $id, true);

        if ($studentOf !== $this->user['id']) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $userModel = new UserModel();
        $studentGradeModel = new StudentGradeModel();

        $studentGradeModel->where('student_id', $id)->delete();
        $userModel->delete($id);

        $userModel->deleteAllUserMeta($id);

        $this->session->setFlashdata('status', 'student_deleted');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Student deleted successfully']);
    }

    public function sendMissedQuestionsEmail() {

        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $studentId = $this->request->getPost('student_id');
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        if (!$studentId || !$startDate || !$endDate) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }
        
        $userModel = new UserModel();
        $student = $userModel->where('id', $studentId)->where('user_type', 'student')->first();

        if (!$student) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $parentEmails = $userModel->getUserMeta('parentEmails', $studentId, true);

        if (empty($parentEmails)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Parent emails not found']);
        }

        $parentEmails = explode(',', $parentEmails);

        foreach ($parentEmails as $parentEmail) {
            $parentEmail = trim($parentEmail);
        }

        $this->sendMissingQuestionsEmail($parentEmails, $student, $startDate, $endDate);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Email sent successfully']);
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
}
