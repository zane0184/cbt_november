<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'title' => $this->faker->sentence(),
            'duration_seconds' => 3600,
            'status' => 'DRAFT',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
            'timezone' => 'Asia/Jakarta',
            'created_by' => User::factory(),
        ];
    }
}
