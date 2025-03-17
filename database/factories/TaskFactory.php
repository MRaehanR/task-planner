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
        $day_of_week = Carbon::instance($start_time)->format('l');

        return [
            'user_id' => rand(1, User::count()),
            'title' => fake()->sentence(),
            'desc' => fake()->text(50),
            'day_of_week' => $day_of_week,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'all_day' => rand(0, 1),
            'is_completed' => $this->faker->optional()->boolean(),
            'is_recurring' => rand(0, 1),
            'is_fixed' => rand(0, 1),
            'deadline' => $deadline
        ];
    }

    /**
     * Indicate that the task is fixed and recurring.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function fixedAndRecurring()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_fixed' => true,
                'is_recurring' => true,
            ];
        });
    }

    /**
     * Indicate that the task is not fixed and not recurring.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function notFixedAndNotRecurring()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_fixed' => false,
                'is_recurring' => false,
            ];
        });
    }

    /**
     * Indicate that the task is not fixed but recurring.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function notFixedButRecurring()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_fixed' => false,
                'is_recurring' => true,
            ];
        });
    }
}
