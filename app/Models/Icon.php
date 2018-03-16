<?php

namespace App\Models;

use DB;
use Log;

class Icon extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    protected $table = 'Icon';

    public function iconLib()
    {
        return $this->belongsTo('App\Models\IconLib', 'id', 'iconLibId');
    }

    /**
     * 格式化
     *
     * @param array $icon
     */
    public static function filter(& $icon)
    {
        $icon = [
            'id'   => $icon['id'],
            'name' => $icon['name'],
            'tags' => $icon['tags'],
            'path' => $icon['path'],
        ];
    }

    /**
     * 格式化
     *
     * @param array $icons
     */
    public static function filterIcons(& $icons)
    {
        foreach ($icons as & $icon) {
            static::filter($icon);
        }
    }
}