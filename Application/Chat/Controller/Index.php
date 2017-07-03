<?php
/**
 * Index.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/5/16 下午10:58
 * 修改记录:
 *
 * $Id$
 */
namespace Chat\Controller;

use Chat\Model\TextWork;
use Chat\Model\Work;
use customer\Lib\Controller;
use Chat\Model\MultiWork;
use customer\Lib\MutliProcess;

class Index extends Controller{

    public function indexAction() {
        //phpinfo();
        try {
           // echo 'hell word' . PHP_EOL;
            $work = new Work();
            $work->run();
        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }



    public function workAction() {

        $work = new MutliProcess(4);
        $workModel = new MultiWork();
        $work->setWork($workModel);
        $work->start();
    }


    public function indexTextAction() {
        //phpinfo();
        try {
            // echo 'hell word' . PHP_EOL;
            $work = new TextWork();
            $work->run();
        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}