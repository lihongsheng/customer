<?php
/**
 * MultiWork.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/6/28 下午9:57
 * 修改记录:
 *
 * $Id$
 */

namespace Chat\Model;

use customer\Lib\MutliProcess;
use customer\Lib\WorkInterface;


class MultiWork extends WorkInterface
{



    public function installSignal()
    {

        //注册
        pcntl_signal(SIGINT, array($this, 'signalHandler'),false);
        pcntl_signal(SIGTERM,  array($this, 'signalHandler'),false);


    }

    public function stop()
    {
        echo "pcntl ::::::::".posix_getpid().PHP_EOL;
        exit(0);
    }


    public function run() {
        $this->setProcessTitle("work::work");
        $this->installSignal();
        while (true) {
            //回调信号注册处理函数
            pcntl_signal_dispatch();
            sleep(10);
            echo posix_getpid()."::WORK::".date("Y-m-d H:i:s").PHP_EOL;
        }
    }




}