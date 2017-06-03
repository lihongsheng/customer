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


    }


    /**
     * work运行
     */
    public function run() {


        $this->pcntl->setDaemonize();
        $this->pcntl->daemonize();
        $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($listen, '0.0.0.0', 20072);
        socket_listen($listen);
        $this->pcntl->resetStd();

        $this->event = new Event();
        $this->event->add($listen, LibEvent::EV_READ,array($this,'accept'));
        Timer::init($this->event);
        Timer::add(30,array($this,'ping'));
        //echo (int)$listen;
        $this->event->loop();

    }


    /**
     * 添加时间
     * @param $fd
     * @param $flag
     * @param $func
     * @param null $args
     */
    public function addEvent($fd, $flag, $func, $args = null) {
        echo 'ADDEVENT'.(int)$fd.PHP_EOL;
        $this->event->add($fd, $flag, $func, $args);
    }

    /**
     * 删除event事件
     * @param $fd
     * @param $flag
     */
    public function delEvent($fd, $flag) {
        $this->event->del($fd, $flag);
    }

    /**
     * 删除链接
     * @param $id
     */
    public function delConnect($id) {
        unset($this->_connect[$id]);
    }


    /**
     * 接受客户端的Tcp链接请求
     * @param $fd
     */
    public function accept($fd) {

        $connect = new TcpConnect($fd);
        $this->_connect[$connect->id] = $connect;

        //call_user_func(array($this,'onConnect'),$connect);
        //echo 'ACCEPT'.$connect->id.PHP_EOL;

    }


    /*public function setEvent($key,$func) {

        $this->$key = $func;

    }*/


    /*protected $onProtocolClose; //协议关闭回调
    protected $onConnect; //建立链接回调
    protected $onMessage; //消息回调
    protected $onClose; //关闭回调*/

    public function onProtocolClose(ConnectInterface $connect) {

    }

    /**
     *客户端建立链接的时候的事件
     * @param ConnectInterface $connect
     */
    public function onConnect(ConnectInterface $connect) {
        //$connect->send(json_encode(['msg'=>'你好','type'=>self::MSG_TYPE_MESSAGE]));
    }


    /**
     * 获的客户端消息的事件函数
     * @param $message
     * @param ConnectInterface $connect
     */
    public function onMessage($message, ConnectInterface $connect) {
        $message = json_decode($message,true);
        switch ($message['type']) {

            case self::MSG_TYPE_BIND_GROUP: //用户绑定用户组
                /**
                 * [组ID=>[
                 *     'connectID'=>[
                 *           'conn'=>ConnectInterface,
                 *           'uid' => '用户UID',
                 *           'name'=>'用户昵称'
                 *      ],
                 *      ...................
                 *    ],
                 *    ....................
                 *
                 * ]
                 */
                $this->_group[$message['sendtoid']][$connect->id]['conn'] = $connect;
                $this->_group[$message['sendtoid']][$connect->id]['uid'] = $message['uid'];
                $this->_group[$message['sendtoid']][$connect->id]['name'] = $message['name'];

                //向组用户发送组内广播
                foreach ($this->_group[$message['sendtoid']] as $_conn) {
                    $_conn['conn']->send(json_encode(["type"=>self::MSG_TYPE_MESSAGE,"msg"=>$message['msg'],'uid'=>$message['uid'],'name'=>$message['name'],'time'=>date('Y-m-d H:i:s')]));
                }

                //echo self::MSG_TYPE_BIND_GROUP.':::'.json_encode($message).PHP_EOL;
                break;

                //用户组消息
            case self::MSG_TYPE_MESSAGE:
                //向组用户发送组内广播
                foreach ($this->_group[$message['sendtoid']] as $_conn) {
                    $_conn['conn']->send(json_encode(["type"=>self::MSG_TYPE_MESSAGE,"msg"=>$message['msg'],'uid'=>$message['uid'],'name'=>$message['name'],'time'=>date('Y-m-d H:i:s')]));
                }
                //echo self::MSG_TYPE_MESSAGE.':::'.json_encode($message).PHP_EOL;
                break;
                //ping消息
            case self::MSG_TYPE_PING:
                break;

                //绑定用户UID消息
            case self::MSG_TYPE_BIND_UID:
                /**
                 * 格式
                 * [
                 *     '用户UID' => [
                 *         'connectID'=>ConnectInterface,
                 *         'UID'=>'uid',
                 *         'name'=>'用户name'
                 *     ]
                 * ]
                 */
                $this->_uid[$message['uid']][$connect->id] = $connect;
                $this->_uid[$message['uid']]['name'] = $message['name'];
                $this->_uid[$message['uid']]['uid'] = $message['uid'];

                //echo self::MSG_TYPE_BIND_UID.':::'.json_encode($message).PHP_EOL;
                break;

                //获取组成员消息
            case self::MSG_TYPE_GET_GROUP:
                $msg = [];
                foreach ($this->_group[$message['sendtoid']] as $_connect) {
                    $msg[] = [
                        'name'=>$_connect['name'],
                        'uid'=>$_connect['uid'],
                    ];
                }
                $connect->send(json_encode(["type"=>"group","msg"=>$msg]));
                //echo self::MSG_TYPE_GET_GROUP.':::'.json_encode($msg).PHP_EOL;
        }

    }



    /**
     * 客户端关闭链接的时候的回调函数
     * @param ConnectInterface $connect
     */
    public function onClose(ConnectInterface $connect) {
        unset($this->_connect[$connect->id]);
        foreach ($this->_group as $k=>$v) {
            if($v[$connect->id]) {
                unset($this->_group[$k][$connect->id]);
                break;
            }
        }
        foreach ($this->_uid as $k=>$v) {
            if($v[$connect->id]) {
                unset($this->_uid[$k]);
                break;
            }
        }

    }




    /**
     * 保持客户端心跳
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