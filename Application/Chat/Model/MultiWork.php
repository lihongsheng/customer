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

class MultiWork
{


    public function installSignal()
    {

    }


    public function run() {
        $this->setProcessTitle("work::work");

        while (true) {
            sleep(10);
            echo posix_getpid()."::WORK::".date("Y-m-d H:i:s").PHP_EOL;
        }
    }


    /**
     * 设置进程名字
     *
     * @param string $title
     * @return void
     */
    protected  function setProcessTitle($title)
    {

        cli_set_process_title($title);
        //        if (function_exists('cli_set_process_title')) {
        //            @cli_set_process_title($title);
        //        }
        //        elseif (extension_loaded('proctitle') && function_exists('setproctitle')) {
        //            @setproctitle($title);
        //        }
    }

}