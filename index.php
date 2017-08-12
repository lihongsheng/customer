<?php
/**
 * index.php
 * 入口文件
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/11 下午4:57
 * 修改记录:
 *

ps aux|grep qemu|awk '{print $2}'|xargs kill -9
ps aux|grep qemu|awk '{print $2}'|xargs kill -9
 * $Id$
 */
error_reporting(E_ERROR);
define('ROOT_PATH',dirname(__FILE__).DIRECTORY_SEPARATOR);
define('APP_PATH',ROOT_PATH.'Application'.DIRECTORY_SEPARATOR);
define('LOG_APTH',ROOT_PATH.'log'.DIRECTORY_SEPARATOR);
define('CACHE_PATH',ROOT_PATH.'cache'.DIRECTORY_SEPARATOR);
require_once APP_PATH.'/bootstrap.php';

(new bootstrap())->init()->run();