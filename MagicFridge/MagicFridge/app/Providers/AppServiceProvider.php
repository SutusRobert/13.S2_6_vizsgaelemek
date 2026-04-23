<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Ide kerülnének a service containerbe regisztrált alkalmazásszolgáltatások.
     */
    public function register(): void
    {
        // Jelenleg nincs külön regisztrálandó szolgáltatás.
    }

    /**
     * Ide kerülnének az alkalmazás indulásakor lefutó globális beállítások.
     */
    public function boot(): void
    {
        // Jelenleg nincs induláskori extra konfiguráció.
    }
}
