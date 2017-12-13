<?php

namespace App\Http\Controllers;

use App\Services\Utils\Log as LogUtil;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use LogUtil;

    /**
     * Controller constructor.
     *
     */
    public function __construct()
    {
        //
    }
}
