<?php

namespace App\Models;

use App\Enum\DayOfWeek;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        // 'day_of_week' => DayOfWeek::class,
        'all_day' => 'boolean',
        'is_recurring' => 'boolean',
        'is_fixed' => 'boolean',
    ];
}
