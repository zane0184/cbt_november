<?php

namespace Database\Factories;

use App\Models\Curriculum;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurriculumFactory extends Factory
{
    protected $model = Curriculum::class;

    public function definition(): array
    {
        return [
            'name' => 'Kurikulum ' . $this->faker->word(),
            'code' => $this->faker->unique()->lexify('K-????'),
            'is_active' => true,
        ];
    }
}
