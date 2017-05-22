<?php
/**
 * Work.php
 *
 * 作者: lihongsheng (dannyzml@qq.com)
 * 创建日期: 17/5/10 上午12:39
 * 修改记录:
 *
 * $Id$
 */
namespace Chat\Model;

use customer\Lib\PcntlModel;

use customer\Lib\Connect\ConnectInterface;

use customer\Lib\Protocol\WebSocket;

use customer\Lib\Connect\TcpConnect;

use customer\Lib\Timer;

use customer\Lib\Events\LibEvent;
use customer\Lib\Events\Event;



class Work
{

    /**
     * @var customer\Lib\PcntlModel
     */
    private $pcntl;

    protected $_connect = [];//所有的链接

    //uid
    protected $_uid = [];

    //组
    protected $_group = [];

    protected $event;

    protected $protocol;

    /**
     * 保持心跳数据格式内容
     * @var
     */
    protected $pingData         = '{"type":"ping","message":""}';
    const MSG_TYPE_PING         = 'ping';
    const MSG_TYPE_GET_GROUP    = 'getGroup';//获取组列表
    const MSG_TYPE_BIND_UID     = 'bindUid';//消息
    const MSG_TYPE_BIND_GROUP   = 'bindGroup';//消息
    const MSG_TYPE_MESSAGE      = 'message';//消息


    public function __construct()
    {

        $this->pcntl = new PcntlModel(0);
        ConnectInterface::$protocol = new WebSocket();
        ConnectInterface::$work = $this;

        $this->event = new Event();

    }


    public function run() {


        $this->pcntl->setDaemonize();
        $this->pcntl->daemonize();
        $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($listen, '0.0.0.0', 6090);
        socket_listen($listen);
        $this->event->add($listen, LibEvent::EV_READ,array($this,'accept'));
        Timer::init($this->event);
        Timer::add(30,array($this,'ping'));
        echo (int)$listen;
        $this->event->loop();

    }

    public function addEvent($fd, $flag, $func, $args = null) {
        echo 'ADDEVENT'.(int)$fd.PHP_EOL;
        $this->event->add($fd, $flag, $func, $args);
    }

    public function delEvent($fd, $flag) {
        $this->event->del($fd, $flag);
    }

    public function delConnect($id) {
        unset($this->_connect[$id]);
    }

    public function accept($fd) {

        $connect = new TcpConnect($fd);
        $this->_connect[$connect->id] = $connect;

        //call_user_func(array($this,'onConnect'),$connect);
        echo 'ACCEPT'.$connect->id.PHP_EOL;

    }


    public function setEvent($key,$func) {

        /*switch ($key) {
            case 'onProtocolClose':
                $this->onProtocolClose = $func;
                break;

        }*/
        $this->$key = $func;

    }


    /*protected $onProtocolClose; //协议关闭回调
    protected $onConnect; //建立链接回调
    protected $onMessage; //消息回调
    protected $onClose; //关闭回调*/

    public function onProtocolClose(ConnectInterface $connect) {

    }

    public function onConnect(ConnectInterface $connect) {
        $connect->send(json_encode(['mes'=>'你好']));
    }

    public function onMessage($message, ConnectInterface $connect) {
        $message = json_decode($message,true);
        switch ($message['type']) {
            case self::MSG_TYPE_BIND_GROUP:
                $this->_group[$message['sendtoid']][$connect->id]['conn'] = $connect;
                $this->_group[$message['sendtoid']][$connect->id]['uid'] = $message['uid'];
                foreach ($this->_group[$message['sendtoid']] as $_conn) {
                    $_conn->send(["type"=>self::MSG_TYPE_MESSAGE,"msg"=>$message['msg']]);
                }
                break;
            case self::MSG_TYPE_MESSAGE:
                foreach ($this->_group[$message['sendtoid']] as $_conn) {
                    $_conn->send(json_encode(["type"=>self::MSG_TYPE_MESSAGE,"msg"=>$message['msg']]));
                }
                break;
            case self::MSG_TYPE_PING:
                break;
            case self::MSG_TYPE_BIND_UID:
                $this->_uid[$message['uid']][$connect->id] = $connect;
                $this->_uid[$message['uid']]['name'] = $message['name'];
                $this->_uid[$message['uid']]['uid'] = $message['uid'];
                break;
            case self::MSG_TYPE_GET_GROUP:
                $msg = [];
                foreach ($this->_group as $_connect) {
                    $msg[] = [
                        'name'=>$this->_uid[$_connect['uid']]['name'],
                        'uid'=>$_connect['uid'],
                    ];
                }
                $connect->send(json_encode(["type"=>self::MSG_TYPE_MESSAGE,"msg"=>$msg]));
        }

    }

    public function onClose(ConnectInterface $connect) {
        unset($this->_connect[$connect->id]);
        unset($this->_group[$connect->id]);
        foreach ($this->_uid as $k=>$v) {
            if($v[$connect->id]) {
                unset($this->_uid[$k]);
            }
        }

    }




    /**
     * 保持心跳操作
     */
    public function ping() {

        foreach ($this->_connect as $k=> $connect) {
            if($connect->resNoCount >= $connect->resNoMaxCount) {
                $connect->destory();
            } else {
                $connect->send($this->pingData);
                $connect->resNoCount++;
            }
        }
    }



}