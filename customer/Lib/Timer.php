<?php
/**
 * Timer.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/18 下午6:12
 * 修改记录:
 *
 * $Id$
 */

namespace customer\Lib;

class Timer
{
    protected static $_tasks  = [];
    protected static $_taskId = [];


    public static function init()
    {
        pcntl_signal(SIGALRM, array('customer\Lib\Timer', 'signalHandle'), false);
    }


    /**
     * ALARM signal handler.
     *
     * @return void
     */
    public static function signalHandle()
    {
        self::tick();
        //pcntl_alarm(1);
    }



    /**
     * 添加定时器事件
     *
     * @param int      $time_interval  时间
     * @param callback $func    回调函数
     * @param mixed    $args    函数参数
     * @param bool     $persistent  是否重新注册
     * @return string
     */
    public static function add($time_interval, $func, $args = array(), $persistent = true)
    {
        if ($time_interval <= 0) {
            echo new Exception("bad time_interval");
            return false;
        }


        if (!is_callable($func)) {
            echo new Exception("not callable");
            return false;
        }

        $id = md5(get_class($func[0]).$func[1]);
        if(!self::$_taskId[$id]) {
            self::$_taskId[$id] = $id;
        }

        /*if (empty(self::$_tasks)) {
            pcntl_alarm(1);
        }*/
        pcntl_alarm(1);
        $time_now = time();
        $run_time = $time_now + $time_interval;
        if (!isset(self::$_tasks[$run_time])) {
            self::$_tasks[$run_time] = array();
        }
        self::$_tasks[$run_time][] = array($func, (array)$args, $persistent, $time_interval);
        return $id;
    }

    /**
     * Tick.
     *
     * @return void
     */
    public static function tick()
    {
        if (empty(self::$_tasks)) {
            pcntl_alarm(0);
            return;
        }

        $time_now = time();
        foreach (self::$_tasks as $run_time => $task_data) {
            if ($time_now >= $run_time) {
                foreach ($task_data as $index => $one_task) {
                    $task_func     = $one_task[0];
                    $task_args     = $one_task[1];
                    $persistent    = $one_task[2];
                    $time_interval = $one_task[3];
                    try {
                        call_user_func_array($task_func, $task_args);
                    } catch (\Exception $e) {
                        echo $e;
                    }
                    $id = md5(get_class($task_func[0]).$task_func[1]);
                    if ($persistent && self::$_taskId[$id]) {
                        self::add($time_interval, $task_func, $task_args);
                    }
                }
                unset(self::$_tasks[$run_time]);
            }
        }
    }


    public static function del($time_id)
    {
        if(self::$_taskId[$time_id]) {
            if(!empty(self::$_tasks)) {
                foreach (self::$_tasks as $run_time => $task_data) {
                    foreach ($task_data as $index => $one_task) {
                        $task_func = $one_task[0];
                        $id = md5(get_class($task_func[0]).$task_func[1]);
                        if($id == $time_id) {
                            unset(self::$_tasks[$run_time],self::$_taskId[$time_id]);
                        }
                    }
                }
            }
        }
    }

    public static function delAll()
    {
        self::$_tasks  = [];
        self::$_taskId = [];
    }
}