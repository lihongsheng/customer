<?php
/**
 * .php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/15 上午11:58
 * 修改记录:
 *
 * $Id$
 */
namespace Cli\Controller;

use customer\Lib\Config;
use customer\Lib\Controller;
use customer\Lib\WebSocket;

use Cli\Model\Register;

class Index extends Controller
{

    public function startServerAction()
    {
        echo 'hello word;'.PHP_EOL;
    }

    public function startRegisterAction()
    {
        $register = new Register();
        $register->run();

    }

    public function startWorkAction()
    {

    }
}