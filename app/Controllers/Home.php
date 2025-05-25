<?php

namespace App\Controllers;

use App\Models\StudentGradeModel;
use App\Models\QuestionAnswersModel;
use App\Models\TopicModel;
use App\Models\QuestionModel;
use App\Models\TopicQuestionsModel;
use App\Models\StudentQuestionsResultsModel;
use App\Models\GradeModel;
use App\Models\StudentSessionResultModel;

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

        $studentGradeModel = new StudentGradeModel();
        $gradeModel = new GradeModel();
        $topicModel = new TopicModel();
        $topicQuestionsModel = new TopicQuestionsModel();
        $questionModel = new QuestionModel();
        $questionAnswersModel = new QuestionAnswersModel();

        $studentGrade = $studentGradeModel->where('student_id', $this->user['id'])->first();

        if ($studentGrade) {
            $grade = $gradeModel->where('id', $studentGrade['grade_id'])->first();

            if ($grade) {
                $numberOfQuestions = (int)$grade['number_of_questions'];
                $currentTopic = $topicModel->where('id', $grade['topic_id'])->first();

                if ($currentTopic) {
                    $message = "Your teacher has not assigned you a topic yet. Talk to your teacher to get assigned a topic. Your current grade is " . $grade['grade_level'] . ".";
                }
            }
            else {
                $numberOfQuestions = 0;
                $currentTopic = null;
                $message = "You have not been assigned a grade yet. Talk to your teacher to get assigned a grade.";
            }
        }
        else {
            $numberOfQuestions = 0;
            $currentTopic = null;
            $message = "You have not been assigned a grade yet. Talk to your teacher to get assigned a grade.";
        }

        $questions = [];

        if ($currentTopic) {
            $topicQuestions = $topicQuestionsModel->where('topic_id', $currentTopic['id'])->limit($numberOfQuestions)->find();

            foreach ($topicQuestions as $topicQuestion) {
                $question = $questionModel->select([
                        'id',
                        'question_type',
                        'question_html'
                    ])->where('id', $topicQuestion['question_id'])->first();
                
                if ($question['question_type'] == "mcq") {
                    $question['answers'] = $questionAnswersModel->select([
                        'answer'
                    ])->where('question_id', $question['id'])->findAll();
                }
                
                $questions[] = $question;
            }

            if (count($questions) == 0) {
                $message = "No questions found in your current topic <b>" . $currentTopic['name'] . "</b>. Talk to your teacher to add questions in this topic.";
            }
            else {
                // Randomize the questions
                shuffle($questions);

                // If count($questions) is less than $numberOfQuestions, then we need to repeat the questions and make sure that the total number of questions is $numberOfQuestions
                while (count($questions) < $numberOfQuestions) {
                    $questions = array_merge($questions, $questions);
                }

                $questions = array_slice($questions, 0, $numberOfQuestions);
            }

            $this->session->set('correct_count', 0);
            $this->session->set('incorrect_count', 0);
            $this->session->set('session_start_time', time());
            $this->session->set('grade_id', $studentGrade['grade_id']);
            $this->session->set('topic_id', $currentTopic['id']);
        }

        return view('home', [
            'pageTitle' => 'Home',
            'user' => $this->user,
            'currentTopic' => $currentTopic,
            'questions' => $questions,
            'numberOfQuestions' => $numberOfQuestions,
            'message' => $message
        ]);
    }

    public function pageSelection() {

        if (!$this->user) {
            return redirect()->to('/');
        }

        if ($this->user['user_type'] != 'student') {
            return redirect()->to(base_url('/'));
        }

        $topicModel = new TopicModel();
        $studentGradeModel = new StudentGradeModel();
        $gradeModel = new GradeModel();

        $studentGrade = $studentGradeModel->where('student_id', $this->user['id'])->first();
        $message = "";

        if ($studentGrade) {
            $grade = $gradeModel->where('id', $studentGrade['grade_id'])->first();

            if ($grade) {
                $currentTopic = $topicModel->where('id', $grade['topic_id'])->first();

                if ($currentTopic) {
                    $message = "Your teacher has not assigned you a topic yet. Talk to your teacher to get assigned a topic. Your current grade is " . $grade['grade_level'] . ".";
                }
            }
            else {
                $currentTopic = null;
                $message = "You have not been assigned a grade yet. Talk to your teacher to get assigned a grade.";
            }
        }
        else {
            $currentTopic = null;
            $message = "You have not been assigned a grade yet. Talk to your teacher to get assigned a grade.";
        }

        return view('page_selection', [
            'pageTitle' => 'Page Selection',
            'user' => $this->user,
            'currentTopic' => $currentTopic,
            'message' => $message
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

    public function evaluateSession() {
        
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'student') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $studentSessionResultModel = new StudentSessionResultModel();

        $sessionStartTime = $this->session->get('session_start_time');
        $correctCount = $this->session->get('correct_count');
        $incorrectCount = $this->session->get('incorrect_count');
        $gradeId = $this->session->get('grade_id');
        $topicId = $this->session->get('topic_id');

        $elapsedTime = time() - $sessionStartTime;

        $studentSessionResultModel->insert([
            'student_id' => $this->user['id'],
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'time_taken' => $elapsedTime,
            'grade_id' => $gradeId,
            'topic_id' => $topicId
        ]);

        $minutes = floor($elapsedTime / 60);
        $seconds = $elapsedTime % 60;

        $totalTime = $minutes . 'm ' . $seconds . 's';

        $response = ['status' => 'success', 'correct_count' => $correctCount, 'incorrect_count' => $incorrectCount, 'elapsed_time' => $totalTime];

        return $this->response->setJSON($response);
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
            $this->session->set('correct_count', (int)$this->session->get('correct_count') + 1);
        }
        else {
            $this->session->set('incorrect_count', (int)$this->session->get('incorrect_count') + 1);
        }

        $response = ['status' => 'success', 'is_correct' => $isCorrect];

        if (!$isCorrect) {
            $response['solution'] = $question['solution_html'];
        }

        $studentQuestionsResultsModel = new StudentQuestionsResultsModel();
        $studentQuestionsResultsModel->insert([
            'student_id' => $this->user['id'],
            'question_id' => $questionId,
            'student_answer' => $answer,
            'is_correct' => $isCorrect
        ]);

        return $this->response->setJSON($response);
    }
}
