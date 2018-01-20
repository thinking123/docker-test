<?php

namespace App\Models;

use DB;
use Log;

class DesignToken extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    protected $table = 'DesignToken';
}