<?php
/**
 * ConnectInterface.php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/5/4 下午11:08
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib\Connect;

abstract class ConnectInterface
{
    public static $work;
    public static $protocol;


    public $onConnect;
    public $onClose;
    public $onMessage;
    public $onError;

}