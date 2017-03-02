<?php
/**
 * Getway.php
 *
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/19 下午3:10
 * 修改记录:
 *
 * $Id$
 */
namespace Cli\Model;

use customer\Lib\Config;
use customer\Lib\PcntlModel;
use customer\Lib\RedisQueue;
use customer\Lib\TextSocket;
use customer\Lib\WebSocket;
use customer\Lib\Timer;
use Cli\Model\Event;
use customer\Lib\Queue;

class Getway extends Event
{

    protected $registerLink;
    protected $worksLink;
    protected $workServer;
    protected $getwayLink;
    protected $getwayServer;
    private   $_links = [];
    private   $userMap = [];
    private   $_PID = ''; //自身进程ID

    const MSG_TYPE_GROUP = 1;//组消息，或是房间消息，或者群消息表示
    const MSG_TYPE_ONLY  = 2;//单人消息
    const MSG_TYPE_WORK  = 3;//发送到work进程的

    private $queueModel;


    public function __construct()
    {
        //创建work端口
        $this->workServer = TextSocket::createAndListen(Config::WorkIp,Config::WorkPort);
        //创建getway端口
        $this->getwayServer = WebSocket::createAndListen(Config::GetwayIp,Config::GetwayPort);
        $this->queueModel = new RedisQueue();
        $this->_links['getS']     = $this->getwayServer;
        $this->_links['workS']    = $this->workServer;
        $this->beforeWork();
        $this->pcntlModel  = new PcntlModel(2);
    }


    public function run()
    {
        $this->pcntlModel->setWork($this,'work');
        $this->pcntlModel->start();
    }



    public function work()
    {
        $this->_PID = posix_getpid();
        //链接register
        $registerIp = Config::RegisterIp == '0.0.0.0' ? '127.0.0.1' : Config::RegisterIp;
        $this->registerLink = TextSocket::clientListen($registerIp,Config::RegisterPort);
        $this->_links[self::LINK_TYPE_REGISTER] = $this->registerLink;

        $server['getS']  = $this->getwayServer;
        $server['workS'] = $this->workServer;
        if($this->registerLink) {
            $this->onRegisterAccept(self::LINK_TYPE_REGISTER);
        }

        Timer::init(); //定时器初始化
        pcntl_signal_dispatch();
        Timer::add(1,array($this,'pingRegister')); //添加定时器任务
        while(true) {
            pcntl_signal_dispatch();//调用注册的信号回调函数
            $data = TextSocket::accept($this->_links,$server);
            switch($data['type']) {
                case TextSocket::SOCKET_TYPE_ACCEPT://来之客户端的链接
                    $id = TextSocket::generateConnectionId();
                    if($data['key'] == 'getS') { //来自getway的链接，前段websocket的链接
                        $this->_links[$id] = $data['link'];
                        echo "LINK GETWAY".$this->_PID.PHP_EOL;
                        $this->getwayLink[$id] = [
                            'link'      =>$data['link'],
                            'handshake' => false,
                        ];
                        $this->onGetwayAccept($id);
                    } else if($data['key'] == 'workS'){ //来自work的链接，后期可以和gets链接的端口合并
                        $this->_links[$id] = $data['link'];
                        $this->worksLink[$id] = [
                            'link'      =>$data['link'],
                        ];
                        $this->onWorkAccept($id);
                        echo "WORK LINK ".$this->_PID.PHP_EOL;
                    }
                    break;
                case TextSocket::SOCKET_TYPE_READ: //消息
                    $this->linkType($data);
                    break;
            }
            $this->getQueueMsg();

        }
    }


    protected function beforeWork()
    {
        $this->onRegisterStart();
        $this->onGetwayStart();
        $this->onWorkStart();

    }


    protected function linkType($data)
    {
        $key = $data['key'];
        if($this->getwayLink[$key]) {
            //$msg = json_decode(WebSocket::decode($data['msg']),true);
            echo "READ getWAY MEG".PHP_EOL;
            if(!$this->getwayLink[$key]['handshake']) {
                $handshake = WebSocket::handshake($data['msg']);
                WebSocket::sendOne($handshake,$this->_links[$key]);
                $this->getwayLink[$key]['handshake'] = true;
                $this->onGetwayAccept();
                echo "HANDLER ".$this->_PID.PHP_EOL;
            } else {
                $this->onGetwayMessage($key,json_decode(WebSocket::decode($data['msg']),true));
            }

        } else if($this->worksLink[$key]) {
            $msg = json_decode(TextSocket::decode($data['msg']),true);
            $this->onWorkMessage($key,$msg);

        } else if($key == self::LINK_TYPE_REGISTER) {
            $msg = json_decode(TextSocket::decode($data['msg']),true);
            $this->onRegisterMessage($key, $msg);
        }

    }


    /**
     * 链接到register服务的时候开始工作
     * @param $key
     */

    public function onRegisterStart($key)
    {

    }




    public function onRegisterAccept($key)
    {
        $msg = [
            'linkType' => self::LINK_TYPE_GETWAY,
            'eventType'=> self::EVENT_TYPE_LINK,
            'ip'       =>Config::WorkIp,
            'port'     =>Config::WorkPort,
            'work'     =>[]
        ];
       // echo '[Getway] SEND '.'LINK '.$key.PHP_EOL;
        TextSocket::sendOne(TextSocket::encode(json_encode($msg)),$this->_links[$key]);
    }



    /**
     * 接受到register服务发送过来的消息的时候的处理
     * @param $key
     * @param $msg
     */
    public function onRegisterMessage($key, $msg)
    {
        if($msg['linkType'] == Register::LINK_TYPE_PING) {
            $sendMsg = [
                'linkType' => Register::LINK_TYPE_PING,
                'eventType'=> Register::EVENT_TYPE_PING,
            ];
            TextSocket::sendOne(TextSocket::encode(json_encode($sendMsg)),$this->_links[$key]);
            //echo '[Getway] RegisterMessage '.$msg['linkType'].PHP_EOL;
        }
    }


    /**
     * register服务关闭时候处理
     *
     */
    public function onRegisterClose()
    {

    }


    /**
     * work服务初始化工作
     * @pram string $key
     * @return bool
     */
    public function onWorkStart($key)
    {

    }


    /**
     * 处理来之 work链接工作的初始化工作
     * @param $key
     */
    public function onWorkAccept($key)
    {

    }

    /**
     * 处理来之work进程的消息，（work进程的处理可以放到master进程，子进程处理队列数据即可）
     * @param string $key _links标示
     * @param array $msg
     * return void
     */
    public function onWorkMessage($key, $msg)
    {
        //分析消息类型
        if($msg['eventType'] == self::EVENT_TYPE_PING) { //ping事件
            $this->pingWork($key);
        } else if($msg['eventType'] ==self::EVENT_TYPE_MSG) {//消息事件
            $this->msgTackle($msg['type'],$msg['uids'],$msg['body']);
        }
    }


    protected function msgTackle($type, $uids, $body)
    {
        foreach($uids as $k=>$val) {
            if($this->userMap[$val]) {
                WebSocket::sendOne(WebSocket::encode(json_encode(['type'=>$type,'body'=>$body])),$this->userMap[$val]);
                unset($uids[$k]);
            }
        }
        if(!empty($msg['uids'])) {
            $this->queueModel->send(['type'=>$type, 'uids'=>$uids, 'body'=>$body]);
        }
    }


    private function getQueueMsg()
    {
        $queueData = $this->queueModel->get();
        if($queueData) {
            if($queueData['type'] != self::MSG_TYPE_WORK) {
                $this->msgTackle($queueData['type'],$queueData['uids'],$queueData['body']);
            } else if($queueData['type'] == self::EVENT_TYPE_MSG) {//
                $this->msgToGetTackle($queueData['extend']['type'],$queueData['extend']['uids'],$queueData['extend']['body']);
            }
            echo 'QUEUE DATA NO EMPTY '.json_encode($queueData).$this->_PID.PHP_EOL;
        }
        echo "GET QUEUE".$this->_PID.PHP_EOL;
        return;
    }
    /**
     * 工作进程关闭时候的处理
     * @param $key
     */
    public function onWorkClose($key)
    {

    }

    /**
     * getway启动工作时初始化工作
     */
    public function onGetwayStart()
    {

    }


    /**
     * 接受来之 浏览器或是其他客户端的链接工作时的触发工作
     * @param $key
     */
    public function onGetwayAccept($key)
    {

    }

    /**
     * 处理getway信息
     * @param $key
     * @param $msg
     */
    public function onGetwayMessage($key, $msg)
    {
        //分析消息类型
        if($msg['eventType'] == self::EVENT_TYPE_PING) { //ping事件
            $this->pingGetway($key);
        } else if($msg['eventType'] ==self::EVENT_TYPE_MSG) {//消息事件
            $this->msgToGetTackle($msg['type'],$msg['uids'],$msg['body']);
        } else if($msg['eventType'] ==self::EVENT_TYPE_BIND_UID) { //绑定UID事件
            $this->userMap[$msg['uid']] = $this->_links[$key];

        }
    }

    /**
     * 处理来自getway的客户端消息
     * @param $type
     * @param $uids
     * @param $body
     */
    private function msgToGetTackle($type, $uids, $body)
    {
        foreach($uids as $k=>$val) {
            if($this->userMap[$val]) {
                WebSocket::sendOne(WebSocket::encode(json_encode(['type'=>$type,'pid'=>$this->_PID,'body'=>$body])),$this->userMap[$val]);
                unset($uids[$k]);
            }
        }
        if(!empty($msg['uids'])) { //发送到work
            if(!empty($this->worksLink)) {
                foreach($this->worksLink as $k=>$val) {
                    TextSocket::sendOne(TextSocket::encode(['type'=>$type, 'uids'=>$uids, 'body'=>$body]),$val);
                    echo "SEND WORK";
                    break;
                }
                //$this->queueModel->send(['type'=>$type, 'uids'=>$uids, 'body'=>$body]);
            } else { //此进程没有work链接发送到队列让其他进程处理
                $this->queueModel->send(['type'=>self::MSG_TYPE_WORK, 'extend'=>['uids'=>$uids, 'body'=>$body,'type'=>$type]]);
            }
        }
    }


    /**
     * 处理getWay关闭工作
     * @param $key
     */
    public function onGetwayClose($key)
    {

    }


    /**
     * 保持与work进程心跳链接，及一段时间内无答复（断开链接的处理）
     * @param string $key
     * return void
     */
    public function pingWork($key = '')
    {
        $sendMsg = [
            'linkType' => Register::LINK_TYPE_PING,
            'eventType'=> Register::EVENT_TYPE_PING,
        ];
        if($key){
            TextSocket::sendOne(TextSocket::encode(json_encode($sendMsg)),$this->_links[$key]);
        } else {
            if(!empty($this->worksLink)) {
                foreach($this->worksLink as $val) {
                    TextSocket::sendOne(TextSocket::encode(json_encode($sendMsg)),$val);
                }
            }
        }
    }



    /**
     * 保持getway的心跳
     */
    public function pingGetway($key)
    {
        $sendMsg = [
            'linkType' => Register::LINK_TYPE_PING,
            'eventType'=> Register::EVENT_TYPE_PING,
        ];
        if($key){
            WebSocket::sendOne(WebSocket::encode(json_encode($sendMsg)),$this->_links[$key]);
        } else {
            if(!empty($this->getwayLink)) {
                foreach ($this->getwayLink as $val) {
                    WebSocket::sendOne(WebSocket::encode(json_encode($sendMsg)), $val['link']);
                }
            }
        }
    }



    /**
     * 保持与register的心跳链接
     */
    public function pingRegister()
    {
        $msg = [
            'linkType' => Register::LINK_TYPE_PING,
            'eventType'=> Register::EVENT_TYPE_PING,
            'work'     =>[]
        ];
        TextSocket::sendOne(TextSocket::encode(json_encode($msg)),$this->_links[self::LINK_TYPE_REGISTER]);
        //echo '[getway] PING '.PHP_EOL;
    }

}