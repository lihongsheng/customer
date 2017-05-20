<?php
/**
 * Protocol.php
 *
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/4/30 上午12:31
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib\Protocol;

abstract class Protocol {
    protected $ishandle = false;
    abstract public function encode($msg);
    abstract public function decode($buffer);
    abstract public function handle($buffer);
    /*abstract public function isProtocol($buffer);*/
    abstract protected function isHandle();
}