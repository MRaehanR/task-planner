<?php

namespace Database\Factories;

use App\Enum\DayOfWeek;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_time = $this->faker->dateTimeBetween('-5 days', '+5 days');
        $end_time = Carbon::instance($start_time)->addHours(rand(1, 3));
        $deadline = Carbon::instance($end_time)->addHours(rand(1, 240));

        return [
            'user_id' => rand(1, User::count()),
            'title' => fake()->sentence(),
            'desc' => fake()->text(50),
            'day_of_week' => fake()->randomElement(DayOfWeek::values()),
            'start_time' => $start_time,
            'end_time' => $end_time,
            'all_day' => rand(0, 1),
            'is_recurring' => rand(0, 1),
            'is_fixed' => rand(0, 1),
            'deadline' => $deadline
        ];
    }
}
