<?php
/**
 * WebSocket.php
 * websocket 协议
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/5 下午3:25
 * 修改记录:
 *
 * $Id$
 */

namespace customer\Lib\Protocol;

use Protocol;

class WebSocket extends Protocol
{

    public function __construct() {
        $this->handle = true;
    }

    /*
    *发送WS协议，建立WS协议链接
    *@param string WS发送的请求协议内容
    */
    public function handshake($buffer)
    {
        //获取KEY及生成新的KEY
        $buf  = substr($buffer,strpos($buffer,'Sec-WebSocket-Key:')+18);
        $key  = trim(substr($buf,0,strpos($buf,"\r\n")));
        $new_key = base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));

        //返回 websocket协议应答
        $new_message = "HTTP/1.1 101 Switching Protocols\r\n";
        $new_message .= "Upgrade: websocket\r\n";
        $new_message .= "Sec-WebSocket-Version: 13\r\n";
        $new_message .= "Connection: Upgrade\r\n";
        $new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";
        return $new_message;
    }


    /**
     * @param $buffer
     * @return string
     */
    public function decode($buffer)
    {
        $mask = array();
        $data = '';
        $msg = unpack('H*',$buffer);
        $head = substr($msg[1],0,2);
        if (hexdec($head{1}) === 8) {
            $data = '';
        }else if (hexdec($head{1}) === 1){
            $mask[] = hexdec(substr($msg[1],4,2));
            $mask[] = hexdec(substr($msg[1],6,2));
            $mask[] = hexdec(substr($msg[1],8,2));
            $mask[] = hexdec(substr($msg[1],10,2));
            $s = 12;
            $e = strlen($msg[1])-2;
            $n = 0;
            for ($i=$s; $i<= $e; $i+= 2) {
                $data .= chr($mask[$n%4]^hexdec(substr($msg[1],$i,2)));
                $n++;
            }
        }
        return $data;
    }

    /**信息编码
     * @param string $msg
     * @return string
     */
    public function encode_old($msg)
    {
        $msg = preg_replace(array('/\r$/','/\n$/','/\r\n$/',), '', $msg);
        $frame = array();
        $frame[0] = '81';
        $len = mb_strlen($msg);
        $frame[1] = $len<16?'0'.dechex($len):dechex($len);
        $frame[2] = $this->ordHex($msg);
        $data = implode('',$frame);
        return pack("H*", $data);
    }

    public function encode($buffer)
    {
        $msg = preg_replace(array('/\r$/','/\n$/','/\r\n$/',), '', $buffer);
        $len = strlen($buffer);
        $first_byte = "\x81";
        if ($len <= 125) {
            $encode_buffer = $first_byte . chr($len) . $buffer;
        } else {
            if ($len <= 65535) {
                $encode_buffer = $first_byte . chr(126) . pack("n", $len) . $buffer;
            } else {
                $encode_buffer = $first_byte . chr(127) . pack("xxxxN", $len) . $buffer;
            }
        }

        return $encode_buffer;
    }

    /**
     * @param string $data
     * @return string
     */
    private function ordHex($data) {
        $msg = '';
        $l = strlen($data);
        for ($i= 0; $i<$l; $i++) {
            $msg .= dechex(ord($data{$i}));
        }
        return $msg;
    }

    /**
     * @param $buffer
     */
    public static function isProtocol($buffer) {

    }

}