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

    /**
     * 未回复次数
     * @var
     */
    public $resNoCount = -1;

    /**
     * 未回复最大次数
     * @var
     */
    public $resNoMaxCount = 3;

    protected $_eventFlag;


    public function __construct($fd)
    {
        $this->_fd = socket_accept($fd);
        $this->id  = (int)$this->_fd;
        $this->_eventFlag = EventInterface::EV_READ;
        self::$work->addEvent($this->_fd,$this->_eventFlag, array($this, 'Read'));
        $this->isHandle = !self::$protocol->isHandle();
    }



    public function Read($fd) {
        $buffer = '';
        $data = socket_recv($fd,$buffer,2048,0);
        if($data === false) {
            echo 'close form read'.socket_strerror(socket_last_error());
            $this->destory();
        }
        $this->resNoCount = -1;//有消息未回复次数为-1
        /*if(!$this->isHandle) {
            echo PHP_EOL.'[READ]:::'.$data.'::'.$buffer.PHP_EOL;
        } else {
            echo PHP_EOL.'[READ]:::'.$data.'::'.self::$protocol->decode($buffer).PHP_EOL;
        }*/

        if(self::$protocol->isHandle() && !$this->isHandle) {
            $protocol = self::$protocol->handle($buffer);
            echo $protocol.PHP_EOL;
            socket_write($this->_fd,$protocol,strlen($protocol));
            $this->isHandle = true;
            call_user_func(array(self::$work,'onConnect'),$this);
        } else {
            $buffer = self::$protocol->decode($buffer);
            call_user_func(array(self::$work,'onMessage'),$buffer,$this);
        }
        return;
    }

    public function send($buffer) {
        $buffer = self::$protocol->encode($buffer);
        socket_write($this->_fd,$buffer,strlen($buffer));
    }


    public function destory() {

        self::$work->delEvent($this->_fd,$this->_eventFlag);
        self::$work->delConnect($this->id);

        if (method_exists(self::$work, 'onClose')) {
            call_user_func(array(self::$work,'onClose'),array($this));
        }
        if (method_exists(self::$work, 'onProtocolClose')) {
            call_user_func(array(self::$work,'onProtocolClose'),array($this));
        }
        socket_close($this->_fd);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}