<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot()
    {
        app('translator')->setLocale('en');
        
        app('validator')->extend('length', function ($attribute, $value, $parameters, $validator) {
            $length = mb_strlen($value, 'UTF-8');

            if (count($parameters) == 1) {
                return $length >= $parameters[0];
            }

            return $length >= $parameters[0] && $length <= $parameters[1];
        });
    }
}
