<?php

namespace App\Models;

class Component extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    const ACCESS_PRIVATE = '0';
    const ACCESS_PUBLIC = '1';

    const DEFAULT_LIST_COUNT = 10;

    protected $table = 'Component';

    /**
     * 新建组件
     *
     * @param string $name
     * @param int $userId
     * @param int|null $teamId
     * @param string $access
     * @param string|null $createdAt
     * @throws \Exception
     * @return Component|null
     */
    public static function createComponent(
        $name,
        $userId,
        $teamId = null,
        $access = Component::ACCESS_PUBLIC,
        $createdAt = null
    ) {
        $component = new static;

        $component->name = $name;
        $component->userId = $userId;
        $component->teamId = $teamId ? $teamId : null;
        $component->access = "{$access}";
        $component->createdAt = !is_null($createdAt) ? $createdAt : date('Y-m-d H:i:s');

        $saved = $component->save();

        if ($saved) {
            return $component;
        }
    }

    /**
     * 获取用户组件列表
     *
     * @param int $userId
     * @param int $offset
     * @param int $limit
     * @return Array
     */
    public static function getUserComponents($userId, $offset, $limit = Component::DEFAULT_LIST_COUNT)
    {
        $builder = static::where('userId', $userId)->where('status', Component::STATUS_NORMAL)->orderBy('id', 'DESC');

        if ($offset > 0) {
            $builder->where('id', '<', $offset);
        }

        $components = $builder->limit($limit)->get()->toArray();

        foreach ($components as & $component) {
            $component['access'] = $component['access'] == 1 ? 'PUBLIC' : 'PRIVATE';
            $component['createdAt'] = strtotime($component['createdAt']);
            $component['updatedAt'] = is_null($component['updatedAt']) ? null : strtotime($component['updatedAt']);

            unset($component['status']);
        }

        return $components;
    }
}