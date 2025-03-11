<?php

namespace App\Providers;

use App\Services\Authentication\AuthenticationService;
use App\Services\Authentication\AuthenticationServiceImplement;
use App\Services\Task\TaskService;
use App\Services\Task\TaskServiceImplement;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function provides(): array
    {
        return [
            AuthenticationService::class,
            TaskService::class
        ];
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->services();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}

    private function services()
    {
        $this->app->singleton(AuthenticationService::class, AuthenticationServiceImplement::class);
        $this->app->singleton(TaskService::class, TaskServiceImplement::class);
    }
}
