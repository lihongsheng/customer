<?php
/**
 * Router.php
 * 路由控制器
 * 只支持PATH_INFO和CLI模式下
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/12 下午4:32
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

use customer\Lib\Tools;
use customer\Lib\Config;


class Router
{

    protected  $module;
    protected  $method;
    protected  $action;
    private    $pathInfo;
    protected  $params = [];
    private $returnArray = [
        'classPath' => '',
        'action'=>'',
    ];


    public function dispatcher(){
        $isCli = Tools::isCli();
        if($isCli) {
            $this->pathInfo = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : (isset($argv[1]) ? $argv['1'] : '');
        } else {
            //获取PATH_INFO
            $this->pathInfo = str_replace(array('//', '../','./'), '/', trim($_SERVER['PATH_INFO'], '/'));
        }

        $this->formatUri();
        $this->setParams();

        return $this->returnArray;
    }



    protected function setParams()
    {
        if($this->pathInfo == ''){
            $this->setDefaultPath();
        } else {
            $uri = explode('/',trim($this->pathInfo,'/'));
            $this->module = ucfirst(strtolower($uri[0]));
            $this->method = ucfirst(strtolower($uri[1]));
            $this->action = strtolower($uri[2]);

            $len = count($uri);
            if($len > 3) {
                for($i = 3;$i<$len;$i++){
                    $this->params[$uri[$i]] = $uri[$i+1];
                }
            }
        }

        $this->returnArray['classPath'] = APP_PATH.$this->module.'/Controller/'.$this->method.'.php';
        $this->returnArray['action'] = $this->action."Action";
        if(!file_exists($this->returnArray['classPath'])){
           throw new \Exception(" file not find ".$this->returnArray['classPath']);
        }

        return;

    }



    public function getParams()
    {
        return $this->params;
    }

    private function formatUri()
    {
        $this->pathInfo = Tools::removeInvisibleCharacters($this->pathInfo,false);
        $slen = strlen(Config::$router['urlSuffix']);

        if (substr($this->uri_string, -$slen) === Config::$router['urlSuffix'])
        {
            $this->pathInfo = substr($this->uri_string, 0, -$slen);
        }

    }




    private function setDefaultPath()
    {
        $this->module = ucfirst(strtolower(Config::$router['defaultModule']));
        $this->method = ucfirst(strtolower(Config::$router['defaultMethod']));
        $this->action = strtolower(Config::$router['defaultAction']);
        //$this->returnArray['class'] = $this->method;
        //$this->returnArray['action'] = $this->action."Action";


    }

    public function getModule()
    {
        return $this->module;
    }


    public function getMethod()
    {
        return $this->method;
    }

    public function getAction()
    {
        return $this->action;
    }
}