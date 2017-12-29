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

    public function owner()
    {
        return $this->hasOne('App\Models\User', 'id', 'userId');
    }

    /**
     * 获取用户文件列表
     *
     * @param int $userId
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public static function getUserFiles($userId, $offset, $limit = File::DEFAULT_LIST_COUNT)
    {
        $builder = static::with('owner')->where('userId', $userId)->where('status', File::STATUS_NORMAL)->orderBy('id',
            'DESC');

        if ($offset > 0) {
            $builder->where('id', '<', $offset);
        }

        $files = $builder->limit($limit)->get()->toArray();

        foreach ($files as &$file) {
            $file['access'] = $file['access'] == 1 ? 'PUBLIC' : 'PRIVATE';
            $file['createdAt'] = strtotime($file['createdAt']);
            $file['updatedAt'] = is_null($file['updatedAt']) ? null : strtotime($file['updatedAt']);

            if (isset($file['owner']) && !empty($file['owner'])) {
                $file['owner'] = [
                    'id'     => $file['owner']['id'],
                    'name'   => $file['owner']['name'],
                    'avatar' => $file['owner']['avatar'],
                    'email'  => $file['owner']['email'],
                ];
            }

            unset($file['userId'], $file['status']);
        }

        return $files;
    }

    /**
     * 新建一个文件
     *
     * @param string $name
     * @param int $userId
     * @param int|null $teamId
     * @param string $access
     * @param string|null $createdAt
     * @throws \Exception
     * @return File|null
     */
    public static function createFile($name, $userId, $teamId = null, $access = File::ACCESS_PUBLIC, $createdAt = null)
    {
        $file = new static;

        $file->name = $name;
        $file->userId = $userId;
        $file->teamId = $teamId ? $teamId : null;
        $file->access = "{$access}";
        $file->createdAt = !is_null($createdAt) ? $createdAt : date('Y-m-d H:i:s');

        $saved = $file->save();

        if ($saved) {
            return $file;
        }
    }
}