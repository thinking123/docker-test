<?php

namespace App\Facades;

use illuminate\Support\Facades\Facade;

class Output extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'output';
    }
}
