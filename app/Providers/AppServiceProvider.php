<?php

namespace App\Providers;

use App\Enums\RolSistema;
use App\Models\User;
use App\Policies\RolPolicy;
use App\Policies\UserPolicy;
use App\Services\Pagos\DeunaPaymentProvider;
use App\Services\Pagos\ManualPaymentProvider;
use App\Services\Pagos\NonePaymentProvider;
use App\Services\Pagos\PagoManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use App\Listeners\RegistrarAccesoListener;
use App\Models\Parametro;
use App\Observers\ParametroObserver;
use Illuminate\Pagination\Paginator;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registro del PagoManager como singleton con los proveedores disponibles.
        $this->app->singleton(PagoManager::class, fn () => new PagoManager([
            'none'   => new NonePaymentProvider(),
            'manual' => new ManualPaymentProvider(),
            'deuna'  => new DeunaPaymentProvider(),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        // Configuración global para paginación con Bootstrap 5
        Paginator::useBootstrapFive();

        // Registro explícito de la Policy de User
        // (aunque Laravel 11 auto-descubriría, declararlo aquí mejora la lectura)
        Gate::policy(User::class, UserPolicy::class);
        // Registro explícito de la Policy de Role
        Gate::policy(Role::class, RolPolicy::class);

        // Gate personalizado: solo super_admin puede otorgar el rol super_admin.
        // Uso en controladores: $this->authorize('asignar-rol-super-admin');
        Gate::define('asignar-rol-super-admin', function (User $user): bool {
            return $user->hasRole(RolSistema::SuperAdmin->value);
        });

        /*
        * Registro del listener de auditoría de accesos.
        * Una sola clase maneja los 4 eventos relevantes de Breeze/Laravel.
        */
        Event::listen(Login::class,   [RegistrarAccesoListener::class, 'handleLogin']);
        Event::listen(Logout::class,  [RegistrarAccesoListener::class, 'handleLogout']);
        Event::listen(Failed::class,  [RegistrarAccesoListener::class, 'handleFailed']);
        Event::listen(Lockout::class, [RegistrarAccesoListener::class, 'handleLockout']);

        /*
         * Registro del Observer de Parametro para auditoría de cambios.
         * Cada vez que se actualice un Parametro, se registrará en la bitácora.
         */
        Parametro::observe(ParametroObserver::class);
    }
}
