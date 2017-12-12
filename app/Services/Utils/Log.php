<?php

namespace App\Services\Utils;

use Log as SysLog;

trait Log
{
    public static function log(\Throwable $t)
    {
        SysLog::info($t->getMessage() . ' in file ' . $t->getFile() . ' on line ' . $t->getLine());
    }
}