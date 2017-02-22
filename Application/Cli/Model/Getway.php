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
use customer\Lib\TextSocket;
use customer\Lib\WebSocket;
use customer\Lib\Timer;
use Cli\Model\Event;

class Getway extends Event
{

    protected $registerLink;
    protected $worksLink;
    protected $workServer;
    protected $getwayLink;
    protected $getwayServer;
    private   $_links = [];


    public function __construct()
    {
        //创建work端口
        $this->workServer = TextSocket::createAndListen(Config::WorkIp,Config::WorkPort);
        //创建getway端口
        $this->getwayServer = WebSocket::createAndListen(Config::GetwayIp,Config::GetwayPort);

        $this->_links['getS']     = $this->getwayServer;
        $this->_links['workS']    = $this->workServer;
        $this->beforeWork();
        $this->pcntlModel  = new PcntlModel(1);
    }


    public function run()
    {
        $this->pcntlModel->setWork($this,'work');
        $this->pcntlModel->start();
    }



    public function work()
    {
        //链接register
        $registerIp = Config::RegisterIp == '0.0.0.0' ? '127.0.0.1' : Config::RegisterIp;
        $this->registerLink = TextSocket::clientListen($registerIp,Config::RegisterPort);
        $this->_links[self::LINK_TYPE_REGISTER] = $this->registerLink;

        $server['getS']  = $this->getwayServer;
        $server['workS'] = $this->workServer;
        if($this->registerLink) {
            $this->onRegisterAccept(self::LINK_TYPE_REGISTER);
        }

        Timer::init();
        pcntl_signal_dispatch();
        Timer::add(1,array($this,'pingRegister'));
        while(true) {
            pcntl_signal_dispatch();
            //$link = $this->_links;
            $data = TextSocket::accept($this->_links,$server);

            switch($data['type']) {
                case TextSocket::SOCKET_TYPE_ACCEPT://来之客户端的链接
                    $id = TextSocket::generateConnectionId();
                    if($data['key'] == 'getS') { //来自getway的链接
                        $this->_links[$id] = $data['link'];
                        $this->getwayLink[$id] = [
                            'link'      =>$data['link'],
                            'handshake' => false,
                        ];
                        $this->onGetwayAccept($id);
                    } else if($data['key'] == 'workS'){
                        $this->_links[$id] = $data['link'];
                        $this->worksLink[$id] = [
                            'link'      =>$data['link'],
                        ];
                        $this->onWorkAccept($id);
                    }
                case TextSocket::SOCKET_TYPE_READ: //消息
                    $this->linkType($data);
            }

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
            if(!$this->getwayLink[$key]['handshake']) {
                $handshake = WebSocket::handshake($data['msg']);
                WebSocket::sendOne($handshake,$this->_links[$key]);
                $this->getwayLink[$key]['handshake'] = true;
                $this->onGetwayAccept();
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
            'linkType' => Register::LINK_TYPE_GETWAY,
            'eventType'=> Register::EVENT_TYPE_LINK,
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
     * 处理来之 work链接工作
     * @param $key
     */
    public function onWorkAccept($key)
    {

    }

    /**
     * 处理来之work进程的消息
     * @param $key
     * @param $msg
     */
    public function onWorkMessage($key, $msg)
    {

    }


    /**
     * 工作进程关闭时候的处理
     * @param $key
     */
    public function onWorkClose($key)
    {

    }

    /**
     * getway启动工作
     */
    public function onGetwayStart()
    {

    }


    /**
     * 接受来之浏览器或是其他客户端的链接工作
     * @param $id
     */
    public function onGetwayAccept($id)
    {

    }

    /**
     * 处理getway信息
     * @param $key
     * @param $msg
     */
    public function onGetwayMessage($key, $msg)
    {

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
     */
    public function pingWork()
    {

    }



    /**
     * 保持getway的心跳
     */
    public function pingGetway()
    {

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