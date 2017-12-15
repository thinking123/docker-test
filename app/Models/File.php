<?php

namespace App\Models;

class File extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    const ACCESS_PRIVATE = '0';
    const ACCESS_PUBLIC = '1';

    const DEFAULT_LIST_COUNT = 10;

    protected $table = 'File';

    /**
     * 获取用户文件列表
     *
     * @param int $userId
     * @param int $offset
     * @param int $limit
     * @return Array
     */
    public static function getUserFiles($userId, $offset, $limit = File::DEFAULT_LIST_COUNT)
    {
        $builder = static::where('userId', $userId)->where('status', File::STATUS_NORMAL)->orderBy('id', 'DESC');

        if ($offset > 0) {
            $builder->where('id', '<', $offset);
        }

        $files = $builder->limit($limit)->get()->toArray();

        foreach ($files as &$file) {
            $file['access'] = $file['access'] == 1 ? 'PUBLIC' : 'PRIVATE';
            $file['createdAt'] = strtotime($file['createdAt']);
            $file['updatedAt'] = is_null($file['updatedAt']) ? null : strtotime($file['updatedAt']);

            unset($file['status']);
        }

        return $files;
    }
}