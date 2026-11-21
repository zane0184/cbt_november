<?php

namespace Database\Factories;

use App\Models\Phase;
use App\Models\Curriculum;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhaseFactory extends Factory
{
    protected $model = Phase::class;

    public function definition(): array
    {
        return [
            'curriculum_id' => Curriculum::factory(),
            'name' => 'Phase ' . $this->faker->lexify('?'),
            'code' => $this->faker->unique()->lexify('PH-??'),
        ];
    }
}
