<?php

namespace App\Providers;

use App\Events\ReportSubmitted;
use App\Listeners\Engine\ProcessNewReport;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ReportSubmitted::class, ProcessNewReport::class);

        Gate::before(function ($user) {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }

            return null;
        });

        Gate::after(function ($user, $ability, $result) {
            if ($result !== null) {
                return $result;
            }

            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability);
            }

            return false;
        });
    }
}
