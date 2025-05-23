<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentWeeklyPointsModel extends Model
{
    protected $table = 'students_weekly_points';
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
    protected $allowedFields = ['student_id', 'week_start_date', 'earned_points'];
}
