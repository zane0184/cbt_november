<?php

namespace Database\Factories;

use App\Models\ExamAttemptScore;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamAttemptScoreFactory extends Factory
{
    protected $model = ExamAttemptScore::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'attempt_id' => ExamAttempt::factory(),
            'student_id' => User::factory(),
            'raw_score' => 80.0,
            'max_score' => 100.0,
            'percentage' => 80.0,
            'grading_status' => 'FINALIZED',
        ];
    }
}
