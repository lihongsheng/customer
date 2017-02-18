<?php
/**
 * Register.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/16 上午12:50
 * 修改记录:
 *
 * $Id$
 */
namespace Cli\Model;

use customer\Lib\Config;
use customer\Lib\PcntlModel;
use customer\Lib\TextSocket;
use customer\Lib\Timer;

class Register
{
    protected $pcntlModel;
    protected $getwayLink = [];
    protected $workLink   = [];
    protected $getwayLinkData = [];
    protected $workLinkData   = [];
    protected $_links     = [];
    protected $master;

    const LINK_TYPE_GETWAY = 'getway';
    const LINK_TYPE_WORK   = 'work';
    const LINK_TYPE_PING   = 'ping';
    const EVENT_TYPE_LINK  = 'link';
    const EVENT_TYPE_PING  = 'ping';


    public function __construct()
    {
        $this->pcntlModel  = new PcntlModel(1);
        $this->master      = TextSocket::createAndListen(Config::RegisterIp,Config::RegisterPort);

    }

    public function run()
    {
        $this->pcntlModel->setWork($this,'work');
        $this->pcntlModel->start();
    }

    public function work()
    {
        $this->_links['s'] = $this->master;
        $server = ['s'=>$this->master];
        //echo Config::RegisterPort.PHP_EOL;
        Timer::init();
        Timer::add(1,array($this,'ping'));
        while(true){
            pcntl_signal_dispatch();
            $links = $this->_links;
            $data = TextSocket::accept($links, $server);
            switch($data['type']) {
                case TextSocket::SOCKET_TYPE_ACCEPT://来之客户端的链接
                    $id = TextSocket::generateConnectionId();
                    $this->getwayLink[$id] = $data['link'];
                    $this->_links[$id]     =  $data['link'];
                    break;
                case TextSocket::SOCKET_TYPE_READ: //消息
                    $this->linkTypeWork($data);
            }
        }
    }


    protected function linkTypeWork(array $data)
    {
        $msg = json_decode($data['msg'],true);
        switch($msg['linkType']){
            case Register::LINK_TYPE_GETWAY://来之getway服务器

                if($msg['eventType'==Register::EVENT_TYPE_LINK]) {//link事件
                    if(FALSE === array_search($data['link'],$this->getwayLinkData)) {
                        $this->getwayLink[array_search($data['link'],$this->getwayLink)] = [
                            'ip' => $msg['ip'],
                            'port' => $msg['port'],
                            'work' => $msg['work']
                        ];
                        if(!empty($this->workLinkData)) {
                            foreach($this->workLinkData as $val) {
                                $tmp[] = [
                                    'ip'   => $val['ip'],
                                    'port' => $val['port']
                                ];
                            }
                            $msg = json_encode(['msgBody'=>$tmp,'linkType'=>self::LINK_TYPE_WORK,'eventType'=>'addGetWay']);
                            unset($tmp);
                            TextSocket::sendMutily($msg,$this->workLink);
                        }
                    }
                }
            break;
            case self::LINK_TYPE_WORK: //来之客户端的链接

                if($msg['eventType'==Register::EVENT_TYPE_LINK]) {//link事件
                    if(FALSE === array_search($data['link'],$this->workLinkData)) {
                        $this->workLinkData[array_search($data['link'],$this->workLink)] = [
                            'ip' => $msg['ip'],
                            'port' => $msg['port'],
                            'work' => $msg['work']
                        ];
                        if(!empty($this->getwayLinkData)) {
                            foreach($this->getwayLinkData as $val) {
                                $tmp[] = [
                                    'ip'   => $val['ip'],
                                    'port' => $val['port']
                                ];
                            }
                            $msg = json_encode(['msgBody'=>$tmp,'linkType'=>'work','eventType'=>'addWork']);
                            unset($tmp);
                            TextSocket::sendMutily($msg,$this->getwayLink);
                        }
                    }
                }
            break;

            case Register::LINK_TYPE_PING:
                $id = array_search($data['link'],$this->_links);
                $nowTime = time();
                if($this->getwayLinkData[$id]) {
                    $this->getwayLinkData[$id]['ping'] = $nowTime;
                }
                if($this->workLinkData[$id]) {
                    $this->workLinkData[$id]['ping'] = $nowTime;
                }
        }
    }


    /**
     * 保持心跳
     */
    public function ping()
    {
        echo 'PING '.PHP_EOL;
        if(!empty($this->_links)){
            foreach($this->_links as $val){
                TextSocket::sendOne(json_encode(['linkType'=>self::LINK_TYPE_PING,'eventType'=>self::EVENT_TYPE_PING,'msgBody'=>[]]),$val);
            }
        }
    }
}