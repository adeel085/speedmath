<?php

namespace App\Models;

use CodeIgniter\Model;

class GradeWeeklyGoalModel extends Model
{
    protected $table = 'grades_weekly_goals';
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
    protected $allowedFields = ['grade_id', 'week_start_date', 'goal_points'];
}
