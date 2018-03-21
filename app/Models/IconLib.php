<?php

namespace App\Models;

use DB;
use Log;

class IconLib extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';
    const ACCOUNT_TYPE_PERSONAL = '1';
    const ACCOUNT_TYPE_TEAM = '2';

    protected $table = 'IconLib';

    public function icons()
    {
        return $this->hasMany('App\Models\Icon', 'iconLibId', 'id')->where('status', '1');
    }

    /**
     * 获取全部可用的 icons
     *
     * @param int $userId
     * @param int $accountId
     * @return array
     */
    public static function getIconLibs($userId, $accountId = 0)
    {
        if ($userId) {
            $builder = static::where(function ($builder) use ($userId) {
                $builder->where('accountId', 0)->orWhere(function ($builder) use ($userId) {
                    $builder->where('accountId', $userId)->where('accountType', static::ACCOUNT_TYPE_PERSONAL);
                });
            });
        } else {
            $builder = static::where(function ($builder) use ($accountId) {
                $builder->where('accountId', 0)->orWhere(function ($builder) use ($accountId) {
                    $builder->where('accountId', $accountId)->where('accountType', static::ACCOUNT_TYPE_TEAM);
                });
            });
        }

        $builder->where('status', static::STATUS_NORMAL);

        $rows = $builder->with('icons')->get()->toArray();

        return $rows;
    }

    /**
     * 获取某一 icon lib
     *
     * @param int $libId
     * @param int $userId
     * @param int $accountId
     * @return array|null
     */
    public static function getIconLib($libId, $userId, $accountId = 0)
    {
        $builder = static::where('id', $libId);

        if ($userId) {
            $builder->where('accountId', $userId)->where('accountType', static::ACCOUNT_TYPE_PERSONAL);
        } else {
            $builder->where('accountId', $accountId)->where('accountType', static::ACCOUNT_TYPE_TEAM);
        }

        $builder->where('status', static::STATUS_NORMAL);

        $lib = $builder->with('icons')->first();

        return is_null($lib) ? null : $lib->toArray();
    }

    /**
     * 格式化
     *
     * @param array $lib
     */
    public static function filter(& $lib)
    {
        Icon::filterIcons($lib['icons']);

        $lib = [
            'id'    => $lib['id'],
            'name'  => $lib['name'],
            'icons' => $lib['icons']
        ];
    }

    /**
     * 格式化一组
     *
     * @param array $libs
     */
    public static function filterLibs(& $libs)
    {
        foreach ($libs as &$lib) {
            static::filter($lib);
        }
    }
}