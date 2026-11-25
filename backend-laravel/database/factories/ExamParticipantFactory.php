<?php

namespace Database\Factories;

use App\Models\ExamParticipant;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamParticipantFactory extends Factory
{
    protected $model = ExamParticipant::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'user_id' => User::factory(),
            'status' => 'ASSIGNED',
        ];
    }
}
