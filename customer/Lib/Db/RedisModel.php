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

    }

    public static function getRedis($reConnect = false) {
        if(!$reConnect) {
            if (!self::$redis) {
                $model = new \Redis();
                $model->pconnect('127.0.0.1', '6379');
                self::$redis = $model;
            }
        } else {
            $model = new \Redis();
            $model->pconnect('127.0.0.1', '6379');
            self::$redis = $model;
        }

        //return $model;
        return self::$redis;
    }
}