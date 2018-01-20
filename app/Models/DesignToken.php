<?php

namespace App\Models;

use DB;
use Log;

class DesignToken extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    const DEFAULT_PAGE_SIZE = 10;

    protected $table = 'DesignToken';

    public function creator()
    {
        return $this->hasOne('App\Models\User', 'id', 'userId');
    }

    /**
     * 获取文件所属的 Design Token 列表
     *
     * @param $id
     * @param $offset
     * @param $limit
     * @return array
     */
    public static function getFileDesignTokens($id, $offset, $limit = DesignToken::DEFAULT_PAGE_SIZE)
    {
        $builder = static::with('creator')->where('status', static::STATUS_NORMAL);

        if ($offset > 0) {
            $builder->where('id', '<', $offset);
        }

        $tokens = $builder->orderBy('id', 'DESC')->limit($limit)->get()->toArray();

        static::filterTokens($tokens);

        return $tokens;
    }

    /**
     * 格式化 Design Token
     *
     * @param DesignToken $dt
     */
    public static function filter(& $dt)
    {
        $dt['createdAt'] = isset($dt['createdAt']) ? strtotime($dt['createdAt']) : null;
        $dt['updatedAt'] = isset($dt['updatedAt']) ? strtotime($dt['updatedAt']) : null;

        if (isset($dt['creator']) && !empty($dt['creator'])) {
            $dt['creator'] = [
                'id'     => $dt['creator']['id'],
                'name'   => $dt['creator']['name'],
                'avatar' => $dt['creator']['avatar'],
                'email'  => $dt['creator']['email'],
            ];
        }

        unset($dt['status'], $dt['userId']);
    }

    /**
     * 格式化 Design Token 数组
     *
     * @param array $tokens
     */
    public static function filterTokens(& $tokens)
    {
        foreach ($tokens as &$token) {
            static::filter($token);
        }
    }
}