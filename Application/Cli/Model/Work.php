<?php
/**
 * Work.php
 *
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/21 下午11:26
 * 修改记录:
 *
 * $Id$
 */

namespace Cli\Model;

use customer\Lib\Config;
use customer\Lib\PcntlModel;
use customer\Lib\TextSocket;
use customer\Lib\Timer;
use Cli\Model\Event;


class Work extends Event
{

    protected $registerLink;
    protected $getwayLink = [];
    protected $getwayLinkData = [];
    private   $_links = [];
    private   $pcntlModel;


    public function __construct()
    {
        $this->pcntlModel  = new PcntlModel(1);
    }


    /**
     * 开始工作前的一些工作
     */
    protected function beforeWork()
    {
        //链接register
        $registerIp = Config::RegisterIp == '0.0.0.0' ? '127.0.0.1' : Config::RegisterIp;
        $this->registerLink = TextSocket::clientListen($registerIp,Config::RegisterPort);
        $this->_links[self::LINK_TYPE_REGISTER] = $this->registerLink;
        if($this->registerLink) {
           $this->onRegisterAccept(self::LINK_TYPE_REGISTER);
        }
    }


    public function run()
    {
        $this->pcntlModel->setWork($this,'work');
        $this->pcntlModel->start();
    }


    public function work()
    {
        $this->beforeWork();
        $server = [];
        Timer::init();
        Timer::add(1,array($this,'pingRegister'));
        Timer::add(1,array($this,'pingGetway'));
        pcntl_signal_dispatch();
        while(true) {
            pcntl_signal_dispatch();
            $data = TextSocket::accept($this->_links,$server);
            switch($data['type']) {
                case TextSocket::SOCKET_TYPE_READ: //消息
                    if($data['key'] == self::LINK_TYPE_REGISTER) {//来自register的消息回复
                        $this->onRegisterMessage($data['key'],json_decode(TextSocket::decode($data['msg']),true));
                    } else {
                        $this->onGetwayMessage($data['key'],json_decode(TextSocket::decode($data['msg']),true));
                    }
            }
        }
    }


    /**
     * 处理getway信息
     * @param $key
     * @param $msg
     */
    public function onGetwayMessage($key, $msg)
    {
        if($msg['linkType'] == self::LINK_TYPE_PING) {
            $this->pingGetway($key);
            return;
        }

        if($msg['linkType'] == self::LINK_TYPE_WORK) { //来自个getway的工作消息
            //$msg['eventType'] 根据事件处理
        }


    }


    /**
     * 接受来之浏览器或是其他客户端的链接工作
     * @param $id
     */
    public function onGetwayAccept($id)
    {
        $msg = [
            'linkType' => self::LINK_TYPE_PING,
            'eventType'=> self::EVENT_TYPE_PING,
        ];
        // echo '[Getway] SEND '.'LINK '.$key.PHP_EOL;
        TextSocket::sendOne(TextSocket::encode(json_encode($msg)),$this->_links[$id]);
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
            'linkType' => self::LINK_TYPE_PING,
            'eventType'=> self::EVENT_TYPE_PING,
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
        if($msg['linkType'] == self::LINK_TYPE_PING) {
            $this->pingRegister();
            return;
        }

        if($msg['linkType'] == self::LINK_TYPE_WORK) {
            if($msg['eventType'] == 'addGetWay') { //添加getway事件
                foreach($msg['msgBody'] as $val) {
                    $id = md5($val['ip'],$val['port']);
                    if($this->getwayLink[$id]) {
                        continue;
                    }

                    $link = TextSocket::clientListen($val['ip'],$val['port']);
                    if($link) {
                        $this->_links[$id]     = $link;
                        $this->getwayLink[$id] = $link;
                        $this->getwayLinkData[$id] = $val;
                        $this->onGetwayAccept($id);
                    }
                }
                return;
            }
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
     * 保持getway的心跳
     */
    public function pingGetway($key)
    {
        if(!empty($this->getwayLink)) {
            $msg = [
                'linkType' => self::LINK_TYPE_PING,
                'eventType' => self::EVENT_TYPE_PING,
                'work' => []
            ];
            foreach($this->getwayLink as $val) {
                TextSocket::sendOne(TextSocket::encode(json_encode($msg)), $val);
            }
        }
    }



    /**
     * 保持与register的心跳链接
     */
    public function pingRegister()
    {
        $msg = [
            'linkType' => self::LINK_TYPE_PING,
            'eventType'=> self::EVENT_TYPE_PING,
            'work'     =>[]
        ];
        TextSocket::sendOne(TextSocket::encode(json_encode($msg)),$this->_links[self::LINK_TYPE_REGISTER]);
        //echo '[getway] PING '.PHP_EOL;
    }

}