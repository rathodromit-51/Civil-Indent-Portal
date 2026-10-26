<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Installation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installation>
 */
class InstallationFactory extends Factory
{
    protected $model = Installation::class;

    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'name' => fake()->city() . ' Plant / Installation',
            'code' => strtoupper(fake()->unique()->lexify('INST-???')),
        ];
    }
}
