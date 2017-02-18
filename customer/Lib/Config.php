<?php
/**
 * Config.php
 * 配置类
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/8 上午12:23
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

class Config
{
    //最大进程数量
    const MaxSize   = 4;
    //是否转换为守护进程
    const Daemonize = false;

    //数据库驱动
    const DbDrive = 'pdo';

    //数据库名
    const DbName = 'customer';
    //数据编码
    const DbEncode = 'utf-8';
    //数据Host
    const DbHost = '';

    const DbUserName = '';

    const DbPassWord = '';

    const RegisterIp = '0.0.0.0';

    const RegisterPort = '44540';

    const GetwayIp   = '0.0.0.0';

    const GetwayPort = '46560';

    const WorkIp    = '127.0.0.1';

    const WorkPort  = '';

    static $router = [
        'defaultModule' => 'web',
        'defaultMethod' => 'index',
        'defaultAction' => 'index',
        'ErrorModule'   =>'Error',
        'ErrorMethod'   =>'Index',
        'ErrorAction'   => 'index',
        'urlSuffix'     => '',
        'Modules' => [
            'web','Cli'
        ]

    ];

}