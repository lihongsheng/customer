<?php
/**
 * Server.php
 * 单进程版listen
 * 作者: 李红生 (dannyzml@qq.com)
 * 创建日期: 17/4/18 下午10:39
 * 修改记录:
 *
 * $Id$
 */

namespace Cli\Model;

use customer\Lib\Config;
use customer\Lib\PcntlModel;
use customer\Lib\RedisQueue;
use customer\Lib\WebSocket;
use customer\Lib\Timer;
use Cli\Model\Event;
use customer\Lib\Queue;

class Server extends Event
{
    private   $_links = [];
    private   $userMap = [];
    private   $_PID = ''; //自身进程ID


    public function __construct()
    {
        //创建getway端口
        $this->getwayServer = WebSocket::createAndListen(Config::GetwayIp,Config::GetwayPort);

        $this->_links['getS']     = $this->getwayServer;
        $this->beforeWork();
        $this->pcntlModel  = new PcntlModel(1);
    }

    public function run()
    {
        $this->pcntlModel->setWork($this,'work');
        $this->pcntlModel->start();
    }

    public function work()
    {
        $this->_PID = posix_getpid();

        $serLink ['server'] = $this->getwayServer;
        while(true) {
            $data = WebSocket::accept($this->_links,$server);

        }

    }


}