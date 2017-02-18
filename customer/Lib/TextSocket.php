<?php
/**
 * TextSocket.php
 * 文本协议
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/16 下午10:57
 * 修改记录:
 *
 * $Id$
 */

namespace customer\Lib;

use customer\Lib\SocketSelect;

class TextSocket extends SocketSelect
{


    public static function handshake($buffer)
    {

        return '';
    }

    /**
     * @param $buffer
     * @return string
     */
    public static function decode($buffer)
    {
        return $buffer;
    }

    /**信息编码
     * @param string $msg
     * @return string
     */
    public static function encode($msg)
    {
        return $msg;
    }

}