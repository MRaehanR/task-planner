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
        'status' => DayOfWeek::class,
        'all_day' => 'boolean',
        'is_reccurring' => 'boolean',
    ];
}
