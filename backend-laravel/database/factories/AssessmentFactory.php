<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'title' => $this->faker->sentence(),
            'assessment_type' => 'SUMMATIVE',
            'created_by' => User::factory(),
        ];
    }
}
