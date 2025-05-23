<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentSessionModel extends Model
{
    protected $table = 'students_sessions';
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
    protected $allowedFields = ['student_id', 'topic_id', 'level', 'percentage', 'correct_answers', 'stars'];
}
