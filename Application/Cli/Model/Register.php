<?php
/**
 * Register.php
 *
 * 作者: 李红生 (549940183@qq.com)
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
use Cli\Model\Event;

class Register extends Event
{
    protected $pcntlModel;
    protected $getwayLink = [];
    protected $workLink   = [];
    protected $getwayLinkData = [];
    protected $workLinkData   = [];
    protected $_links     = [];
    protected $master;




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
        pcntl_signal_dispatch();
        Timer::add(1,array($this,'ping'));
        while(true){
            pcntl_signal_dispatch();
           // $links = $this->_links;
            $data = TextSocket::accept($this->_links, $server);

            switch($data['type']) {
                case TextSocket::SOCKET_TYPE_ACCEPT://来之客户端的链接
                    $id = TextSocket::generateConnectionId();
                   // $this->getwayLink[$id] = $data['link'];
                    $this->_links[$id]     =  $data['link'];
                    echo "[Register] new accept".PHP_EOL;
                    break;
                case TextSocket::SOCKET_TYPE_READ: //消息
                    $this->linkTypeWork($data);
            }
        }
    }


    protected function linkTypeWork(array $data)
    {
        $msg = json_decode(TextSocket::decode($data['msg']),true);
        switch($msg['linkType']){
            case Register::LINK_TYPE_GETWAY://来之getway服务器

                if($msg['eventType'==Register::EVENT_TYPE_LINK]) {//link事件
                    $id = $data['key'];
                    $this->getwayLink[$id] = $this->_links[$id];
                    //if(FALSE === array_search($data['link'],$this->getwayLinkData)) {
                    if(!$this->getwayLinkData[$id]) {
                       // $this->getwayLink[array_search($data['link'],$this->getwayLink)] = [
                        $this->getwayLinkData[$id] = [
                            'ip' => $msg['ip'],
                            'port' => $msg['port'],
                            'work' => $msg['work']
                        ];
                        echo '[Register] '.' LINK '.$msg['ip'].PHP_EOL;
                        //向work发送新新增加的getway
                        if(!empty($this->workLink)) {
                            foreach($this->getwayLinkData as $val) {
                                $tmp[] = [
                                    'ip'   => $val['ip'],
                                    'port' => $val['port']
                                ];
                            }
                            $msg = json_encode(['msgBody'=>$tmp,'linkType'=>self::LINK_TYPE_WORK,'eventType'=>'addGetWay']);
                            unset($tmp);
                            TextSocket::sendMutily(TextSocket::encode($msg),$this->workLink);
                        }
                    }
                }
            break;
            case self::LINK_TYPE_WORK: //来之客户端的链接

                if($msg['eventType'==Register::EVENT_TYPE_LINK]) {//link事件
                    $id = $data['key'];
                    $this->workLink[$id] = $this->_links[$id];
                    //if(FALSE === array_search($data['link'],$this->workLinkData)) {
                    if(!$this->workLinkData[$id]) {
                        $this->workLinkData[$id] = [
                            'ip' => $msg['ip'],
                            'port' => $msg['port'],
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
                            //发送给当前链接的work所有的getway信息
                            //TextSocket::sendMutily(TextSocket::encode($msg),$this->getwayLink);
                            TextSocket::sendOne(TextSocket::encode($msg),$this->_links[$id]);
                        }
                    }
                }
            break;

            case Register::LINK_TYPE_PING:
                //$id = array_search($data['link'],$this->_links);
                $id = $data['key'];
                $nowTime = time();
                if($this->getwayLinkData[$id]) {
                    $this->getwayLinkData[$id]['ping'] = $nowTime;
                }
                if($this->workLinkData[$id]) {
                    $this->workLinkData[$id]['ping'] = $nowTime;
                }
                echo '[Register] '." GET PING ".PHP_EOL;
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
                if($val == $this->master)  {
                    continue;
                }

                TextSocket::sendOne(TextSocket::encode(json_encode(['linkType'=>self::LINK_TYPE_PING,'eventType'=>self::EVENT_TYPE_PING,'msgBody'=>[]])),$val);
                echo "[Reigster] PING ".PHP_EOL;
            }
        }
    }
}