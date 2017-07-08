<?php
/**
 * RedisModel.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/7/8 下午2:41
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib\Db;

class RedisModel {
    private static $redis;

    private function __construct()
    {
        $model = new \Redis();
        $model->pconnect('127.0.0.1','6379');
        return $model;
    }

    public static function getRedis() {
        if(!self::$redis) {
            self::$redis = new self();
        }
        return self::$redis;
    }
}