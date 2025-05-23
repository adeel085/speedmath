<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\GradeModel;
use App\Models\StudentGradeModel;
use App\Models\ClassModel;
use App\Models\StudentProgressModel;
use App\Models\StudentSessionStateModel;
use App\Models\StudentSessionQuestionModel;
use App\Models\StudentQuestionsResultsModel;
use App\Models\StudentSessionModel;
use App\Models\StudentWeeklyPointsModel;
use App\Models\TopicModel;
use App\Models\QuestionModel;
use App\Models\QuestionAnswersModel;
use App\Models\GradeWeeklyGoalModel;

use CodeIgniter\Exceptions\PageNotFoundException;

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

        $userModel = new UserModel();
        $studentProgressModel = new StudentProgressModel();
        $topicModel = new TopicModel();
        $studentGradeModel = new StudentGradeModel();
        $gradeModel = new GradeModel();
        $questionModel = new QuestionModel();
        $questionAnswersModel = new QuestionAnswersModel();

        $student = $userModel->find($id);

        if (empty($student)) {
            throw PageNotFoundException::forPageNotFound('Student not found');
        }

        // Get student's grade
        $studentGrade = $studentGradeModel->where('student_id', $student['id'])->first();

        if ($studentGrade) {
            $student['grade'] = $gradeModel->where('id', $studentGrade['grade_id'])->first();
        }

        $startDateProgress = $this->request->getGet('sdp');
        $endDateProgress = $this->request->getGet('edp');

        $filteredProgress = false;

        // Get student's progress
        if (empty($startDateProgress) || empty($endDateProgress)) {
            $studentProgress = $studentProgressModel->where('student_id', $student['id'])->findAll();
        }
        else {
            $studentProgress = $studentProgressModel->where([
                'student_id' => $student['id'],
                'created_at >=' => $startDateProgress,
                'created_at <=' => $endDateProgress
            ])->findAll();

            $filteredProgress = true;
        }

        $student['progress'] = [];

        foreach ($studentProgress as &$progress) {
            $topic = $topicModel->where('id', $progress['topic_id'])->first();
            $student['progress'] []= [
                'topic' => $topic,
                'level' => $progress['level'],
                'completed' => $progress['completed']
            ];
        }

        // Get student's missed questions for last 7 days
        helper('student_reports');

        $isLast7Days = false;

        $startDate = $this->request->getGet('sd');
        $endDate = $this->request->getGet('ed');

        if (empty($startDate) || empty($endDate)) {
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate = date('Y-m-d');
            $isLast7Days = true;
        }
        else {
            $startDate = date('Y-m-d', strtotime($startDate));
            $endDate = date('Y-m-d', strtotime($endDate));

            if ($startDate == date('Y-m-d', strtotime('-7 days')) && $endDate == date('Y-m-d')) {
                $isLast7Days = true;
            }
        }

        $missingQuestionsResults = getMissingQuestions($student['id'], $startDate, $endDate);

        $missingQuestions = [];

        foreach ($missingQuestionsResults as $missingQuestionResult) {
            $missingQuestion = $questionModel->find($missingQuestionResult['question_id']);
            $missingQuestion['incorrect_count'] = $missingQuestionResult['incorrect_count'];
            $missingQuestion['student_answers'] = $missingQuestionResult['student_answers'];
            $missingQuestion['correct_answer'] = $questionAnswersModel->where('question_id', $missingQuestionResult['question_id'])->where('is_correct', 1)->first()['answer'];
            $missingQuestions[] = $missingQuestion;
        }

        $monday = new \DateTime();
        if ($monday->format('N') != 1) { // 'N' = ISO day of week (1 = Monday, 7 = Sunday)
            $monday->modify('last monday');
        }
        $lastMondayDate = $monday->format('Y-m-d');

        $gradeWeeklyGoalModel = new GradeWeeklyGoalModel();
        $studentWeeklyPointsModel = new StudentWeeklyPointsModel();

        // Get the latest weekly goal
        $weeklyGoal = $gradeWeeklyGoalModel->where([
            'grade_id' => $studentGrade['grade_id'],
            'week_start_date <= ' => $lastMondayDate
        ])->orderBy('week_start_date', 'DESC')->first();

        // Get the student's weekly points for the current week
        $studentWeeklyPoints = $studentWeeklyPointsModel->where([
            'student_id' => $student['id'],
            'week_start_date' => $lastMondayDate
        ])->first();

        $currentWeekPoints = 0;

        if ($studentWeeklyPoints) {
            $currentWeekPoints = $studentWeeklyPoints['earned_points'];
        }

        // Get the total points for the entire current year
        $currentYearStartDate = new \DateTime();
        $currentYearStartDate->setDate($currentYearStartDate->format('Y'), 1, 1);
        $sum = $studentWeeklyPointsModel->where([
            'student_id' => $student['id'],
            'week_start_date >= ' => $currentYearStartDate->format('Y-m-d'),
            'week_start_date <= ' => $lastMondayDate
        ])->selectSum('earned_points')->first();

        $currentYearTotalPoints = 0;

        if ($sum['earned_points'] !== NULL) {
            $currentYearTotalPoints = $sum['earned_points'];
        }

        return view('admin/students_report', [
            'pageTitle' => 'Reports',
            'student' => $student,
            'missingQuestions' => $missingQuestions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isLast7Days' => $isLast7Days,
            'flashData' => $this->session->getFlashdata(),
            'filteredProgress' => $filteredProgress,
            'startDateProgress' => $startDateProgress,
            'endDateProgress' => $endDateProgress,
            'weeklyGoal' => $weeklyGoal,
            'studentWeeklyPoints' => $studentWeeklyPoints,
            'currentWeekPoints' => $currentWeekPoints,
            'currentYearTotalPoints' => $currentYearTotalPoints,
            'user' => $this->user
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
        $studentWeeklyPointsModel = new StudentWeeklyPointsModel();

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
            $studentProgressModel = new StudentProgressModel();
            $studentProgressModel->where('student_id', $id)->delete();

            $studentSessionStateModel = new StudentSessionStateModel();
            $studentSessionStateModel->where('student_id', $id)->delete();

            $studentSessionQuestionModel = new StudentSessionQuestionModel();
            $studentSessionQuestionModel->where('student_id', $id)->delete();

            $studentQuestionsResultsModel = new StudentQuestionsResultsModel();
            $studentQuestionsResultsModel->where('student_id', $id)->delete();

            $studentSessionModel = new StudentSessionModel();
            $studentSessionModel->where('student_id', $id)->delete();

            // Delete the weekly points of the student
            $studentWeeklyPointsModel->where('student_id', $id)->delete();
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

    public function resetPoints()
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
        $studentOf = $userModel->getUserMeta('studentOf', $id, true);

        if ($studentOf !== $this->user['id']) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $studentWeeklyPointsModel = new StudentWeeklyPointsModel();
        $studentWeeklyPointsModel->where('student_id', $id)->delete();

        $this->session->setFlashdata('status', 'student_points_reset');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Student points reset successfully']);
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
