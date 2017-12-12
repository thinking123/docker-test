<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\Utils\Log as LogUtil;

class Base extends Model
{
    use LogUtil;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    public $timestamps = false;
}
