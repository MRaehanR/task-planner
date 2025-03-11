<?php

use App\Http\Controllers\Authentication\LoginController;
use App\Http\Controllers\Authentication\LogoutController;
use App\Http\Controllers\Authentication\RegisterController;
use App\Http\Controllers\Task\CreateTaskController;
use App\Http\Controllers\Task\DeleteTaskByIdController;
use App\Http\Controllers\Task\GetTaskByIdController;
use App\Http\Controllers\Task\GetTasksController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

if (config('app.env') == 'local' || config('app.env') == 'testing') {
    Route::middleware('auth:sanctum')->get('/test', function (Request $request) {
        return response()->json([
            'status' => true,
            'message' => 'TEST',
        ]);
    });
}

Route::prefix('auth')->group(function () {
    Route::post('/login', LoginController::class);
    Route::post('/register', RegisterController::class);
    Route::post('/logout', LogoutController::class)->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->prefix('task')->group(function () {
    Route::get('/', GetTasksController::class);
    Route::get('/{id}', GetTaskByIdController::class);
    Route::post('/', CreateTaskController::class);
    Route::delete('/{id}', DeleteTaskByIdController::class);
});

Route::get('/tasktest', function () {
    $time = '2025-03-08 09:00:00'; // Example start time
    $dayOfWeek = 'Monday'; // Example day of week

    $currentDate = Carbon::now();
    $currentWeekDay = $currentDate->dayOfWeek;
    $targetDay = Carbon::parse($dayOfWeek)->dayOfWeek;
    $daysDifference = $targetDay - $currentWeekDay;

    $adjustedDate = $currentDate->copy()->addDays($daysDifference);
    $adjustedTime = Carbon::parse($time)->setDate($adjustedDate->year, $adjustedDate->month, $adjustedDate->day);

    $adjustedTime =  $adjustedTime->toDateTimeString();

    echo "Adjusted Time: " . $adjustedTime . "\n";
});