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

    /**
     * 根据文件 id 获取其 layer
     *
     * @param int $id
     * @param int $depth
     * @return array
     */
    public static function getFileLayers($id, $depth = 5)
    {
        if ($depth < 1) {
            return [];
        }

        $layers = Layer::where('fileId', $id)->where('parentId', 0)
            ->where('status', Layer::STATUS_NORMAL)->orderBy('position', 'DESC')->get();

        dd($layers);
    }
}