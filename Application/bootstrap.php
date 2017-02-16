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
        //捕获异常注册
        set_exception_handler('bootstrap::Exception');
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


    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
     public static function Exception($e) {
        $error = array();
        $error['message']   =   $e->getMessage();
        $trace              =   $e->getTrace();
        if('E'==$trace[0]['function']) {
            $error['file']  =   $trace[0]['file'];
            $error['line']  =   $trace[0]['line'];
        }else{
            $error['file']  =   $e->getFile();
            $error['line']  =   $e->getLine();
        }
        $error['trace']     =   $e->getTraceAsString();
         if(customer\Lib\Tools::isCli()){
            foreach($error as $k=>$v){
                echo $k."::   ".$v.PHP_EOL;
            }
         } else {
             header('HTTP/1.1 404 Not Found');
             header('Status:404 Not Found');
             foreach($error as $k=>$v){
                 echo $k."::........".$v.'<br/>';
             }
         }
    }

    public function run()
    {
        $router = new customer\Lib\Router();
        $router->dispatcher();
        $class = $router->getModule().'\\'.'Controller\\'.$router->getMethod();
        $action = $router->getAction().'Action';
        $model = new $class('',$router);

        if(!method_exists($model,$action)) {
            throw new \Exception("NOT FIND action IN ".$router->getMethod());
        }
        $model->$action();

    }
}