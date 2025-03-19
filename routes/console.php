<?php

use App\Models\Task;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\Notification\WhatsAppNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $whatsAppNotificationService = app(WhatsAppNotificationService::class);
    Log::info('scheduleTaskNotifications method called.');

    $now = Carbon::now();
    $startOfMinute = $now->copy()->startOfMinute();
    $endOfMinute = $now->copy()->endOfMinute();

    $tasks = Task::whereBetween('start_time', [$startOfMinute, $endOfMinute])
        ->with('user') // Eager load the user relationship
        ->get();

    Log::info('Tasks found: ', ['tasks' => $tasks]);

    foreach ($tasks as $task) {
        $userPhoneNumber = $task->user->phone; // Assuming the user's phone number is stored in the user model

        // Convert phone number format from 08 to +628
        $formattedPhoneNumber = convertPhoneNumber($userPhoneNumber);

        $message = "**REMINDER TASK**\n"
            . "Title: {$task->title}\n"
            . "Start: {$task->start_time}\n"
            . "End: {$task->end_time}";

        Log::info('Sending WhatsApp message: ', ['phone' => $formattedPhoneNumber, 'message' => $message]);
        $whatsAppNotificationService->sendWhatsAppMessage($formattedPhoneNumber, $message);
    }
})->everyMinute();

/**
 * Convert phone number format from 08 to +628.
 *
 * @param string $phoneNumber
 * @return string
 */
function convertPhoneNumber($phoneNumber)
{
    if (strpos($phoneNumber, '08') === 0) {
        return '+628' . substr($phoneNumber, 2);
    }
    return $phoneNumber;
}
