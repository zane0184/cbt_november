<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeLevelFactory extends Factory
{
    protected $model = GradeLevel::class;

    public function definition(): array
    {
        return [
            'phase_id' => Phase::factory(),
            'name' => $this->faker->word(),
            'code' => $this->faker->unique()->lexify('GL-??'),
            'level' => $this->faker->numberBetween(1, 12),
        ];
    }
}
