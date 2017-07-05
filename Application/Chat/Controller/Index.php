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


    /**
     * 测试 websocket
     */
    public function indexAction() {
        //phpinfo();
        try {
           // echo 'hell word' . PHP_EOL;
            /*$work = new Work();
            $work->run();*/

            $workModel = new MutliProcess(4);
            $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);
            socket_bind($listen, '0.0.0.0', 20072);
            socket_listen($listen);

            $work = new Work($listen);
            $workModel->setWork($work);
            echo $workModel->MasterId.PHP_EOL;
            $workModel->start();


        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }






}