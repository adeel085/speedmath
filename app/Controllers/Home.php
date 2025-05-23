<?php

namespace App\Controllers;

use App\Models\StudentProgressModel;
use App\Models\StudentGradeModel;
use App\Models\GradeRouteModel;
use App\Models\QuestionAnswersModel;
use App\Models\TopicModel;
use App\Models\QuestionModel;
use App\Models\StudentSessionModel;
use App\Models\UserLoginSessionModel;
use App\Models\StudentSessionStateModel;
use App\Models\StudentSessionQuestionModel;
use App\Models\TopicQuestionsModel;
use App\Models\StudentQuestionsResultsModel;
use App\Models\GradeModel;
use App\Models\GradeWeeklyGoalModel;
use App\Models\StudentWeeklyPointsModel;
use App\Models\UserModel;

use DateTime;

class Home extends BaseController
{
    public function index()
    {
        if (!$this->user) {
            return redirect()->to('/');
        }

        if ($this->user['user_type'] != 'student') {
            return redirect()->to(base_url('/'));
        }

        $topicModel = new TopicModel();
        $studentGradeModel = new StudentGradeModel();
        $gradeRouteModel = new GradeRouteModel();
        $studentProgressModel = new StudentProgressModel();
        $studentSessionStateModel = new StudentSessionStateModel();
        $studentSessionQuestionModel = new StudentSessionQuestionModel();
        $gradeModel = new GradeModel();

        $studentGrade = $studentGradeModel->where('student_id', $this->user['id'])->first();

        if (!$studentGrade) {
            echo "No student grade found";
            exit;
        }

        $timerMinutes = 5;

        if ($studentGrade) {
            $timerMinutes = $gradeModel->where('id', $studentGrade['grade_id'])->first()['timer_minutes'];
        }

        $studentSessionState = $studentSessionStateModel->where('student_id', $this->user['id'])->first();

        // Check if we have the data in session
        if ($studentSessionState && $studentSessionState['completed'] == 0) {

            $startTime = new DateTime($studentSessionState['created_at']);

            // Add the timer minutes
            $endTime = clone $startTime;
            $endTime->modify("+{$timerMinutes} minutes");

            // Get the current time
            $currentTime = new DateTime();

            // Check if the timer has expired
            if ($currentTime >= $endTime) {
                try {
                    $this->endCurrentSession(false, false);
                    return redirect()->to('/');
                }
                catch (\Exception $e) {
                    if ($e->getMessage() == 'session_state_not_found') {
                        return redirect()->to('/');
                    }
                    else if ($e->getMessage() == 'no_topics') {
                        return view('no_topics', [
                            'pageTitle' => 'No Topics',
                            'user' => $this->user
                        ]);
                    }
                    else if ($e->getMessage() == 'student_grade_not_found') {
                        echo "Student grade not found";
                        exit();
                    }
                    else if ($e->getMessage() == 'progress_record_not_found') {
                        echo "Student progress record not found";
                        exit();
                    }
                    else if ($e->getMessage() == 'all_topics_completed') {
                        echo "All topics are completed";
                        exit();
                    }
                    else {
                        echo $e->getMessage();
                        exit();
                    }
                }
            }

            // Check if current question index is greater than or equal to 25
            if ($studentSessionState['current_question_index'] >= 25) {
                // redirect to evaluation page
                return redirect()->to('/evaluation');
            }

            $currentTopic = $topicModel->find($studentSessionState['current_topic_id']);
            $currentLevel = $studentSessionState['current_level'];

            // Check if the current topic has been deleted
            if (!$currentTopic) {

                // delete the student session state
                $studentSessionStateModel->where('student_id', $this->user['id'])->delete();

                // delete the student progress where the topic id is the current topic id
                $studentProgressModel->where([
                    'student_id' => $this->user['id'],
                    'topic_id' => $studentSessionState['current_topic_id']
                ])->delete();

                // delete the student session question for this student
                $studentSessionQuestionModel->where('student_id', $this->user['id'])->delete();
                
                // reload the page
                return redirect()->to('/');
            }

            return view('home', [
                'pageTitle' => 'Home',
                'user' => $this->user,
                'currentTopic' => $currentTopic,
                'currentLevel' => $currentLevel,
                'remainingSeconds' => max(0, $endTime->getTimestamp() - $currentTime->getTimestamp())
            ]);
        }
        
        $gradeRoute = $gradeRouteModel->where('grade_id', $studentGrade['grade_id'])->findAll();

        $topicsIds = [];

        foreach ($gradeRoute as $route) {
            if ($topicModel->find($route['topic_id'])) {
                $topicsIds[] = $route['topic_id'];
            }
        }

        if (count($topicsIds) == 0) {
            return view('no_topics', [
                'pageTitle' => 'No Topics',
                'user' => $this->user
            ]);
        }

        $currentTopic = null;
        $currentLevel = 0;

        foreach ($topicsIds as $topicId) {
            $studentProgressRecord = $studentProgressModel->where([
                'student_id' => $this->user['id'],
                'topic_id' => $topicId
            ])->first();

            if (!$studentProgressRecord) {
                $studentProgressModel->insert([
                    'student_id' => $this->user['id'],
                    'topic_id' => $topicId,
                    'level' => 1,
                    'completed' => 0
                ]);

                $currentTopic = $topicModel->find($topicId);

                if ($currentTopic) {
                    $currentLevel = 1;
                    break;
                }
            }
            else {
                if ($studentProgressRecord['completed'] == 0) {
                    $currentTopic = $topicModel->find($topicId);

                    if ($currentTopic) {
                        $currentLevel = $studentProgressRecord['level'];
                        break;
                    }
                }
            }
        }

        if (!$currentTopic) {
            return view('passed_all', [
                'pageTitle' => 'Passed All Levels',
                'user' => $this->user
            ]);
        }

        // Delete the old student session state
        $studentSessionStateModel->where('student_id', $this->user['id'])->delete();

        // Eneter all the session information into the database
        $studentSessionStateModel->insert([
            'student_id' => $this->user['id'],
            'current_topic_id' => $currentTopic['id'],
            'current_level' => $currentLevel,
            'current_question_index' => 0,
            'incorrect_count' => 0,
            'correct_count' => 0
        ]);

        return view('home', [
            'pageTitle' => 'Home',
            'user' => $this->user,
            'currentTopic' => $currentTopic,
            'currentLevel' => $currentLevel,
            'remainingSeconds' => $timerMinutes * 60
        ]);
    }

    public function pageSelection() {

        if (!$this->user) {
            return redirect()->to('/');
        }

        if ($this->user['user_type'] != 'student') {
            return redirect()->to(base_url('/'));
        }

        $studentSessionStateModel = new StudentSessionStateModel();
        $gradeRouteModel = new GradeRouteModel();
        $topicModel = new TopicModel();
        $studentProgressModel = new StudentProgressModel();
        $studentGradeModel = new StudentGradeModel();

        $studentSessionState = $studentSessionStateModel->where('student_id', $this->user['id'])->first();

        // Check if we have an in-progress session
        if ($studentSessionState && $studentSessionState['completed'] == 0) {
            return redirect()->to('/home');
        }

        $currentTopic = null;

        $studentGrade = $studentGradeModel->where('student_id', $this->user['id'])->first();

        if ($studentGrade) {
            $gradeRoute = $gradeRouteModel->where('grade_id', $studentGrade['grade_id'])->findAll();

            if ($gradeRoute) {
                $topicsIds = [];

                foreach ($gradeRoute as $route) {
                    if ($topicModel->find($route['topic_id'])) {
                        $topicsIds[] = $route['topic_id'];
                    }
                }

                foreach ($topicsIds as $topicId) {
                    $studentProgressRecord = $studentProgressModel->where([
                        'student_id' => $this->user['id'],
                        'topic_id' => $topicId
                    ])->first();

                    if (!$studentProgressRecord) {
                        $studentProgressModel->insert([
                            'student_id' => $this->user['id'],
                            'topic_id' => $topicId,
                            'level' => 1,
                            'completed' => 0
                        ]);

                        $currentTopic = $topicModel->find($topicId);

                        if ($currentTopic) {
                            break;
                        }
                    }
                    else {
                        if ($studentProgressRecord['completed'] == 0) {
                            $currentTopic = $topicModel->find($topicId);

                            if ($currentTopic) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        $monday = new DateTime();
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
            'student_id' => $this->user['id'],
            'week_start_date' => $lastMondayDate
        ])->first();

        $currentWeekPoints = 0;

        if ($studentWeeklyPoints) {
            $currentWeekPoints = $studentWeeklyPoints['earned_points'];
        }

        // Get the total points for the entire current year
        $currentYearStartDate = new DateTime();
        $currentYearStartDate->setDate($currentYearStartDate->format('Y'), 1, 1);
        $sum = $studentWeeklyPointsModel->where([
            'student_id' => $this->user['id'],
            'week_start_date >= ' => $currentYearStartDate->format('Y-m-d'),
            'week_start_date <= ' => $lastMondayDate
        ])->selectSum('earned_points')->first();

        $currentYearTotalPoints = 0;

        if ($sum['earned_points'] !== NULL) {
            $currentYearTotalPoints = $sum['earned_points'];
        }

        return view('page_selection', [
            'pageTitle' => 'Page Selection',
            'user' => $this->user,
            'currentTopic' => $currentTopic,
            'weeklyGoal' => $weeklyGoal,
            'studentWeeklyPoints' => $studentWeeklyPoints,
            'currentWeekPoints' => $currentWeekPoints,
            'currentYearTotalPoints' => $currentYearTotalPoints
        ]);
    }

    public function progressPage() {

        if (!$this->user) {
            return redirect()->to('/');
        }

        if ($this->user['user_type'] != 'student') {
            return redirect()->to(base_url('/'));
        }

        $studentSessionStateModel = new StudentSessionStateModel();

        $studentSessionState = $studentSessionStateModel->where('student_id', $this->user['id'])->first();
        
        // Check if we have an in-progress session
        if ($studentSessionState && $studentSessionState['completed'] == 0) {
            return redirect()->to('/home');
        }

        $studentProgressModel = new StudentProgressModel();
        $topicModel = new TopicModel();
        $studentGradeModel = new StudentGradeModel();
        $gradeModel = new GradeModel();

        // Get student's progress
        $studentProgressRecords = $studentProgressModel->where('student_id', $this->user['id'])->findAll();

        $studentProgress = [];

        foreach ($studentProgressRecords as &$progress) {
            $topic = $topicModel->where('id', $progress['topic_id'])->first();
            $studentProgress []= [
                'topic' => $topic,
                'level' => $progress['level'],
                'completed' => $progress['completed']
            ];
        }

        // Get student's grade
        $studentGrade = $studentGradeModel->where('student_id', $this->user['id'])->first();

        if ($studentGrade) {
            $this->user['grade'] = $gradeModel->where('id', $studentGrade['grade_id'])->first();
        }
        
        return view('progress', [
            'pageTitle' => 'Progress',
            'user' => $this->user,
            'studentProgress' => $studentProgress
        ]);
    }

    public function historyPage() {

        if (!$this->user) {
            return redirect()->to('/');
        }

        if ($this->user['user_type'] != 'student') {
            return redirect()->to(base_url('/'));
        }

        return view('history', [
            'pageTitle' => 'History',
            'user' => $this->user
        ]);
    }

    public function getHistory() {

        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'student') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $week = $this->request->getPost('week'); // format: 2025-W15

        $userModel = new UserModel();
        $studentSessionModel = new StudentSessionModel();

        $student = $userModel->find($this->user['id']);

        if (!$student) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Student not found']);
        }
        
        $students = [$student];
        
        // Determine date range
        if (!empty($week)) {
            // If week is provided, calculate start (Monday) and end (Sunday) dates of that week
            $dt = new \DateTime();
            $dt->setISODate(substr($week, 0, 4), substr($week, 6, 2)); // Year and week number
            $startDate = $dt->format('Y-m-d');

            $dt->modify('+6 days');
            $endDate = $dt->format('Y-m-d');
        } else {
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
        }

        return $this->response->setJSON(['status' => 'success', 'data' => ["students" => $students]]);
    }

    public function getQuestion() {

        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'student') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $studentSessionStateModel = new StudentSessionStateModel();
        $studentSessionQuestionModel = new StudentSessionQuestionModel();
        $topicQuestionsModel = new TopicQuestionsModel();
        $questionModel = new QuestionModel();
        $questionAnswersModel = new QuestionAnswersModel();
        $userLoginSessionModel = new UserLoginSessionModel();
        $gradeModel = new GradeModel();
        $studentGradeModel = new StudentGradeModel();

        $studentSessionState = $studentSessionStateModel->where('student_id', $this->user['id'])->first();

        if (!$studentSessionState || $studentSessionState['completed'] == 1) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Student session state not found']);
        }

        if ($studentSessionState['current_question_index'] >= 25) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'session_completed']);
        }

        $studentGrade = $studentGradeModel->where('student_id', $this->user['id'])->first();

        if (!$studentGrade) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Student grade not found']);
        }

        $timerMinutes = 5;

        if ($studentGrade) {
            $timerMinutes = $gradeModel->where('id', $studentGrade['grade_id'])->first()['timer_minutes'];
        }

        $studentSessionState = $studentSessionStateModel->where('student_id', $this->user['id'])->first();

        // Check if the timer has expired
        $startTime = new DateTime($studentSessionState['created_at']);

        // Add the timer minutes
        $endTime = clone $startTime;
        $endTime->modify("+{$timerMinutes} minutes");

        // Get the current time
        $currentTime = new DateTime();

        // Check if the timer has expired
        if ($currentTime >= $endTime) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'session_completed']);
        }

        $studentSessionQuestions = $studentSessionQuestionModel->where('student_id', $this->user['id'])->findAll();

        $processedItems = [];

        foreach ($studentSessionQuestions as $studentSessionQuestion) {
            $processedItems[] = $studentSessionQuestion['question_id'];
        }

        // Remove the processed items from the list that has been deleted
        $filteredProcessedItems = [];

        foreach ($processedItems as $processedItem) {
            if ($questionModel->find($processedItem)) {
                $filteredProcessedItems[] = $processedItem;
            }
        }

        $processedItems = $filteredProcessedItems;

        // Now get the list of available items
        $currentTopicId = $studentSessionState['current_topic_id'];
        $currentLevel = $studentSessionState['current_level'];

        $topicQuestions = $topicQuestionsModel->where('topic_id', $currentTopicId)->findAll();

        $questionsIds = [];
        foreach ($topicQuestions as $topicQuestion) {
            $questionsIds[] = $topicQuestion['question_id'];
        }

        if (count($questionsIds) == 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No questions found']);
        }

        // List of available questions that are not processed
        $questions = [];

        foreach ($questionsIds as $questionId) {

            if (count($processedItems) == 0) {
                $question = $questionModel->select([
                    'id',
                    'level',
                    'question_type',
                    'question_html'
                ])->where(['id' => $questionId, 'level' => $currentLevel])->first();
            }
            else {
                $question = $questionModel->select([
                    'id',
                    'level',
                    'question_type',
                    'question_html'
                ])->where(['id' => $questionId, 'level' => $currentLevel])->whereNotIn('id', $processedItems)->first();
            }

            if ($question) {
                $questions[] = $question;
            }
        }

        // If there are some unporcessed questions available then send a random available unprocessed question
        if (count($questions)) {

            $randomIndex = array_rand($questions);
            $question = $questions[$randomIndex];

            if ($question['question_type'] == 'mcq') {
                $question['answers'] = $questionAnswersModel->select([
                    'answer'
                ])->where('question_id', $question['id'])->findAll();
            }

            return $this->response->setJSON(['status' => 'success', 'question' => $question]);
        }

        $checkedItems = [];
        $nextQuestionId = -1;

        for ($i = count($processedItems) - 1; $i >= 0; $i--) {

            if (in_array($processedItems[$i], $checkedItems)) {
                $nextQuestionId = $processedItems[$i + 1];
                break;
            }

            $checkedItems[] = $processedItems[$i];
        }

        if (count($processedItems) == 0) {
            
            // Delete user login session from database
            $userLoginSessionModel->where('user_id', $this->user['id'])->delete();

            // Logout user
            $this->session->destroy();

            // Complete the session
            $studentSessionStateModel->set('completed', 1)->where('student_id', $this->user['id'])->update();

            $studentSessionQuestionModel->where('student_id', $this->user['id'])->delete();
            
            return $this->response->setJSON(['status' => 'success', 'question' => NULL]);
            
        }

        if ($nextQuestionId == -1) {
            $nextQuestionId = $processedItems[0];
        }

        $question = $questionModel->select([
            'id',
            'level',
            'question_type',
            'question_html'
        ])->find($nextQuestionId);

        if ($question['question_type'] == "mcq") {
            $question['answers'] = $questionAnswersModel->select([
                'answer'
            ])->where('question_id', $question['id'])->findAll();
        }

        return $this->response->setJSON(['status' => 'success', 'question' => $question]);
    }

    public function submitAnswer() {

        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'student') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $questionId = $this->request->getPost('question_id');
        $answer = $this->request->getPost('answer');

        if (!$questionId || !$answer) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Bad Request']);
        }

        $studentSessionStateModel = new StudentSessionStateModel();
        $studentSessionQuestionModel = new StudentSessionQuestionModel();
        $gradeModel = new GradeModel();
        $studentGradeModel = new StudentGradeModel();

        $studentGrade = $studentGradeModel->where('student_id', $this->user['id'])->first();

        if (!$studentGrade) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Student grade not found']);
        }

        $timerMinutes = 5;

        if ($studentGrade) {
            $timerMinutes = $gradeModel->where('id', $studentGrade['grade_id'])->first()['timer_minutes'];
        }

        $studentSessionState = $studentSessionStateModel->where('student_id', $this->user['id'])->first();

        if (!$studentSessionState) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Student session state not found']);
        }

        // Check if the timer has expired
        $startTime = new DateTime($studentSessionState['created_at']);

        // Add the timer minutes
        $endTime = clone $startTime;
        $endTime->modify("+{$timerMinutes} minutes");

        // Get the current time
        $currentTime = new DateTime();

        // Check if the timer has expired
        if ($currentTime >= $endTime) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'session_completed']);
        }

        $questionModel = new QuestionModel();
        $question = $questionModel->find($questionId);

        if (!$question) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Question not found']);
        }

        if ($question['question_type'] == 'mcq') {
            $answer = base64_decode($answer);
        }

        $questionAnswersModel = new QuestionAnswersModel();
        $questionAnswer = $questionAnswersModel->where('question_id', $questionId)->where('answer', $answer)->first();

        $isCorrect = false;

        if ($question['question_type'] == 'mcq') {
            $isCorrect = $questionAnswer && $questionAnswer['is_correct'];
        }
        else if ($question['question_type'] == 'text') {
            $isCorrect = ($questionAnswer != null) ? true : false;
        }

        if ($isCorrect) {
            $studentSessionStateModel->set('correct_count', $studentSessionState['correct_count'] + 1)->where('student_id', $this->user['id'])->update();
        }
        else {
            $studentSessionStateModel->set('incorrect_count', $studentSessionState['incorrect_count'] + 1)->where('student_id', $this->user['id'])->update();
        }

        // Increment the current question index
        $studentSessionStateModel->set('current_question_index', $studentSessionState['current_question_index'] + 1)->where('student_id', $this->user['id'])->update();

        $response = ['status' => 'success', 'is_correct' => $isCorrect];

        if (!$isCorrect) {
            $response['solution'] = $question['solution_html'];
        }

        $studentSessionQuestionModel->insert([
            'student_id' => $this->user['id'],
            'question_id' => $questionId
        ]);

        $studentQuestionsResultsModel = new StudentQuestionsResultsModel();
        $studentQuestionsResultsModel->insert([
            'student_id' => $this->user['id'],
            'question_id' => $questionId,
            'student_answer' => $answer,
            'is_correct' => $isCorrect
        ]);

        return $this->response->setJSON($response);
    }

    public function evaluationPage() {

        if (!$this->user) {
            return redirect()->to('/');
        }

        if ($this->user['user_type'] != 'student') {
            return redirect()->to(base_url('/'));
        }

        try {
            $result = $this->endCurrentSession();

            return view('results', [
                'pageTitle' => 'Results',
                'user' => $this->user,
                'stars' => $result['stars'],
                'currentTopic' => $result['currentTopic']
            ]);
        }
        catch (\Exception $e) {
            if ($e->getMessage() == 'session_state_not_found') {
                return redirect()->to('/');
            }
            else if ($e->getMessage() == 'no_topics') {
                return view('no_topics', [
                    'pageTitle' => 'No Topics',
                    'user' => $this->user
                ]);
            }
            else if ($e->getMessage() == 'student_grade_not_found') {
                echo "Student grade not found";
                exit();
            }
            else if ($e->getMessage() == 'progress_record_not_found') {
                echo "Student progress record not found";
                exit();
            }
            else if ($e->getMessage() == 'all_topics_completed') {
                echo "All topics are completed";
                exit();
            }
        }
    }

    private function endCurrentSession($logoutUser = true, $recordScores = true) {

        $studentSessionStateModel = new StudentSessionStateModel();
        $studentSessionState = $studentSessionStateModel->where('student_id', $this->user['id'])->first();

        if (!$studentSessionState) {
            throw new \Exception('session_state_not_found');
        }

        $correctCount = $studentSessionState['correct_count'];

        $totalQuestions = 25;
        $percentage = ($correctCount / $totalQuestions) * 100;

        $currentTopicIndex = -1;
        $currentTopic = null;
        $currentLevel = 0;

        // Find the current topic and level
        $studentGradeModel = new StudentGradeModel();
        $studentGrade = $studentGradeModel->where('student_id', $this->user['id'])->first();

        if (!$studentGrade) {
            throw new \Exception('student_grade_not_found');
        }

        $gradeRouteModel = new GradeRouteModel();
        $gradeRoute = $gradeRouteModel->where('grade_id', $studentGrade['grade_id'])->findAll();

        $topicModel = new TopicModel();
        $topicsIds = [];

        foreach ($gradeRoute as $route) {
            if ($topicModel->find($route['topic_id'])) {
                $topicsIds[] = $route['topic_id'];
            }
        }

        if (count($topicsIds) == 0) {
            throw new \Exception('no_topics');
        }

        $studentProgressModel = new StudentProgressModel();

        foreach ($topicsIds as $topicIdIndex => $topicId) {
            $studentProgressRecord = $studentProgressModel->where([
                'student_id' => $this->user['id'],
                'topic_id' => $topicId
            ])->first();

            if (!$studentProgressRecord) {
                
                // This situation will happen when a student is working on a topic and the admin removes that topic from his route or deletes the topic
                // As a result we will find a topic in the route that is not available in student's progress records
                // In this case, we need to delete the progress records of the student for that topic and create a new one for the next available topic according to the route

                $studentProgressModel->where([
                    'student_id' => $this->user['id'],
                    'completed' => 0
                ])->delete();

                $studentProgressModel->insert([
                    'student_id' => $this->user['id'],
                    'topic_id' => $topicId,
                    'level' => 1,
                    'completed' => 0
                ]);

                $currentTopic = $topicModel->find($topicId);
                $currentTopicIndex = $topicIdIndex;

                if ($currentTopic) {
                    $currentLevel = 1;
                    break;
                }
            }
            else {
                if ($studentProgressRecord['completed'] == 0) {
                    $currentTopic = $topicModel->find($topicId);
                    $currentTopicIndex = $topicIdIndex;

                    if ($currentTopic) {
                        $currentLevel = $studentProgressRecord['level'];
                        break;
                    }
                }
            }
        }

        if ($percentage >= 90) {

            $nextTopicId = -1;

            if ($currentTopicIndex < count($topicsIds) - 1) {
                $nextTopicId = $topicsIds[$currentTopicIndex + 1];
            }

            if (!$currentTopic) {
                // All topics are completed
                throw new \Exception('all_topics_completed');
            }

            $newLevel = $currentLevel + 1;

            $pointsToAdd = 0;

            if ($newLevel < 4) {

                // Promote to next level
                $studentProgressModel->set('level', $newLevel)->where([
                    'student_id' => $this->user['id'],
                    'topic_id' => $currentTopic['id']
                ])->update();

                if ($newLevel == 2) {
                    $pointsToAdd = 5;
                }
                else if ($newLevel == 3) {
                    $pointsToAdd = 10;
                }
            }
            else {
                // Complete the topic
                $studentProgressModel->set('completed', 1)->where([
                    'student_id' => $this->user['id'],
                    'topic_id' => $currentTopic['id']
                ])->update();

                if ($nextTopicId != -1) {
                    // Promote to the next topic level 1
                    $studentProgressModel->insert([
                        'student_id' => $this->user['id'],
                        'topic_id' => $nextTopicId,
                        'level' => 1,
                        'completed' => 0
                    ]);
                }

                $pointsToAdd = 15;
            }

            // Code to add points to the student's weekly points
            $monday = new DateTime();
            if ($monday->format('N') != 1) { // 'N' = ISO day of week (1 = Monday, 7 = Sunday)
                $monday->modify('last monday');
            }
            $lastMondayDate = $monday->format('Y-m-d');

            $studentWeeklyPointsModel = new StudentWeeklyPointsModel();

            // Get the student's weekly points for the current week
            $studentWeeklyPoints = $studentWeeklyPointsModel->where([
                'student_id' => $this->user['id'],
                'week_start_date' => $lastMondayDate
            ])->first();

            if ($studentWeeklyPoints) {

                $totalPoints = $studentWeeklyPoints['earned_points'] + $pointsToAdd;

                if ($totalPoints < 1200) {
                    $studentWeeklyPointsModel->set('earned_points', $studentWeeklyPoints['earned_points'] + $pointsToAdd)->where([
                        'student_id' => $this->user['id'],
                        'week_start_date' => $lastMondayDate
                    ])->update();
                }
                else {
                    // Reset the points to 0 by deleting all weekly points of the student
                    $studentWeeklyPointsModel->where('student_id', $this->user['id'])->delete();
                }
            }
            else {
                $studentWeeklyPointsModel->insert([
                    'student_id' => $this->user['id'],
                    'week_start_date' => $lastMondayDate,
                    'earned_points' => $pointsToAdd
                ]);
            }
        }

        if ($logoutUser) {
            // Delete user login session from database
            $userLoginSessionModel = new UserLoginSessionModel();
            $userLoginSessionModel->where('user_id', $this->user['id'])->delete();

            // Logout user
            $this->session->destroy();
        }

        if ($recordScores) {

            // Complete the session
            $studentSessionStateModel->set('completed', 1)->where('student_id', $this->user['id'])->update();

            $stars = round(($correctCount / $totalQuestions) * 5);

            if ($stars == 0) {
                $stars = 1;
            }

            $studentSessionModel = new StudentSessionModel();
            $studentSessionModel->insert([
                'student_id' => $this->user['id'],
                'topic_id' => $currentTopic['id'],
                'level' => $currentLevel,
                'percentage' => $percentage,
                'correct_answers' => $correctCount,
                'stars' => $stars
            ]);

            $studentSessionQuestionModel = new StudentSessionQuestionModel();
            $studentSessionQuestionModel->where('student_id', $this->user['id'])->delete();
            
            return [
                'stars' => $stars,
                'percentage' => $percentage,
                'currentTopic' => $currentTopic
            ];
        }
        else {

            $studentSessionStateModel->where('student_id', $this->user['id'])->delete();
 
            $studentSessionQuestionModel = new StudentSessionQuestionModel();
            $studentSessionQuestionModel->where('student_id', $this->user['id'])->delete();
        }
    }
}
