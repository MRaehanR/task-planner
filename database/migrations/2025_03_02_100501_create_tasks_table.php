<?php

use App\Enum\DayOfWeek;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('title');
            $table->string('desc')->nullable();
            $table->enum('day_of_week', [DayOfWeek::values()]);
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->boolean('is_completed')->nullable();
            $table->boolean('all_day')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_fixed')->default(false);
            $table->time('deadline')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
