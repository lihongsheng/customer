<?php
/**
 * LibEvent.php
 *
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/4/30 上午12:33
 * 修改记录:
 *
 * $Id$
 */

/*
event_base 处理事件，事件管理器
event设置事件
event_buffer及设置IO缓冲事件


event_base介绍:
event_base_new()创建一个新的事件管理器
event_base_free() 释放事件管理器，:这个函数不会释放当前与 event_base 关联的任何事件,或者关闭他们的套
接字 ,或 者释放任何指针
event_base_priority_init()设置 event_base 的优先级数目
event_reinit()重新初始化event_base,因为在fork一个新进程后不是所有的事件后端都可以正确工作。
event_loop 循环监听事件
event_base_loopbreak 如果 event_base 当前正在执行激活事件的回调 ,它将在执行完当前正在处理的事件后立即退出处理完所有活跃事件后退出
event_base_loopexit  如果 event_base 当前正在执行任何激活事件的回调,则回调会继续运行,直到运行完所有激活事件的回调之才退出
event_base_dispatch ()等同于没有设置标志的 event_base_loop ( )。所以,event_base_dispatch ()将一直运行,直到没有已经注册的事件了,或者调用 了event_base_loopbreak()或者 event_base_loopexit()为止。
event_base_set(resource event,resource event_base) — 设置event_base和event事件相连

event设置事件:
libevent 的基本操作单元是事件。每个事件代表一组条件的集合,这些条件包括:
    文件描述符已经就绪,可以读取或者写入
    文件描述符变为就绪状态,可以读取或者写入(仅对于边沿触发 IO)
    超时事件
    发生某信号
    用户触发事件
  所有事件具有相似的生命周期。调用 libevent 函数设置事件并且关联到
event_base 之后, 事件进入“已初始化(initialized)”状态。此时可以将事件添加到
event_base 中,这使之进入“未决(pending)”状态。在未决状态下,如果触发事件的条
件发生(比如说,文件描述 符的状态改变,或者超时时间到达 ),则事件进入“激活
(active)”状态,(用户提供的)事件回调函数将被执行。如果配置为“持久的
(persistent)”,事件将保持为未决状态。否则, 执行完回调后,事件不再是未决的。删
除操作可以让未决事件成为非未决(已初始化)的 ; 添加操作可以让非未决事件再次
成为未决的。

event_new()创建一个新的事件（如socket监听后有新的链接进来，需要创建一个新的事件去监听客户端）
event_free() 释放event
event_set(resource $event , mixed $fd , int $events , mixed $callback [, mixed $arg ] )设置事件
     参数说明
     event = event_new();
     fd 文件句柄（文件IO,socket等等）
     events  EV_TIMEOUT, EV_SIGNAL, EV_READ, EV_WRITE , EV_PERSIST.
     callback 回调函数
     arg 回调函数参数
event_add($eventSource[,timeout]);添加事件,$eventSource必须经过event设置
event_del()删除事件


IO缓冲事件处理
event_buffer系列
event_buffer_new(resource $stream , mixed $readcb , mixed $writecb , mixed $errorcb [, mixed $arg ] )
     参数说明：
     stream 必须是有效的IO流资源，能转换为文件句柄
     readcb 读取回调
     writecb 写入回调
     errorcb 错误回调
     arg     设置回调参数
event_buffer_set(resource $bevent , resource $event_base ) 将缓冲事件与是事件库相连
event_buffer_timeout_set(resource $bevent , int $read_timeout , int $write_timeout)设置缓冲超时时间
event_buffer_watermark_set设置缓冲区的最大最小值
event_buffer_enable 启用那些缓冲事件 EV_READ, EV_WRITE , EV_PERSIST
event_buffer_free释放
event_butter_read 读取数据从缓冲事件
event_buffer_write 写入数据到缓冲区
*/
namespace customer\Lib\Events;

use customer\Lib\Events\EventInterface;



class Event implements EventInterface {

    protected $_eventBase;
    protected $_allEvents = [];
    protected $_allTimer  = [];
    protected $_allSignal = [];
    protected $_eventTimer = [];
    /**
     * Timer id.
     * @var int
     */
    protected static $_timerId = 0;

    public function __construct()
    {
        $this->_eventBase = new \EventBase();
    }


    /**
     * 添加监听事件
     * @param mixed $fd
     * @param int $flag
     * @param callable $func
     * @param null $args
     * @return null
     */
    public function add($fd, $flag, $func, $args = null) {
        $id = (int)$fd;
        switch ($flag) {
            case self::EV_SIGNAL:

                $fd_key = (int)$fd;
                $event = \Event::signal($this->_eventBase, $fd, $func);
                if (!$event||!$event->add()) {
                    return false;
                }
                $this->_eventSignal[$fd_key] = $event;
                return true;
            case self::EV_TIMER;
            case self::EV_TIMER_ONCE:
                self::$_timerId++;
            /**
             * 0回调函数
             * 1参数
             * 2事件标签
             * 3事件ID
             * 4 时间索引
             */
                $param = array($func, (array)$args, $flag, $fd, self::$_timerId);
                $event = new \Event($this->_eventBase, -1, \Event::TIMEOUT|\Event::PERSIST, array($this, "timerCallback"), $param);


                if (!$event||!$event->addTimer($fd)) {
                    return false;
                }
                $this->_eventTimer[self::$_timerId] = $event;
                return self::$_timerId;
            default :
                $fd_key = (int)$fd;
                $real_flag = $flag === self::EV_READ ? \Event::READ | \Event::PERSIST : \Event::WRITE | \Event::PERSIST;
                $event = new \Event($this->_eventBase, $fd, $real_flag, $func, $fd);
                if (!$event||!$event->add()) {
                    return false;
                }
                $this->_allEvents[$fd_key][$flag] = $event;
                return true;
        }
    }

    /**
     * Timer callback.
     * @param null $fd
     * @param int $what
     * @param int $timer_id
     */
    public function timerCallback($fd, $what, $param)
    {
        /**
         * $param
         * 0回调函数
         * 1参数
         * 2事件标签
         * 3事件ID
         * 4 时间索引
         */
        $timer_id = $param[4];

        //如果只是执行一次的时间事件
        if ($param[2] === self::EV_TIMER_ONCE) {
            $this->_eventTimer[$timer_id]->del();
            unset($this->_eventTimer[$timer_id]);
        }

        try {
            call_user_func_array($param[0], $param[1]);
        } catch (\Exception $e) {
            exit($e->getMessage());
        } catch (\Error $e) {
            exit($e->getMessage());
        }
    }


    /**
     * @param mixed $fd
     * @param int $flag
     * @return bool
     */
    public function del($fd, $flag)
    {
        switch ($flag) {

            case self::EV_READ:
            case self::EV_WRITE:

                $fd_key = (int)$fd;
                if (isset($this->_allEvents[$fd_key][$flag])) {
                    $this->_allEvents[$fd_key][$flag]->del();
                    unset($this->_allEvents[$fd_key][$flag]);
                }
                if (empty($this->_allEvents[$fd_key])) {
                    unset($this->_allEvents[$fd_key]);
                }
                break;

            case  self::EV_SIGNAL:

                $fd_key = (int)$fd;
                if (isset($this->_eventSignal[$fd_key])) {
                    $this->_allEvents[$fd_key][$flag]->del();
                    unset($this->_eventSignal[$fd_key]);
                }
                break;

            case self::EV_TIMER:
            case self::EV_TIMER_ONCE:
                if (isset($this->_eventTimer[$fd])) {
                    $this->_eventTimer[$fd]->del();
                    unset($this->_eventTimer[$fd]);
                }
                break;
        }
        return true;
    }

    /**
     *
     * @return void
     */
    public function clearAllTimer()
    {
        foreach ($this->_eventTimer as $event) {
            $event->del();
        }
        $this->_eventTimer = array();
    }


    /**
     * 启动事件监听
     */
    public function loop()
    {
        $this->_eventBase->loop();
    }


}
