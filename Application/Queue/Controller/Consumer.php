<?php
/**
 * Consumer.php
 *
 * 作者: 队列消费者
 * 创建日期: 17/8/12 下午9:20
 * 修改记录:
 *
 * $Id$
 */
namespace Queue\Controller;

use customer\Lib\MutliProcess;
use Queue\Model\Mq;
use customer\Lib\Controller;

class Consumer extends Controller
{

    public function indexAction() {
        $host = 'localhost';
        $port = '5672';
        $user = 'guest';
        $pwd  = 'guest';
        $vhost= '/';


        $server = new Mq($host, $port, $user, $pwd, $vhost);
        $server->msgWork = function ($msg) {
            echo $msg.PHP_EOL;
        };
        $server->queue        = 'queue';
        $server->exchange     = 'exchange';
        $server->routing_key  = 'exchange_queue';
        $server->consumer_tag = $server->routing_key;

        $multiModel = new MutliProcess(4,true,$this->router);
        $multiModel->setWork($server);
        $multiModel->start();

    }



}