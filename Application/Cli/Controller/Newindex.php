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


    public function testAction() {
        $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($listen, '0.0.0.0', 6090);
        socket_listen($listen);
        $bEvent = event_base_new();
        $event  = event_new();
        event_set($event,$listen,EV_READ|EV_PERSIST,function($listen,$flag,$base){
            $accept = socket_accept($listen);
            $id = (int)$accept;
            $buffer = event_buffer_new($accept,function(){},function(){},function(){},$id);
            event_buffer_base_set($buffer,$base);
            event_buffer_timeout_set($buffer, 30, 30);
            event_buffer_enable($accept,EV_READ|EV_WRITE|EV_PERSIST,0, 0xffffff);

        },$bEvent);
        event_base_set($event,$bEvent);
        event_add($event);
        event_base_loop($bEvent);


    }
}