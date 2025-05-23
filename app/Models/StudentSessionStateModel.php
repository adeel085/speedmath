<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentSessionStateModel extends Model
{
    protected $table = 'students_sessions_states';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $dateFormat = 'datetime';
    protected $allowedFields = ['student_id', 'current_topic_id', 'current_level', 'current_question_index', 'incorrect_count', 'correct_count', 'completed'];
}
