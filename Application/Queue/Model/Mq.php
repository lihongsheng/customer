<?php
/**
 * Mq.php
 *
 * 作者: lihongsheng (549940183@qq.com)
 * 创建日期: 17/8/10 下午10:26
 * 修改记录:
 *
 * $Id$
 */

namespace Queue\Model;
use PhpAmqpLib\Connection\AMQPConnection;

class Mq extends WorkInterface
{

    /**
     * 交换器
     *
     * @var string
     */
    public $exchange = '';
    /**
     * 队列名称
     *
     * @var string
     */
    public $queue = '';


    /**
     * 路由key
     *
     * @var string
     */
    public $routing_key = '';

    /**
     * 消息者标识
     *
     * @var string
     */
    public $consumer_tag = '';

    /**
     * 失败重试最大次数
     *
     * @var int
     */
    public $retry_max = 3;

    protected $host;

    protected $port;

    protected $user;

    protected $pwd;

    protected $vhost;


    public function __construct($host, $port, $user, $pwd,$vhost)
    {

        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pwd  = $pwd;
        $this->vhost= $vhost;

    }


    public function installSignal()
    {
    }


    public function run() {
        $this->setProcessTitle("work::mq");
        $this->installSignal();

        $conn = new AMQPConnection($this->host, $this->port, $this->user, $this->port, $this->vhost);
        $ch = $conn->channel();


        $ch->queue_declare($this->queue, false, true, false, false);


        $ch->exchange_declare($this->exchange, 'direct', false, true, false);


        $ch->queue_bind($this->queue, $this->exchange, $this->routing_key);



        $process_message = function ($msg){
            $this->msgHandler($msg);
        };

        /**
         * 我们可以使用basic_qos方法并将prefetch_count设置为1(prefetch_count=1)。这里告诉RabbitMQ不要一次给一个worker一个以上的消息。或者换句话说，不要向一个worker分派新的任务，知道它处理完成并已经确认了上一个消息。相反，它会将其分派给下一个仍然不忙的worker。
         */
        $ch->basic_qos(null, 1, null);


        /**
         *
         */
        $ch->basic_consume($this->queue, $this->consumer_tag, false, false, false, false, $process_message);

        function shutdown($ch, $conn) {
            $ch->close();
            $conn->close();
        }

        register_shutdown_function('shutdown', $ch, $conn);

        // Loop as long as the channel has callbacks registered
        while (count($ch->callbacks)) {
            $ch->wait();
        }
    }


    /**
     * 消息处理handler
     * @param $msg
     */
    protected function msgHandler($msg) {
        $isWork = call_user_func($this->msgWork,$msg->body);
        if($isWork) {
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }
    }

    /**
     * @var function($msg) :bool {

     * }
     */
    public $msgWork;

    /**
     * @param string $msg
     */
    public function sendMsg($msg) {

    }




}