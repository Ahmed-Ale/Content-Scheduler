<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Platform>
 */
class PlatformFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platforms = [
            ['name' => 'LinkedIn', 'type' => 'linkedin'],
            ['name' => 'Twitter', 'type' => 'twitter'],
            ['name' => 'Instagram', 'type' => 'instagram'],
        ];
        $platform = $this->faker->randomElement($platforms);

        return [
            'name' => $platform['name'],
            'type' => $platform['type'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
