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



use customer\Lib\Connect\ConnectInterface;

use customer\Lib\Db\RedisModel;
use customer\Lib\Protocol\WebSocket;

use customer\Lib\Connect\TcpConnect;

use customer\Lib\Queue\RedisQueue;
use customer\Lib\Timer;

use customer\Lib\Events\LibEvent;
use customer\Lib\Events\Event;
use customer\Lib\WorkInterface;


class Work extends WorkInterface
{



    protected $_connect = [];//所有的链接

    //uid
    protected $_uid = [];

    //组
    protected $_group = [];

    /**
     * @var Event
     */
    protected $event;

    protected $protocol;

    /**
     * @var
     */
    protected $listen;

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
    /**
     * @var RedisQueue
     */
    protected $queue;
    /**
     * @var \redis
     */
    protected $redis;
    /**
     * @var
     */
    protected $pid;


    protected $masterLink;


    protected $masterBuffer;


    protected $memKey = 'members::';
    /**
     * Work constructor.
     * @param $listen
     */
    public function __construct($listen)
    {

        ConnectInterface::$protocol = new WebSocket();
        ConnectInterface::$work = $this;
        $this->listen = $listen;


    }


    /**
     * 设置进程名字
     *
     * @param string $title
     * @return void
     */
    protected  function setProcessTitle($title)
    {
        if(function_exists("cli_set_process_title")) {
            cli_set_process_title($title);
        } else if (extension_loaded('proctitle') && function_exists('setproctitle')) {
            @setproctitle($title);
        }
    }



    /**
     * 信号处理
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {

        switch ($signal) {
            // Stop.
            case SIGINT:
                $this->stop();
                break;
            case SIGTERM:
                $this->stop();
                break;
            // Reload.
            case SIGUSR1:
                //$this->reload();
                break;
            // Show status.
            case SIGUSR2:
                //$this->writeStatisticsToStatusFile();
                break;
        }
    }


    /**
     *
     */
    public function stop() {
        echo "child KILL ::::".posix_getpid().PHP_EOL;
        exit(0);
    }

    public function installSignal()
    {
        $this->event->add(SIGINT, LibEvent::EV_SIGNAL, array($this, 'signalHandler'));
        $this->event->add(SIGTERM, LibEvent::EV_SIGNAL, array($this, 'signalHandler'));
    }
    /**
     * work运行
     */
    public function run() {

        $this->setProcessTitle("work:listen");

        sleep(3);
        $this->redis = RedisModel::getRedis(true);
        $this->redis->select(1);
        $this->queue = new RedisQueue();

        $this->pid = posix_getpid();
        $this->event = new Event();
        $this->installSignal();

        /**
         *
         */
        $this->masterToLink();
        $this->event->add($this->masterLink, LibEvent::EV_READ,array($this,'masterMsg'));

        $this->event->add($this->listen, LibEvent::EV_READ,array($this,'accept'));
        Timer::init($this->event);
        Timer::add(30,array($this,'ping'));
        $this->event->loop();

    }

    /**
     * 链接主进程，并绑定 pid
     */
    protected function masterToLink() {
        $this->masterLink  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($this->masterLink,'127.0.0.1',20073);
        $msg = json_encode(['type'=>'bind','pid'=>$this->pid])."\n";
        socket_write($this->masterLink,$msg,strlen($msg));
    }


    /**
     * 处理主进程发过来的信息
     * @param $fd
     */
    public function masterMsg($fd) {
        $buffer = '';
        $data = socket_recv($fd,$buffer,2048,0);

        if($data < 0) {
            $this->masterToLink();
            $msg = json_encode(['type'=>'bind','pid'=>$this->pid])."\n";
            socket_write($this->masterLink,$msg,strlen($msg));
            $this->event->del($fd, LibEvent::EV_READ);
            $this->event->add($this->masterLink, LibEvent::EV_READ,array($this,'masterMsg'));
        }
        if($buffer == '') {
            return;
        }
        $this->masterBuffer .= $buffer;
        //解析text协议
        while(true) {
            $pos = strpos($this->masterBuffer, "\n");
            if($pos === false) {
                break;
            }
            $pos = $pos + 1;
            $tmp = substr($buffer,0,$pos);
            $this->masterBuffer = substr($this->masterBuffer,$pos);
            $tmp = json_decode($tmp,true);
            if($tmp['type'] != 'bind' && $tmp['type'] != 'ping') {
                $uid = $tmp['uid'];
                if($uid && $this->_uid[$uid]) {
                    //发送消息到对应的用户端
                    $this->_uid[$uid]['conn']->send(json_encode($tmp));
                }
            }
        }
    }


    /**
     * 添加时间
     * @param $fd
     * @param $flag
     * @param $func
     * @param null $args
     */
    public function addEvent($fd, $flag, $func, $args = null) {
        //echo 'ADDEVENT'.(int)$fd.PHP_EOL;
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
        echo "connet is aleardy".PHP_EOL;
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
                /*$this->_group[$message['sendtoid']][$connect->id]['conn'] = $connect;
                $this->_group[$message['sendtoid']][$connect->id]['uid'] = $message['uid'];
                $this->_group[$message['sendtoid']][$connect->id]['name'] = $message['name'];*/
                //获取 全部用户UID
                $gid = $message['sendtoid'];
                $uids = $this->redis->smembers($this->memKey.$gid);

                $uids[] = $message['uid'];
                //把用户UID绑定到对应的组
                $this->redis->sAdd($this->memKey.$gid,$message['uid']);


                foreach ($uids as $v) {
                    if($this->_uid[$v]) {
                        $msg = json_encode(["type" => self::MSG_TYPE_MESSAGE, "msg" => $message['msg'] . ":::" . posix_getpid(), 'uid' => $message['uid'], 'name' => $message['name'], 'time' => date('Y-m-d H:i:s')]);
                        $this->_uid[$v]['conn']->send($msg);
                    } else {
                        $this->queue->send($msg);
                    }
                }

                break;

                //用户组消息
            case self::MSG_TYPE_MESSAGE:
                $gid = 1;//测试默认只有一个组
                //获取所有组用户的UID
                $uids = $this->redis->smembers($this->memKey.$gid);
                foreach ($uids as $v) {
                    if($this->_uid[$v]) {
                        $msg = json_encode(["type"=>self::MSG_TYPE_MESSAGE,"msg"=>$message['msg'],'uid'=>$message['uid'],'name'=>$message['name'],'time'=>date('Y-m-d H:i:s')]);
                        $this->_uid[$v]['conn']->send($msg);
                    } else {
                        $this->queue->send($msg);
                    }
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
                $this->_uid[$message['uid']]['conn'] = $connect;
                $this->_uid[$message['uid']]['name'] = $message['name'];
                $this->_uid[$message['uid']]['uid']  = $message['uid'];

                //存入用户信息 到 REDIS
                $this->redis->HSET($message['uid'],[
                    'conn'=>$connect->id,
                    "name"=>$message['name'],
                    "IP"  =>'127.0.0.1',
                    "pid" =>$this->pid,
                ]);
                //echo self::MSG_TYPE_BIND_UID.':::'.json_encode($message).PHP_EOL;
                break;

                //获取组成员消息
            case self::MSG_TYPE_GET_GROUP:
                $msg = [];
                $gid = 1;
                $uids = $this->redis->smembers($this->memKey.$gid);
                foreach ($uids as $v) {
                    $_uid = $v;
                    $_name = $this->_uid[$_uid] ? $this->_uid[$_uid]['name'] : $this->redis->hGet($_uid)['name'];
                    $msg[] = [
                        'name'=>$_name,
                        'uid'=>$_uid,
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
        //请求用户列表
        foreach ($this->_uid as $k=>$v) {
            if($v['conn']->id == $connect->id) {
                unset($this->_uid[$k]);
                $this->redis->hDel($k);
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