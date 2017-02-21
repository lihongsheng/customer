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

abstract class SocketSelect
{

    private $_connectLink = array();
    private $_connectValues = array();
    private $_registerLink = array();
    private $_workLink    = array();
    private static $_connectionIdRecorder;


    const SOCKET_TYPE_ACCEPT = 1;
    const SOCKET_TYPE_READ   = 2;
    const SOCKET_TYPE_CLOSE  = 3;

    //创建
    //protected static $listen;
    //作为客户端的链接
    //protected static $clientListen = array();

//为work创建并监听 端口
    public static function createAndListen($ip, $port)
    {
        $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($listen, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($listen, $ip, $port);
        socket_listen($listen);
        return $listen;
    }


    public static function clientListen($ip,$port)
    {
        $listen = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(!socket_connect($listen,$ip,$port)) {
            $errorId = socket_last_error($listen);
            throw new \Exception("NET ERROR ".socket_strerror($errorId));
        }
        return $listen;
    }

    /**
     * 获取fd
     * @return mixed
     */
    /*public static function getListen()
    {
        return self::$listen;
    }*/

   /*public static function getClientListen()
    {
        return self::$clientListen;
    }*/

    /**
     * @param $acceptLink
     * @param $server
     * @return array
     * @throws \Exception
     */
    public static function accept(& $acceptLink,& $server)
    {
        $links = $acceptLink;

        $intLinks = socket_select($links,$write=null,$except=null,0);//无阻赛运行
        if($intLinks === false) {
            $errorId = socket_last_error();
            if($errorId != 4) {
                throw new \Exception(" accept ERROR ".$errorId.'-'.EINTR.' string '.socket_strerror($errorId));
            }
        }
        $buffer = '';

        foreach($links as $k=>$r){
            if(in_array($r,$server)) {//有新的链接进来
                return ['link'=>socket_accept($r),
                    'type'=>self::SOCKET_TYPE_ACCEPT,
                    'key'=>array_search($r,$server),
                    'msg'=>''];
            } else {
                $data = socket_recv($r,$buffer ,2048,0);
                if($data < 7) {
                    self::stop($r);
                    return ['link'=>$r,
                        'type'=>self::SOCKET_TYPE_CLOSE,
                        'key'=>$k,
                        'msg'=>''];
                }

                return ['link'=>$r,
                    'type'=>self::SOCKET_TYPE_READ,
                    'key'=>$k,
                    'msg'=>$buffer];

            }
        }
        return [];
    }


    public static function sendOne($msg,$sign)
    {
        //socket_write(self::encode($msg),$sign);
        $no = socket_write($sign, $msg, strlen($msg));
        //$nos = socket_send($sign,$msg,strlen($msg),0);
    }

    public static function sendMutily($msg,array $signs)
    {
        //$msg = self::encode($msg);
        foreach($signs as $r) {
            //socket_write(self::encode($msg),$r);
            socket_write($r, $msg, strlen($msg));
        }
    }

    public static function close($sign)
    {
        socket_close($sign);
    }

    /**
     * 生成connection id
     * @return int
     */
    public function generateConnectionId()
    {
        $max_unsigned_int = 4294967295;
        if (self::$_connectionIdRecorder >= $max_unsigned_int) {
            self::$_connectionIdRecorder = 1;
        }
        $id = self::$_connectionIdRecorder ++;
        return $id;
    }

    abstract public static function encode($msg);
    abstract public static function decode($buffer);
    abstract public static function handshake($buffer);


}