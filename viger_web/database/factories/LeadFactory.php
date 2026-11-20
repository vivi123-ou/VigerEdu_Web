<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create('vi_VN');

        return [
            'full_name' => $faker->name(),
            'relationship' => $this->faker->randomElement(['Bố', 'Mẹ', 'Người giám hộ']),
            'student_name' => $this->faker->optional(0.8)->passthrough($faker->name()),
            'education_level' => $this->faker->optional(0.6)->randomElement(['Đang học THPT', 'Tốt nghiệp THPT', 'Đại học']),
            'program_interest' => $this->faker->optional(0.6)->randomElement(['Dự bị đại học (Studienkolleg)', 'Đại học', 'Du học nghề (Ausbildung)']),
            'phone' => '09'.$this->faker->unique()->numerify('########'),
            'email' => $this->faker->optional(0.7)->safeEmail(),
            'source' => $this->faker->randomElement(array_keys(Lead::SOURCES)),
            'status' => $this->faker->randomElement(array_keys(Lead::STATUSES)),
            'assigned_to' => null,
            'note' => null,
            'created_at' => $this->faker->dateTimeBetween('-60 days'),
        ];
    }
}
