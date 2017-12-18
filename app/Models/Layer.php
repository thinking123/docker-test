<?php

namespace App\Models;

class Layer extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    protected $table = 'Layer';
}