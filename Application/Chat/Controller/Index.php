<?php
/**
 * Index.php
 * 测试多进程监听 端口
 * 及测试 多进程间的用户传递消息的
 *    多进程间传递消息
 *    项目方案：
 *
 *        主进程创建一个用于进程间通信的内部socket,子进程链接父进程的socket，及监听对外的socket，
 *            （可以依据socket于建立分布式，或者依据REDIS建立消息的分布式转发）
 *        父进程，轮询REDIS队列，有本服务器消息处理的时候，查询用户所在进程，进行消息投递，并有子进程把消息投递到客户端（分布式的可以有子进程发送不在此服务器的消息到父进程，由父进程发送消息到中心服务器去调度到别的服务器进行，消息间的夸服务器传递）
 *           主进程负责监控子进程，及其他事项，所以主进程的socket依据于select,子进程的socket依据于event
 *        为方便测试依据 依据于redis建立此项目
 *        数据定义：
 *           用户数据定义：
 *              uid为redis键值对key
 *                 包含信息
 *                 name姓名
 *                 status 状态
 *                 channel 所在频道 IP-进程ID
 *            用户消息队列数据定义：（可依据服务器IP建立多个消息队列，服务器依据IP读取对应的队列的数据）
 *                JSON格式：
 *                   {uid:用户UID,msg:消息内容}
 *
 * 作者:
 * 创建日期: 17/5/16 下午10:58
 * 修改记录:
 *
 * $Id$
 */
namespace Chat\Controller;


use Chat\Model\Work;
use customer\Lib\Controller;
use customer\Lib\Db\RedisModel;
use customer\Lib\MutliProcess;
use customer\Lib\RedisQueue;

class Index extends Controller{


    protected $workModel;
    protected $sockeLink;

    protected $links = [];

    protected $pidMapChild = [];

    /**
     * @var RedisQueue
     */
    protected $queue;

    /**
     * @var \redis
     */
    protected $redis;

    protected $startTime;
    /**
     * 多进程监听socket已完成
     */
    public function indexAction() {

        try {


            $this->workModel = new MutliProcess(4);

            //创建对外的监听端口
            $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);
            socket_bind($listen, '0.0.0.0', 20072);
            socket_listen($listen);

            //创建一个对内的socket用户父子进程间的通信
            $this->sockeLink = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_set_option($this->sockeLink, SOL_SOCKET, SO_REUSEADDR, 1);
            socket_bind($this->sockeLink, '127.0.0.1', 20073);
            socket_listen($this->sockeLink);
            $this->links['serv'] = $this->sockeLink;

            $this->redis = RedisModel::getRedis();
            $this->queue = new RedisQueue();
            $this->startTime = time();
            $work = new Work($listen);
            $this->workModel->setWork($work);
            //主进程工作ID
            $this->workModel->masterWork = function () {
                $links = $this->links;
                //无阻赛运行 监听端口
                $intLinks = socket_select($links,$write=null,$except=null,0);
                foreach($links as $k=>$r){
                    if($r == $this->links['serv']) {
                        $eventLink = socket_accept($r);
                        $id = (int)$eventLink;
                        echo "master ::::::".$id.":::::".PHP_EOL;
                        $this->links[$id] = $eventLink;
                    } else {
                        $data = socket_recv($r,$buffer ,2048,0);
                        $id = (int)$r;
                        if($data < 0) {
                            unset($this->links[$id]);
                            foreach ($this->pidMapChild as $k=>$v) {
                                if($v==$id) {
                                    unset($this->pidMapChild[$k]);
                                }
                            }
                            break;
                        }
                        //解析text协议
                        while(true) {
                            $pos = strpos($buffer, "\n");
                            if($pos === false) {
                                break;
                            }
                            $tmp = substr($buffer,0,$pos+1);
                            $tmp = json_encode($tmp)."\n";
                            if($tmp['type'] = 'bind') {
                                $this->pidMapChild[$tmp['pid']] = $id;
                            }
                        }
                    }
                }

                //从队列获取消息
                $msg = $this->queue->get();
                $uid = $msg['uid'];
                $userInfo = $this->redis->hGet($uid);
                $pid = $userInfo['pid'];
                if($pid) {
                    $msg = json_encode($msg);
                    socket_write($this->links[$this->pidMapChild[pid]],$msg,strlen($msg));
                }

                //向子进程放送ping
                $tmpTime = time()-$this->startTime;
                if($tmpTime > 30) {
                    foreach ($this->pidMapChild as $k => $v) {
                        if ($k == $pid) {
                            continue;
                        }
                        $msg = json_encode(['type' => 'ping','msg'=>'']);
                        socket_write($this->links[$v],$msg,strlen($msg));
                    }
                }
            };
            $this->workModel->start();


        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 主进程工作进程
     */
    public function masterWork() {

    }






}