<?php
/**
 * NewIndex.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/4/19 上午12:45
 * 修改记录:
 *
 * $Id$
 */

namespace Cli\Controller;

use Cli\Model\Server;
use customer\Lib\Controller;


class NewIndex extends Controller
{
    public function startAction()
    {
        $ser = new Server();
        $ser->run();
    }
}