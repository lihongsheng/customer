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


    protected $_recv = '';

    protected $_tmpData = '';


    public function __construct($fd)
    {
        $this->_fd = socket_accept($fd);
        $this->id  = (int)$this->_fd;
        $this->_eventFlag = EventInterface::EV_READ;
        self::$work->addEvent($this->_fd,$this->_eventFlag, array($this, 'Read'));
        $this->isHandle = !self::$protocol->isHandle();
    }


    public function setRecv($buffer){
        $this->_recv = $buffer;
    }


    public function setTmpData($tmpData) {
        $this->_tmpData = $tmpData;
    }


    public function Read($fd) {
        $buffer = '';
        $data = socket_recv($fd,$buffer,2048,0);
        if($data === false) {
            echo 'close form read'.socket_strerror(socket_last_error());
            $this->destory();
        }
        $this->resNoCount = -1;//有消息未回复次数为-1

        if($buffer == '') {
            return;
        }

        if(self::$protocol->isHandle() && !$this->isHandle) {
            $protocol = self::$protocol->handle($buffer);
            echo $protocol.PHP_EOL;
            socket_write($this->_fd,$protocol,strlen($protocol));
            $this->isHandle = true;
            call_user_func(array(self::$work,'onConnect'),$this);
        } else {
            $this->_recv .= $buffer;
            while (true) {
                $tmpLen = self::$protocol->input($this->_recv,$this);
                $tmpStr = '';
                if($tmpLen === 0) {
                    break;
                } else {
                    $tmpStr = substr($this->_recv,0,$tmpLen);
                    $this->_recv = substr($this->_recv,$tmpLen);
                }
                //$buffer = self::$protocol->decode($buffer);
                if($tmpStr) {
                    echo $tmpStr;
                    $decodeData = $this->_tmpData . self::$protocol->decode($tmpStr);
                    $this->_tmpData = '';
                    call_user_func(array(self::$work,'onMessage'),$decodeData,$this);
                }

            }

        }
        return;
    }

    public function send($buffer) {
        $buffer = self::$protocol->encode($buffer);
        $isSend = socket_write($this->_fd,$buffer,strlen($buffer));
        //判断是否写入成功
        if($isSend === false) {
            echo 'close form read'.socket_strerror(socket_last_error());
            $this->destory();
        }
    }


    public function destory() {

        self::$work->delEvent($this->_fd,$this->_eventFlag);
        self::$work->delConnect($this->id);

        if (method_exists(self::$work, 'onClose')) {
            call_user_func_array(array(self::$work,'onClose'),array($this));
        }
        if (method_exists(self::$work, 'onProtocolClose')) {
            call_user_func_array(array(self::$work,'onProtocolClose'),array($this));
        }
        socket_close($this->_fd);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}