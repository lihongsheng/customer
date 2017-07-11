<?php
/**
 * Multi.php
 * 多进程测试样例
 * 作者: 李红生 (dannyzml@qq.com)
 * 创建日期: 17/7/5 下午11:00
 * 修改记录:
 *
 * $Id$
 */

namespace Chat\Controller;


use Chat\Model\MultiWork;
use customer\Lib\Controller;
use customer\Lib\MutliProcess;

class Multi extends Controller {


    /**
     * 测试多进程工作
     */
    public function workAction() {

        $work = new MutliProcess(2,false);
        $workChild = new MultiWork();
        $work->setWork($workChild);
        $work->start();
    }


}