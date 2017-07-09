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

    /**
     * @var int
     */
    protected $_eventFlag;

    /**
     * @var string
     */
    protected $_recv = '';

    /**
     * @var string
     */
    protected $_tmpData = '';

    /**
     * 最大缓存包
     * @var
     */
    protected $_maxBufferSize = 65536;


    public function __construct($fd)
    {
        $this->_fd = socket_accept($fd);
        $this->id  = (int)$this->_fd;
        $this->_eventFlag = EventInterface::EV_READ;
        self::$work->addEvent($this->_fd,$this->_eventFlag, array($this, 'Read'));
        $this->isHandle = !self::$protocol->isHandle();
    }

    /**
     * 设置已经接受的缓存内容
     * @param $buffer
     */
    public function setRecv($buffer){
        $this->_recv = $buffer;
    }

    /**
     * 设置已转码的临时数据待和后边的数据合并成一个完整数据
     * @param $tmpData
     */
    public function setTmpData($tmpData) {
        $this->_tmpData = $tmpData;
    }


    /**
     * 从 socket读取发送的内容
     * @param $fd
     */
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
            //echo $protocol.posix_getpid();
            socket_write($this->_fd,$protocol,strlen($protocol));
            $this->isHandle = true;
            call_user_func(array(self::$work,'onConnect'),$this);
        } else {
            $this->_recv .= $buffer;

            while (true) {

                try {
                    $tmpLen = self::$protocol->input($this->_recv, $this);
                }catch (\Exception $e) {
                    echo $e->getMessage();
                }
                $tmpStr = '';
                if($tmpLen === 0) {

                    break;
                } else {
                    $tmpStr = substr($this->_recv,0,$tmpLen);

                    $this->_recv = substr($this->_recv,$tmpLen);
                }
                //$buffer = self::$protocol->decode($buffer);
                if($tmpStr) {

                    $decodeData = $this->_tmpData . self::$protocol->decode($tmpStr);
                    echo $decodeData."::::PID".posix_getpid().PHP_EOL;
                    $this->_tmpData = '';
                    call_user_func(array(self::$work,'onMessage'),$decodeData,$this);
                }

            }

            /**
             * 处理判断最大缓存的buffer，避免伪造或是错误导致的内存溢出
             */
            if(strlen($this->_recv) > $this->_maxBufferSize) {
                echo 'buffer is Exceed '.$this->_maxBufferSize.' bytes';
                $this->destory();
            }

        }
        return;
    }


    /**
     * 发送内容
     * @param $buffer
     */
    public function send($buffer) {
        $buffer = self::$protocol->encode($buffer);
        $isSend = socket_write($this->_fd,$buffer,strlen($buffer));
        //判断是否写入成功
        if($isSend === false) {
            echo 'close form read'.socket_strerror(socket_last_error());
            $this->destory();
        }
    }


    /**
     * 注销服务
     */
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