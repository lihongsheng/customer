<?php
/**
 * SocketSelect.php
 * 基于事件轮询的socket处理
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/8 下午11:54
 * 修改记录:
 *
 * $Id$
 */

namespace customer\Lib;

use Cli\Model\Event;

abstract class SocketSelect
{

    private $_connectLink = array();
    private $_connectValues = array();
    private $_registerLink = array();
    private $_workLink    = array();
    private static $_connectionIdRecorder = 0;

    protected $_ClientLinks = [];  //客服端的链接
    protected $_uidLinks     = []; //uid与客服端绑定

    const SOCKET_TYPE_ACCEPT = 1;
    const SOCKET_TYPE_READ   = 2;
    const SOCKET_TYPE_CLOSE  = 3;

    protected $_links = [];

    protected  $event;
    protected $handle = false;

    public function setEvent(Event $event) {
        $this->event = $event;
    }

    //创建
    //protected static $listen;
    //作为客户端的链接
    //protected static $clientListen = array();

//为work创建并监听 端口
    public function createAndListen($ip, $port)
    {
        $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($listen, $ip, $port);
        socket_listen($listen);
        $this->_links["server"] = $listen;
    }


    public function clientListen($ip,$port)
    {
        $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(!socket_connect($listen,$ip,$port)) {
            $errorId = socket_last_error($listen);
            throw new \Exception("NET ERROR ".socket_strerror($errorId));
        }
        $this->_links["client"] = $listen;
    }



    /**
     * @throws \Exception
     */
    public function accept()
    {
        $links = $this->_links;

        $intLinks = socket_select($links,$write=null,$except=null,0);//无阻赛运行
        if($intLinks === false) {
            $errorId = socket_last_error();
            if($errorId != 4) {
                throw new \Exception(" accept ERROR ".$errorId.'-'.EINTR.' string '.socket_strerror($errorId));
            }
        }
        $buffer = '';

        foreach($links as $k=>$r){
            //if(in_array($r,$server)) {
            if($r === $this->_links['server']) {
                $eventLink = socket_accept($r);
                $id = (int)$eventLink;
                $this->_ClientLinks[$id] = [
                    'fd'    =>$eventLink,
                    'handle'=>false,
                    'uid'   => ''
                ];
                $this->_links[$id] = $eventLink;

                $this->event->onConnect($id,'');
            } else {
                $data = socket_recv($r,$buffer ,2048,0);
                $id = (int)$r;
                if($data < 7) {
                    $this->delLinks($id);
                    $this->event->onClose($id);
                } else {
                    if($this->handle && !$this->_ClientLinks['handle']) {
                        echo "websocket handle".PHP_EOL;
                        $this->handshake($buffer);
                    } else {
                        $buffer = $this->decode($buffer);
                        $this->event->onMessage($buffer,$id);
                    }
                }

            }
        }

    }


    protected function delLinks($id){
        socket_close($this->_links[$id]);
        unset($this->_ClientLinks[$id]);
        unset($this->_links[$id]);
    }

    public function sendOne($msg,$id)
    {
        $sign = $this->_links[$id];
        $msg  = $this->encode($msg);
        $no   = socket_write($sign, $msg, strlen($msg));
        return $no;

    }

    public function sendMutily($msg,array $ids)
    {
        foreach($ids as $r) {
            $this->sendOne($msg,$r);
        }
    }

    public function close($id)
    {
        $this->delLinks($id);
    }

    /**
     * 生成connection id
     * @return int
     */
    public static function generateConnectionId()
    {
        $max_unsigned_int = 4294967295;
        if (self::$_connectionIdRecorder >= $max_unsigned_int) {
            self::$_connectionIdRecorder = 1;
        }
        $id = self::$_connectionIdRecorder ++;
        return $id;
    }

    abstract public function encode($msg);
    abstract public function decode($buffer);
    abstract public function handshake($buffer);


}