<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Utils\Output;

class OutputServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('output', function () {
            return new Output();
        });
    }
}
