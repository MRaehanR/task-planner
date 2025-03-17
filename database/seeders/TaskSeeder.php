<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Task::factory()->count(10)->fixedAndRecurring()->create();
        Task::factory()->count(10)->notFixedAndNotRecurring()->create();
        Task::factory()->count(10)->notFixedButRecurring()->create();

        Task::factory(1, [
            'user_id' => 1,
            'is_fixed' => true
        ])->create();
    }
}
