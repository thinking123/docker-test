<?php

namespace App\Models;

class Layer extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    const TYPES = [
        'SCREEN' => '1',
        'TEXT'   => '2',
        'IMAGE'  => '3',
        'BOX'    => '4',
        'ICON'   => '5',
        'SLOT'   => '6'
    ];

    protected $table = 'Layer';

    /**
     * 根据类型名称获取对应数值
     *
     * @param $typeName
     * @return int|null
     */
    public static function getTypeIdByName($typeName)
    {
        $typeName = strtoupper($typeName);

        return isset(static::TYPES[$typeName]) ? isset(static::TYPES[$typeName]) : null;
    }

    /**
     * 根据类型 ID 获取对应名称
     *
     * @param int $id
     * @return string|null
     */
    public static function getTypeNameById($id)
    {
        $types = array_flip(static::TYPES);

        return isset($types[$id]) ? $types[$id] : null;
    }
}