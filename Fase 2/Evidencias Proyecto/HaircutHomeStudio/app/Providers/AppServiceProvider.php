<?php

namespace App\Providers;

use App\Models\Configuracion;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('layouts.app', function ($view): void {
            $view->with('layoutConfig', Configuracion::query()->first() ?? new Configuracion([
                'hora_inicio' => '10:30:00',
                'hora_fin' => '19:30:00',
                'dias_atencion' => '2,3,4,5,6',
            ]));
        });
    }
}
