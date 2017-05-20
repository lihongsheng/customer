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

use Chat\Model\Work;
use customer\Lib\Controller;

class Index extends Controller{

    public function indexAction() {
        //phpinfo();
        try {
            echo 'hell word' . PHP_EOL;
            $work = new Work();
            $work->run();
        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}