<?php

namespace Modules\Crm;

use Celios\Core\Services\ModuleRegistry;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Crm\Filament\Resources\Contacts\ContactResource;
use Modules\Crm\Filament\Support\FormSubmissionCrmBridge;
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

        if (file_exists(__DIR__.'/Routes/api.php')) {
            Route::prefix('api')
                ->middleware('api')
                ->group(__DIR__.'/Routes/api.php');
        }

        Gate::policy(Contact::class, ContactPolicy::class);

        if (class_exists(ModuleRegistry::class)) {
            ModuleRegistry::registerNamespace('crm', 'Modules\\Crm\\');
            ModuleRegistry::registerModule('crm', [
                'name' => 'CRM & Leads',
                'description' => 'Manage customer leads, contact pipeline, interaction notes, and form-to-lead conversions.',
                'icon' => 'heroicon-o-user-group',
                'resources' => [
                    ContactResource::class,
                ],
            ]);
            ModuleRegistry::registerFormSubmissionTableColumns('crm', fn (): array => FormSubmissionCrmBridge::tableColumns());
            ModuleRegistry::registerFormSubmissionTableActions('crm', fn (): array => FormSubmissionCrmBridge::tableActions());
            ModuleRegistry::registerFormSubmissionHeaderActions('crm', fn (Closure $recordResolver): array => FormSubmissionCrmBridge::headerActions($recordResolver));
        }
    }
}
