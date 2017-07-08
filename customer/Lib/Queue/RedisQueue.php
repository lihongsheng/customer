<?php
/**
 * RedisQueue.php
 *
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/3/2 下午7:47
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

use customer\Lib\Db\RedisModel;

class RedisQueue
{
    private $model;
    private $key = 'QUEUE::';

    public function __construct($redis)
    {
        $this->model = RedisModel::getRedis();
        $this->model->select(1);
    }

    /**
     * 发送队列
     * @param array $msg
     * @param int $tag
     */
    public function send($msg, $tag = 1) {
        //msg_send($this->msgKey, $tag, $msg,true);
        $this->model->lPush($this->key.$tag,json_encode($msg));
    }

    /**
     * 取队列值
     * @param int $tag
     * @return bool
     */
    public function get($tag = 1) {
        //$data = msg_receive($this->msgKey, $tag, $msgType, 1024, $message);
        $data = $this->model->lPop($this->key.$tag);
        if($data) {
            return json_decode($data,true);
        }
        return false;
    }


    /**
     * 获取队列状态
     * @return array
     */
    public function getStatus() {
        //return msg_stat_queue($this->msgKey);
    }

    /**
     * 移除队列
     * @return bool
     */
    public function close() {
        //return msg_remove_queue($this->msgKey);
    }

    /**
     * 判断队列是否存在
     * @return bool
     */
    public function isDelete() {
        //return msg_queue_exists($this->msgId);
    }
}