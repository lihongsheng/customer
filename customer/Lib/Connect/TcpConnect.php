<?php
/**
 * TcpConnect.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/5/4 下午10:40
 * 修改记录:
 *
 * $Id$
 */

namespace customer\Lib\Connect;

use customer\Lib\Events\EventInterface;

class TcpConnect extends ConnectInterface
{
    public $isHandle = false;

    private $_fd;

    public $id;


    public function __construct($fd)
    {
        $this->_fd = socket_accept($fd);
        $this->id  = (int)$this->_fd;
        self::$work->addEvent($this->_fd,EventInterface::EV_READ, array($this, 'Read'));

    }


    public function Read($fd) {
        $buffer = '';
        $data = socket_recv($fd,$buffer,2048,0);
        if($data < 1) {
            $this->destory();
        }

        if(!self::$protocol->isHandle()) {
            $protocol = self::$protocol->handle($buffer);
            socket_write($this->_fd,$protocol,strlen($protocol));
        } else {
            $buffer = self::$protocol->decode($buffer);
            call_user_func(self::$work->onMessage,array($buffer,$this));
        }
        return;
    }

    public function send($buffer) {
        $buffer = self::$protocol->encode($buffer);
        socket_write($this->_fd,$buffer,strlen($buffer));
    }


    public function destory() {
        self::$work->delEvent($this->_fd,EventInterface::EV_READ);
        //self::$work->delEvent($this->_fd,EventInterface::EV_READ);
        self::$work->delConnect($this->id);
        if (method_exists(self::$work, 'onProtocolClose')) {
            call_user_func(array(self::$work,'onProtocolClose'),$this);
        }
        socket_close($this->_fd);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}