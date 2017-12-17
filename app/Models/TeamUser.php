<?php

namespace App\Models;

class TeamUser extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    protected $table = 'TeamUser';
}