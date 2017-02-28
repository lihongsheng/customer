<?php
/**
 * Event.php
 * 时间集合
 * 作者: lihongsheng (549940183@qq.com)
 * 创建日期: 17/2/22 上午12:47
 * 修改记录:
 *
 * $Id$
 */



namespace Cli\Model;

abstract class Event
{
    //往getway发送发送工作的标示
    const LINK_TYPE_GETWAY = 'getway';
    //往work进程发送工作的标示
    const LINK_TYPE_WORK   = 'work';
    //保持心跳的标示
    const LINK_TYPE_PING   = 'ping';
    //是register链接的标示
    const LINK_TYPE_REGISTER = 'register';
    //链接事件
    const EVENT_TYPE_LINK  = 'link';
    //ping事件
    const EVENT_TYPE_PING  = 'ping';
    //消息事件
    const EVENT_TYPE_MSG   = 'msg';
    //绑定用户UID事件
    const EVENT_TYPE_BIND_UID = 'bind';

    //添加getway事件
    const EVENT_TYPE_ADD_GETWAY = 'addGetWay';
    //删除getway事件
    const EVENT_TYPE_DEL_GETWAY = 'delGetway';


    abstract public function run();
    abstract public function work();

}