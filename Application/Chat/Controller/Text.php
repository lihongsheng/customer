<?php
/**
 * Text.php
 * 文本协议测试
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/7/5 下午11:02
 * 修改记录:
 *
 * $Id$
 */
namespace Chat\Controller;

use Chat\Model\TextWork;
use customer\Lib\Controller;

class Text extends  Controller{
    /**
     * 测试 text协议（以换行符分割）
     */
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