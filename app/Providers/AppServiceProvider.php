<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Configuracion;
use App\Models\Movimiento;
use App\Observers\MovimientoObserver;
use Illuminate\Support\Facades\View;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // 🔍 Registrar Observer para auditoría de ingresos y egresos
       // Movimiento::observe(MovimientoObserver::class);

        View::composer('*', function ($view) {

            $view->with(
                'configuracionGlobal',
                Configuracion::first()
            );

            $view->with(
                'notificaciones',
                NotificationService::ultimas()
            );

            $view->with(
                'cantidadNotificaciones',
                NotificationService::cantidad()
            );

        });
    }
}