<?php
/**
 * WorkInterface.php
 * 工作进程必须继承的类
 * 作者:
 * 创建日期: 17/7/11 下午7:14
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

abstract class WorkInterface
{
    /**
     *
     * 信号注册函数
     */

    abstract public  function installSignal();





    /**
     * 信号处理
     *
     * @param int $signal
     */

    public function  signalHandler($signal)
    {

        switch ($signal) {
            // Stop.
            case SIGINT:
                $this->stop();
                break;
            case SIGTERM:
                $this->stop();
                break;
            // Reload.
            case SIGUSR1:
                //$this->reload();
                break;
            // Show status.
            case SIGUSR2:
                //$this->writeStatisticsToStatusFile();
                break;
        }
    }


    public function stop() {
        exit(0);
    }


    abstract public function run();
}