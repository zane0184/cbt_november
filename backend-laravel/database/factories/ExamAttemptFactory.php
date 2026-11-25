<?php

namespace Database\Factories;

use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'participant_id' => ExamParticipant::factory(),
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
        ];
    }
}
