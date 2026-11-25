<?php

namespace Database\Factories;

use App\Models\Rombel;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class RombelFactory extends Factory
{
    protected $model = Rombel::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'grade_level_id' => GradeLevel::factory(),
            'name' => $this->faker->word(),
        ];
    }
}
