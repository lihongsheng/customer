<?php
/**
 * SocketSelect.php
 * 基于事件轮询的socket处理
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/8 下午11:54
 * 修改记录:
 *
 * $Id$
 */

namespace customer\Lib;

class SocketSelect
{

    private $_connectLink = array();
    private $_connectValues = array();
    private $_registerLink = array();
    private $_workLink    = array();

    protected static $listen;

//为work创建并监听 端口
    public static function createAndListen($ip, $port)
    {
        self::$listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option(self::$listen, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind(self::$listen, $ip, $port);
        socket_listen(self::$listen);
        return self::$listen;
    }

    public static function accept(& $acceptLink)
    {

    }

}