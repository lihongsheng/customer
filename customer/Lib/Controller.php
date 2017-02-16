<?php
/**
 * Controller.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/15 下午11:42
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

use customer\Lib\Config;

abstract class Controller
{
    protected $module;
    protected $method;
    protected $action;
    protected $viewModel;
    protected $params = [];
    protected $_name;
    protected $assignList = [];

    public function __construct($viewModel,Router $router){
        $this->module = $router->getModule();
        $this->method = $router->getMethod();
        $this->action = $router->getAction();
        $this->params = $router->getParams();
        $this->viewModel = $viewModel;
        $this->_name = get_class($this);
        $this->init();
    }


    protected function init()
    {

    }

    /**
     * @param array $body
     */
    protected function out(array $body)
    {

    }

    protected function assign($key, $val)
    {
        //$this->viewModel;
        $this->assignList[$key] = $val;
    }

    protected function display($viewPath = '')
    {
        $viewPath = $viewPath ? $viewPath : $this->action;
        $viewPath = APP_PATH.$this->module.'/View/'.$this->method.'/'.$viewPath.'.phtml';
        if(!file_exists($viewPath)){
            throw new \Exception($this->action." VIEW is NO find");
        } else {
            extract($this->assignList, EXTR_PREFIX_SAME);
            require_once $viewPath;
        }

    }
}