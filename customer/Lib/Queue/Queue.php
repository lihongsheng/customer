<?php
/**
 * Queue.php
 * 对外队列提供统一接口（msg_queue,redis,MQ,mongo都可以实现队列）
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/23 上午9:55
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

/**
 * （LINUX 下起作用）队列控制
 * Class MsgController
 */
class Queue
{
    //相当于路由标示
    private  $msgId = '69000';
    //获取的队列标示
    private  $msgKey;

    public function __construct()
    {
        if(!function_exists('msg_send')) {
            throw new \Exception("MSG_SEND NOT FIND");
        }
        $this->msgKey = msg_get_queue($this->msgId);
        /*if(!msg_queue_exists($this->msgId)){
            $this->msgKey = msg_get_queue($this->msgId);
        } else {
            $this->msgKey = msg_get_queue($this->msgId);
            msg_remove_queue($this->msgKey);
            $this->msgKey = msg_get_queue($this->msgId);
            //throw new \Exception("QUEUE IS SET");
        }*/
    }

    /**
     * 发送队列
     * @param array $msg
     * @param int $tag
     */
    public function send($msg, $tag = 1) {
        msg_send($this->msgKey, $tag, $msg,true);
    }

    /**
     * 取队列值
     * @param int $tag
     * @return bool
     */
    public function get($tag = 0) {
        $data = msg_receive($this->msgKey, $tag, $msgType, 1024, $message, true, MSG_IPC_NOWAIT);
        if($data) {
            return $message;
        }
        return false;
    }

    /**
     * 获取队列状态
     * @return array
     */
    public function getStatus() {
        return msg_stat_queue($this->msgKey);
    }

    /**
     * 移除队列
     * @return bool
     */
    public function close() {
        return msg_remove_queue($this->msgKey);
    }

    /**
     * 判断队列是否存在
     * @return bool
     */
    public function isDelete() {
        return msg_queue_exists($this->msgId);
    }

}