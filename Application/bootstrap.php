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
        //$index = md5($class);
        if(self::$ClassMap[$class]) {
            include_once self::$ClassMap[$class];
        } elseif(false !== stripos($class,'\\')) {
            $name = str_replace('\\','/',$class);
            if(false !== stripos($name,'customer')){
                self::$ClassMap[$class] = ROOT_PATH.$name.'.php';
                require_once self::$ClassMap[$class];
            } elseif(file_exists(APP_PATH.$name.'.php')) {
                self::$ClassMap[$class] = APP_PATH.$name.'.php';
                require_once self::$ClassMap[$class];
            } else {
                throw new Exception("NOT FIND ".$name.'.php');
            }
        }
    }

    public function run()
    {
        $router = new customer\Lib\Router();
    }
}