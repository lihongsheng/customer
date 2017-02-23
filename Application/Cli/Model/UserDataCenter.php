<?php
/**
 * UserDataCenter.php
 *
 * 基于reids来存储当前来自getway的用户链接属性，
 * 以便work获取用户信息及时处理，根据用户的ip,port来选择分发
 * （NOTE 还可以依据register模式自己实现一个用户存储中心模式）
 *
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/22 下午11:51
 * 修改记录:
 *
 * $Id$
 */
namespace Cli\Model;


use Cli\Model\CDkey;
use customer\Lib\Config;

/**
 * 单列模式提供
 * Class UserDataCenter
 * @package Cli\Model
 */
class UserDataCenter
{

    private static $instance = null;
    private $redisModel;
    public static function Instance()
    {
        if(!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 初始化一些操作
     */
    private function __construct()
    {
        $this->redisModel = new \Redis();
        $this->redisModel->pconnect(Config::RedisIp,Config::RedisPort);
        if(!Config::RedisPwd) {
            $this->redisModel->auth(Config::RedisPwd);
        }
    }

    /**
     * @param $ip
     * @param $userId
     * @param $port
     */
    public function addUser($userId, $ip, $port, $pid, $extend = [])
    {
        $userData = [
            'uid'=>$userId,
            'ip'=>$ip,
            'port'=>$port,
            'pid'=>$pid,
            'extend'=>$extend,
            'online'=>1
        ];
        $key = CDkey::USER_GETWAY_INFO.$userId;
        $this->redisModel->set($key,json_encode($userData));
    }

    /**
     * @param $userId
     * @param $ip
     * @param $port
     * @param $pid
     * @param array $extend
     */
    public function updateUser($userId, $ip, $port, $pid, $extend = [])
    {
        $userData = [
            'uid'=>$userId,
            'ip'=>$ip,
            'port'=>$port,
            'pid'=>$pid,
            'extend'=>$extend,
            'online'=>1,
        ];
        $key = CDkey::USER_GETWAY_INFO.$userId;
        $this->redisModel->set($key,json_encode($userData));
    }


    /**
     * 用户是否在线
     * @param $userId
     * @return bool
     */
    public function isOnline($userId)
    {
        $key = CDkey::USER_GETWAY_INFO.$userId;
        if($this->redisModel->exists($key)) {
            $tmp = $this->getUserInfo($userId);
            if($tmp['online'] == 1) {
                return true;
            }
        }

        return false;
    }


    /**
     * 检测用户是否注册
     * @param $userId
     * @return bool
     */
    public function userCheck($userId)
    {
        $key = CDkey::USER_GETWAY_INFO.$userId;
        return $this->redisModel->exists($key);
    }


    /**
     * 获取用户信息
     * @param $userId
     * @return bool|string
     */
    public function getUserInfo($userId)
    {
        $key = CDkey::USER_GETWAY_INFO.$userId;
        if($this->redisModel->exists($key)) {
            return json_decode($this->redisModel->get($key),true);
        }


        return false;
    }


}