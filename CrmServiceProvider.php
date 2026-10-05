<?php

namespace Modules\Crm;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Crm\Models\Contact;
use Modules\Crm\Policies\ContactPolicy;
use Modules\Crm\Services\CrmManager;
use Modules\Crm\Services\CrmService;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/crm.php', 'crm');

        $this->app->singleton(CrmManager::class, function ($app) {
            return new CrmManager($app);
        });

        $this->app->singleton(CrmService::class, function ($app) {
            return new CrmService($app->make(CrmManager::class));
        });

        $this->app->alias(CrmService::class, 'crm');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/crm.php' => config_path('crm.php'),
            ], 'crm-config');
        }

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (file_exists(__DIR__.'/Resources/lang')) {
            $this->loadTranslationsFrom(__DIR__.'/Resources/lang', 'crm');
        }

        Gate::policy(Contact::class, ContactPolicy::class);
    }
}
