<?php
/**
 * Websoc.php
 *
 * 作者:
 * 创建日期: 17/7/5 下午11:08
 * 修改记录:
 *
 * $Id$
 */
namespace Chat\Controller;


use Chat\Model\WebsocketWork;
use customer\Lib\Controller;


class Websoc extends  Controller {
    /**
     * 测试 websocket
     */
    public function indexAction() {
        //phpinfo();
        try {
             echo 'hell word' . PHP_EOL;
            $work = new WebsocketWork();
            $work->run();





        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}