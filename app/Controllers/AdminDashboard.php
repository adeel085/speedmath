<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentSessionModel;
use App\Models\TopicModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\StudentSessionStateModel;
use App\Models\StudentGradeModel;
use App\Models\GradeModel;
use App\Models\StudentProgressModel;
use App\Models\GradeWeeklyGoalModel;
use App\Models\StudentWeeklyPointsModel;

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

        $classModel = new ClassModel();
        $userModel = new UserModel();
        $studentSessionStateModel = new StudentSessionStateModel();
        $studentGradeModel = new StudentGradeModel();
        $gradeModel = new GradeModel();
        $studentProgressModel = new StudentProgressModel();
        $topicModel = new TopicModel();

        $classes = $classModel->findAll();

        foreach ($classes as &$class) {
            $class['students'] = [];
        }

        $students = $userModel->filterByStudentOf($this->user['id'])->findAll();

        foreach ($students as &$student) {
            $classId = $userModel->getUserMeta("studentClassId", $student['id'], true);

            // Get student's grade
            $studentGrade = $studentGradeModel->where('student_id', $student['id'])->first();

            if ($studentGrade) {
                $student['grade'] = $gradeModel->where('id', $studentGrade['grade_id'])->first();
            }

            $timerMinutes = $gradeModel->where('id', $studentGrade['grade_id'])->first()['timer_minutes'];

            // select where created_at greater than last midnight
            $student['session'] = $studentSessionStateModel->where('student_id', $student['id'])->where('updated_at >', date('Y-m-d 00:00:00', strtotime('today')))->first();

            if ($student['session'] && $student['session']['completed'] == 0) {
                $startTime = new \DateTime($student['session']['created_at']);

                // Add the timer minutes
                $endTime = clone $startTime;
                $endTime->modify("+{$timerMinutes} minutes");

                // Get the current time
                $currentTime = new \DateTime();

                // Check if the timer has expired
                if ($currentTime >= $endTime) {
                    $student['session'] = NULL;
                }
            }

            // Get student's progress
            $studentProgress = $studentProgressModel->where('student_id', $student['id'])->findAll();

            $student['progress'] = [];

            foreach ($studentProgress as &$progress) {
                $topic = $topicModel->where('id', $progress['topic_id'])->first();
                $student['progress'] []= [
                    'topic' => $topic,
                    'level' => $progress['level'],
                    'completed' => $progress['completed']
                ];
            }

            foreach ($classes as &$class) {
                if ($class['id'] == $classId) {
                    $class['students'][] = $student;
                    break;
                }
            }
        }
        
        return view('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'flashData' => $this->session->getFlashdata(),
            'classes' => $classes,
            'user' => $this->user
        ]);
    }

    public function viewReport()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin' && $this->user['user_type'] != 'teacher') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $classId = $this->request->getPost('classId');
        $week = $this->request->getPost('week'); // format: 2025-W15

        $userModel = new UserModel();
        $studentSessionModel = new StudentSessionModel();
        $studentGradeModel = new StudentGradeModel();
        $gradeWeeklyGoalModel = new GradeWeeklyGoalModel();
        $studentWeeklyPointsModel = new StudentWeeklyPointsModel();

        $students = $userModel->filterByStudentOf($this->user['id'])->findAll();

        $filteredStudents = [];

        // Determine date range
        if (!empty($week)) {
            // If week is provided, calculate start (Monday) and end (Sunday) dates of that week
            $dt = new \DateTime();
            $dt->setISODate(substr($week, 0, 4), substr($week, 6, 2)); // Year and week number
            $startDate = $dt->format('Y-m-d');

            $dt->modify('+6 days');
            $endDate = $dt->format('Y-m-d');
        }
        else {
            // Otherwise, last 7 days
            $startDate = date('Y-m-d', strtotime('-6 days'));
            $endDate = date('Y-m-d');
        }

        // Prepare dates array
        $dates = [];
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );
        foreach ($period as $date) {
            $dates[$date->format('l')] = $date->format('Y-m-d');
        }

        foreach ($students as &$student) {
            $studentClass = $userModel->getUserMeta("studentClassId", $student['id'], true);

            if ($studentClass != $classId) {
                continue;
            }

            // Fetch latest records per day in the selected date range
            $subQuery = $studentSessionModel->select('MAX(created_at) as latest_created_at, DATE(created_at) as session_date')
                ->where('created_at >=', $startDate . " 00:00:00")
                ->where('created_at <=', $endDate . " 23:59:59")
                ->where('student_id', $student['id'])
                ->groupBy('session_date')
                ->get()
                ->getResultArray();

            $latestTimestamps = [];
            foreach ($subQuery as $row) {
                $latestTimestamps[$row['session_date']] = $row['latest_created_at'];
            }

            $records = [];
            foreach ($dates as $dayName => $date) {
                if (isset($latestTimestamps[$date])) {
                    $record = $studentSessionModel->where('created_at', $latestTimestamps[$date])->first();
                    $records[$dayName] = $record;
                } else {
                    $records[$dayName] = [];
                }
            }

            $student['sessions'] = $records;

            $currentWeekPoints = 0;

            if (!empty($week)) {
                $studentGrade = $studentGradeModel->where('student_id', $student['id'])->first();

                if ($studentGrade) {
                    // Get the latest weekly goal
                    $weeklyGoal = $gradeWeeklyGoalModel->where([
                        'grade_id' => $studentGrade['grade_id'],
                        'week_start_date <= ' => $startDate
                    ])->orderBy('week_start_date', 'DESC')->first();

                    if ($weeklyGoal) {
                        // Get the student's weekly points for the current week
                        $studentWeeklyPoints = $studentWeeklyPointsModel->where([
                            'student_id' => $student['id'],
                            'week_start_date' => $startDate
                        ])->first();

                        if ($studentWeeklyPoints) {
                            $currentWeekPoints = $studentWeeklyPoints['earned_points'];
                        }

                        $student['weekly_goal'] = $weeklyGoal['goal_points'];
                    }
                    else {
                        $student['weekly_goal'] = 0;
                    }

                    $student['current_week_points'] = $currentWeekPoints;
                }
            }

            $filteredStudents[] = $student;
        }

        return $this->response->setJSON(['status' => 'success', 'data' => ["students" => $filteredStudents]]);
    }
}
