<?php
/**
 * Server.php
 * 单进程版listen
 * 作者: 李红生 (dannyzml@qq.com)
 * 创建日期: 17/4/18 下午10:39
 * 修改记录:
 *
 * $Id$
 */

namespace Cli\Model;

use customer\Lib\Config;
use customer\Lib\PcntlModel;
use customer\Lib\RedisQueue;
use customer\Lib\WebSocket;
use customer\Lib\Timer;
use Cli\Model\Event;
use customer\Lib\Queue;

class Server extends Event
{
    private   $_PID = ''; //自身进程ID
    protected $ser;
    protected $userLink  = [];
    protected $groupLink = [];
    protected $fdLink    = [];

    public function __construct()
    {
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
        $this->_PID = posix_getpid();
        $this->ser = new WebSocket();
        $this->ser->setEvent($this);
        $this->ser->createAndListen('127.0.0.1','9701');
        while(true) {
            $this->ser->accept();
        }
    }

    protected function beforeWork(){

    }


    /**
     * 在web端登录后，发起websocket请求建立长链接
     * 根据cookie信息来添加用户UID与client的绑定
     * @param $id
     * @param null $req 建立的链接的时候，发送的http协议，请求头中带有cookie和请求信息头信息
     *
     */
    public function onConnect($id,$req = null) {

    }

    /**
     * @param $msg
     * @param $id
     */
    public function onMessage($msg,$id) {
        $jsonMsg = json_decode($msg,true);
        $uid     = $jsonMsg['uid'];
        switch($jsonMsg['type']) {
            case '1'://uid与clentid绑定
                $this->userLink[$uid]['l'] = $id;
                $this->userLink[$uid]['n'] = $jsonMsg['name'];
                $this->fdLink[$id]['u'] = $uid;
                $this->ser->sendOne(json_encode(['sendid'=>$uid,'type'=>'1','msg'=>true]),$this->userLink[$uid]['l']);
                break;
            case '2'://解绑uid与clentid
                unset($this->userLink[$uid]['l'],$this->fdLink[$id]['u']);
                $this->ser->sendOne(json_encode(['sendid'=>$uid,'type'=>'2','msg'=>true]),$this->userLink[$uid]['l']);
                break;
            case '3'://加入组消息
                $groupid = $jsonMsg['sendid'];
                $this->userLink[$uid]['g'][$groupid] = $groupid;
                $this->groupLink[$groupid][$uid] = $uid;
                $this->ser->sendOne(json_encode(['sendid'=>$uid,'type'=>'3','msg'=>true]),$this->userLink[$uid]['l']);
                break;
            case '4'://退出组消息
                $groupid = $jsonMsg['sendid'];
                unset($this->userLink[$uid]['g'][$groupid]);
                unset($this->groupLink[$groupid][$uid]);
                $this->ser->sendOne(json_encode(['sendid'=>$uid,'uname'=>$this->userLink[$uid]['n'],'type'=>'4','msg'=>true]),$this->userLink[$uid]['l']);
                break;
            case '5'://发送用户消息
                $sendid = $jsonMsg['sendid'];
                $msg    = $jsonMsg['msg'];
                $this->ser->sendOne(json_encode(['sendid'=>$uid,'uname'=>$this->userLink[$uid]['n'],'revuid'=>$sendid,'type'=>'5','msg'=>$msg]), $this->userLink[$sendid]['l']);
                break;
            case '6'://发送用户组消息
                $sendid = $jsonMsg['sendid'];
                foreach($this->groupLink[$sendid] as $v) {
                    $this->ser->sendOne(json_encode(['sendid'=>$uid,'type'=>'6','msg'=>$msg,'uname'=>$this->userLink[$uid]['n']]), $this->userLink[$v]['l']);
                }
                break;
            case '7'://获取组用户列表
                $groupid = $jsonMsg['sendid'];
                $users = [];
                foreach($groupid[$groupid] as $v) {
                    $users[] =[
                        'name'=> $this->userLink[$v]['n'],
                        'uid'=> $v];
                }
                $this->ser->sendOne(json_encode(['sendid'=>$uid,'type'=>'7','msg'=>$users]),$this->userLink[$uid]['l']);
        }
    }


    /**
     * @param $id
     */
    public function onClose($id) {
        $uid = $this->fdLink[$id]['u'];
        //删除组
        foreach($this->userLink['g'] as $v) {
            unset($this->groupLink[$v][$uid]);
        }
        unset($this->userLink[$uid]);
        unset($this->fdLink[$id]);

    }
}