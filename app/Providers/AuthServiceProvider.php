<?php

namespace App\Providers;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Policies are auto-discovered by Laravel (e.g. Product -> ProductPolicy)
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register policies explicitly if needed
        $this->registerPolicies();

        // Make $user->can('module.action') and Gate checks use our dynamic RBAC from DB
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            // If our permission service grants it, allow
            if (PermissionService::can($user, $ability)) {
                return true;
            }

            // Return null to let other gates/policies decide (e.g. for model-specific)
            return null;
        });
    }
}
