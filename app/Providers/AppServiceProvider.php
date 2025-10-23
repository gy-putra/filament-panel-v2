<?php

namespace App\Providers;

use App\Models\Pendaftaran;
use App\Models\TabunganAlokasi;
use App\Models\User;
use App\Observers\PendaftaranObserver;
use App\Observers\PendaftaranRoomingObserver;
use App\Observers\TabunganAlokasiObserver;
use App\Observers\UserObserver;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        FilamentView::registerRenderHook(
            'panels::head.start',
            fn (): string => '<meta name="robots" content="noindex,nofollow">'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers with proper dependency injection
        Pendaftaran::observe(app(PendaftaranObserver::class));
        Pendaftaran::observe(app(PendaftaranRoomingObserver::class));
        TabunganAlokasi::observe(app(TabunganAlokasiObserver::class));
        User::observe(app(UserObserver::class));
    }
}
