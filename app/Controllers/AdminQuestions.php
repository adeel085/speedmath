<?php

namespace App\Controllers;

use App\Models\TopicModel;
use App\Models\TopicQuestionsModel;
use App\Models\QuestionModel;
use App\Models\QuestionAnswersModel;

class AdminQuestions extends BaseController
{
    public function index()
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin') {
            return redirect()->to(base_url('/'));
        }

        $topicModel = new TopicModel();
        $topicQuestionsModel = new TopicQuestionsModel();

        $topics = $topicModel->findAll();

        $questionModel = new QuestionModel();
        $questions = $questionModel->orderBy('id', 'DESC')->paginate(10);

        foreach ($questions as &$question) {
            $question['topics'] = $topicQuestionsModel->where('question_id', $question['id'])->findAll();
        }

        return view('admin/questions', [
            'pageTitle' => 'Questions',
            'questions' => $questions,
            'pager' => $questionModel->pager,
            'topics' => $topics,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }

    public function newPage()
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin') {
            return redirect()->to(base_url('/'));
        }

        $topicModel = new TopicModel();
        $topics = $topicModel->findAll();

        return view('admin/questions_new', [
            'pageTitle' => 'New Question',
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

        $topicId = $this->request->getPost('topicId');
        $difficulty = $this->request->getPost('difficulty');
        $questionType = $this->request->getPost('questionType');
        $question = $this->request->getPost('question');
        $solution = $this->request->getPost('solution');
        $answers = json_decode(base64_decode($this->request->getPost('answers')));
        $correctAnswerIndex = (int) $this->request->getPost('correctAnswerIndex');

        $questionModel = new QuestionModel();
        $topicQuestionsModel = new TopicQuestionsModel();
        $questionAnswersModel = new QuestionAnswersModel();

        $questionId = $questionModel->insert([
            'level' => $difficulty,
            'question_type' => $questionType,
            'question_html' => $question,
            'solution_html' => $solution,
            'created_by' => $this->user['id']
        ]);

        $topicQuestionsModel->insert([
            'topic_id' => $topicId,
            'question_id' => $questionId
        ]);

        foreach ($answers as $index => $answer) {
            $questionAnswersModel->insert([
                'question_id' => $questionId,
                'answer' => $answer,
                'is_correct' => $questionType == "text" || $correctAnswerIndex == $index ? 1 : 0
            ]);
        }

        $this->session->setFlashdata('status', 'question_created');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Question saved successfully']);
    }

    public function editPage($id)
    {
        if (!$this->user) {
            return redirect()->to(base_url('/admin'));
        }

        if ($this->user['user_type'] != 'admin') {
            return redirect()->to(base_url('/'));
        }

        $questionModel = new QuestionModel();
        $question = $questionModel->find($id);

        if (!$question) {
            return redirect()->to(base_url('/admin/questions'));
        }

        $questionAnswersModel = new QuestionAnswersModel();
        $question['answers'] = $questionAnswersModel->where('question_id', $id)->findAll();

        return view('admin/questions_edit', [
            'pageTitle' => 'Edit Question',
            'question' => $question,
            'flashData' => $this->session->getFlashdata(),
            'user' => $this->user
        ]);
    }

    public function update()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $questionId = $this->request->getPost('questionId');
        $difficulty = $this->request->getPost('difficulty');
        $questionType = $this->request->getPost('questionType');
        $question = $this->request->getPost('question');
        $solution = $this->request->getPost('solution');
        $answers = json_decode(base64_decode($this->request->getPost('answers')));
        $correctAnswerIndex = (int) $this->request->getPost('correctAnswerIndex');

        $questionModel = new QuestionModel();
        $questionAnswersModel = new QuestionAnswersModel();

        $updated = $questionModel->update($questionId, [
            'level' => $difficulty,
            'question_type' => $questionType,
            'question_html' => $question,
            'solution_html' => $solution,
        ]);

        if (!$updated) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update question']);
        }

        $questionAnswersModel->where('question_id', $questionId)->delete();

        foreach ($answers as $index => $answer) {
            $questionAnswersModel->insert([
                'question_id' => $questionId,
                'answer' => $answer,
                'is_correct' => $questionType == "text" || $correctAnswerIndex == $index ? 1 : 0
            ]);
        }

        $this->session->setFlashdata('status', 'question_updated');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Question updated successfully']);
    }

    public function delete()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $questionId = $this->request->getPost('questionId');

        $questionModel = new QuestionModel();

        $question = $questionModel->find($questionId);

        if (!$question) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Question not found']);
        }

        $topicQuestionsModel = new TopicQuestionsModel();
        $topicQuestionsModel->where('question_id', $questionId)->delete();

        $questionAnswersModel = new QuestionAnswersModel();
        $questionAnswersModel->where('question_id', $questionId)->delete();

        $deleted = $questionModel->delete($questionId);

        if (!$deleted) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to delete question']);
        }

        $this->session->setFlashdata('status', 'question_deleted');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Question deleted successfully']);
    }

    public function importCsv()
    {
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $questionModel = new QuestionModel();
        $questionAnswersModel = new QuestionAnswersModel();

        $file = $this->request->getFile('file');

        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Move the uploaded file to the writable directory
            $newFileName = $file->getRandomName();
            $file->move(FCPATH . 'public/uploads', $newFileName);

            // Parse the CSV file
            $filePath = FCPATH . 'public/uploads/' . $newFileName;
            $csvRows = array_map('str_getcsv', file($filePath));

            for ($i = 1; $i < count($csvRows); $i++) {
                $row = $csvRows[$i];

                $topicName = $row[0];

                $topicModel = new TopicModel();
                $topicQuestionsModel = new TopicQuestionsModel();
                $topic = $topicModel->where('name', $topicName)->first();

                if (!$topic) {
                    $topicId = $topicModel->insert([
                        'name' => $topicName
                    ], true);
                }
                else {
                    $topicId = $topic['id'];
                }

                $level = $row[1];
                $questionType = $row[2];
                $question = $row[3];
                $solution = $row[4];

                // Answers
                $answer1 = $row[5];
                $is_answer1_correct = (int) $row[6];
                $answer2 = $row[7];
                $is_answer2_correct = (int) $row[8];
                $answer3 = $row[9];
                $is_answer3_correct = (int) $row[10];
                $answer4 = $row[11];
                $is_answer4_correct = (int) $row[12];

                $questionId = $questionModel->insert([
                    'level' => $level,
                    'question_type' => $questionType,
                    'question_html' => $question,
                    'solution_html' => $solution,
                    'created_by' => $this->user['id']
                ]);

                if ($questionId) {
                    $topicQuestionsModel->insert([
                        'topic_id' => $topicId,
                        'question_id' => $questionId
                    ]);

                    $questionAnswersModel->insert([
                        'question_id' => $questionId,
                        'answer' => $answer1,
                        'is_correct' => ($questionType == "text" || $is_answer1_correct == 1) ? 1 : 0
                    ]);

                    if (!empty($answer2)) {
                        $questionAnswersModel->insert([
                            'question_id' => $questionId,
                            'answer' => $answer2,
                            'is_correct' => ($questionType == "text" || $is_answer2_correct == 1) ? 1 : 0
                        ]);
                    }

                    if (!empty($answer3)) {
                        $questionAnswersModel->insert([
                            'question_id' => $questionId,
                            'answer' => $answer3,
                            'is_correct' => ($questionType == "text" || $is_answer3_correct == 1) ? 1 : 0
                        ]);
                    }

                    if (!empty($answer4)) {
                        $questionAnswersModel->insert([
                            'question_id' => $questionId,
                            'answer' => $answer4,
                            'is_correct' => ($questionType == "text" || $is_answer4_correct == 1) ? 1 : 0
                        ]);
                    }
                }
            }

            return $this->response->setJSON(['status' => 'success', "message" => "CSV file imported successfully."]);
        }
        else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'File upload failed.']);
        }
    }

    public function updateTopics() {
        
        if (!$this->user) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        if ($this->user['user_type'] != 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $questionId = $this->request->getPost('questionId');
        $topicsIds = explode(',', $this->request->getPost('topicsIds'));

        $topicQuestionsModel = new TopicQuestionsModel();
        $topicQuestionsModel->where('question_id', $questionId)->delete();

        foreach ($topicsIds as $topicId) {
            $topicQuestionsModel->insert([
                'topic_id' => $topicId,
                'question_id' => $questionId
            ]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Topics updated successfully']);
    }
}
