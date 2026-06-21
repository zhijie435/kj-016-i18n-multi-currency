<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Services\PermissionService;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionService::class, function ($app) {
            return new PermissionService();
        });
    }

    public function boot(PermissionService $permissionService): void
    {
        Gate::define('permission', function ($user, string $permission) use ($permissionService) {
            return $permissionService->hasPermission($permission);
        });

        Gate::define('role', function ($user, string $role) use ($permissionService) {
            return $permissionService->hasRole($role);
        });

        Gate::before(function ($user, string $ability) use ($permissionService) {
            if ($permissionService->hasPermission('*')) {
                return true;
            }
            return null;
        });
    }
}
