<?php
/**
 * queue.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/23 下午5:02
 * 修改记录:
 *
 * $Id$
 */
namespace Cli\Controller;

class Queue
{
    private  $msgId = '67000';
    private  $msgKey;

    public function __construct()
    {
       /* if(!function_exists('msg_send')) {
            throw new Exception("MSG_SEND NOT FIND");
        }
        if(!msg_queue_exists($this->msgId)){
            $this->msgKey = msg_get_queue($this->msgId);
        } else {
            $this->msgKey = msg_get_queue($this->msgId);
            msg_remove_queue($this->msgKey);
            $this->msgKey = msg_get_queue($this->msgId);
            //throw new Exception("QUEUE IS SET");
        }*/
    }




    public function indexAction()
    {
        $this->msgKey = msg_get_queue($this->msgId);

        $this->send(['ni'=>';;','kl'=>''],1);
        $this->send(['ni'=>'00;','kl'=>'klkl'],2);
        var_dump($this->get(2));
        $this->close();
    }


    /**
     * 发送队列
     * @param $msg
     */
    public function send($msg,$tag) {
        msg_send($this->msgKey, $tag, $msg,true);
    }

    /**
     * 取队列值
     * @return bool
     */
    public function get($tag) {
        $data = msg_receive($this->msgKey, $tag, $msgType, 1024, $message);
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