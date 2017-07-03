<?php
/**
 * TextSocket.php
 * 文本协议
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/16 下午10:57
 * 修改记录:
 *
 * $Id$
 */





namespace customer\Lib\Protocol;

use customer\Lib\Connect\ConnectInterface;

use customer\Lib\Protocol\Protocol;

class TextSocket extends Protocol
{

    public function __construct() {

        $this->ishandle = false;
    }




    public  function handle($buffer)
    {


    }


    /**
     *
     * @param $buffer
     * @param ConnectInterface $connect
     */
    public function input($buffer,ConnectInterface $connect) {
        $pos = strpos($buffer, "\n");
        if($pos === false) {
            return 0;
        }

        return $pos+1;
    }


    /**
     * @param $buffer
     * @return string
     */
    public  function decode($buffer)
    {
        return str_replace("\n","#",$buffer);
    }

    /**信息编码
     * @param string $msg
     * @return string
     */
    public  function encode($msg)
    {
        return $msg."\n";
    }

    /**
     * @return bool
     */
    public function isHandle() {
        return $this->ishandle;
    }

}