<?php

namespace App\Modules\ApplicationTracking;

use App\Modules\ApplicationTracking\Models\Application;
use App\Modules\ApplicationTracking\Policies\ApplicationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ApplicationTrackingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Route::middleware('api')->prefix('api')->group(__DIR__.'/routes.php');

        Gate::policy(Application::class, ApplicationPolicy::class);
    }
}
