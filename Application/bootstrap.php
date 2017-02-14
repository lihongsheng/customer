<?php
/**
 * bootstart.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/14 上午12:24
 * 修改记录:
 *
 * $Id$
 */

class bootstrap
{

    protected  static $ClassMap= [];
    public function init()
    {
        // 注册AUTOLOAD方法
        spl_autoload_register('bootstrap::autoload');
        return $this;
    }

    /**
     * 自动加载
     */
    public static function autoload($class)
    {
        if(self::$ClassMap[$class]) {
            include_once self::$ClassMap[$class];
        } else {
            exit($class);
        }
    }

    public function run()
    {
        $router = new customer\Lib\Router();
    }
}