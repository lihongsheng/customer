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



use customer\Lib\Connect\ConnectInterface;
use customer\Lib\Connect\TcpConnect;
use customer\Lib\Protocol\Protocol;

class WebSocket extends Protocol
{

    public function __construct() {
        $this->ishandle = true;
    }


    public function input($buffer, ConnectInterface $connect) {

        echo "websocket start".PHP_EOL;
        $bufferLen = strlen($buffer);
        if($bufferLen < 2) {
            echo "2:::: ".$this->decode($buffer).PHP_EOL;
            return 0;
        }

        echo "websocket protocol start ".PHP_EOL;
        //获取第一字符（8个bit位）
        $firstbyte = ord($buffer[0]);
        //右移位 获取第一个bit位的fin码
        $fin       = $firstbyte >> 7;
        //获取后四个字节的opcode
        $opcode    = $firstbyte & 0xf;//与 10000000 相与

        //获取第二个字符（8个bit位）
        $secondbyte  = ord($buffer[1]);
        //获取masked是否需要编码
        $masked      = $secondbyte >> 7;

        //获取 数据位长度
        $dataLen     = $secondbyte & 127; //与 01111111 相与

        switch ($opcode) {
        //x0表示是延续frame；x1表示文本frame；x2表示二进制frame；x3-7保留给非控制frame；x8表示关 闭连接；x9表示ping；xA表示pong；xB-F保留给控制frame
            //continue frame
            case 0x0:
                break;
            // Blob type.
            case 0x1:
                break;
            // Arraybuffer type.
            case 0x2:
                break;
            // Close package.
            case 0x8:
                $connect->close();
                return 0;
                //ping
            case 0x9:
                $connect->send(pack('H*', '8a00'), true);
                if (!$dataLen) {
                    $headLen = $masked ? 6 : 2;

                    if ($bufferLen > $headLen) {
                        $connect->setRecv(substr($buffer,$headLen));
                    }
                    return 0;
                }
                break;
            // Pong package.处理对于ping数据回复
            case 0xa:
                if (!$dataLen) {
                    $headLen = $masked ? 6 : 2;

                    if ($bufferLen > $headLen) {
                        $connect->setRecv(substr($buffer,$headLen));
                    }
                    return 0;
                }
        }

        echo " websocket    parse".PHP_EOL;

        $headLen = 6;//一般前六个字符未协议头
        if($dataLen == 126) {
            $headLen = 8;
            //数据接收一半，继续接受
            if ($headLen > $bufferLen) {
                return 0;
            }
            $pack     = unpack('nn/ntotal_len', $buffer);
            //实际data长度
            $dataLen = $pack['total_len'];
        } else {
            if($dataLen == 127) {
                $headLen = 14;
                //数据接收一半，继续接受
                if ($headLen > $bufferLen) {
                    return 0;
                }
                $arr      = unpack('n/N2c', $buffer);
                //实际data长度
                $dataLen = $arr['c1']*4294967296 + $arr['c2'];
            }
        }

        //frame长度
        $currentLen = $headLen + $dataLen;
        //数据未接受完毕
        if($currentLen > $bufferLen) {
            return 0;
        }

        if($currentLen <= $bufferLen) {
            //最后一个包
            if($fin) {
                echo "currentLen ".$currentLen.PHP_EOL;
                return $currentLen;
            } else {
                $tmpBuffer = substr($buffer,0,$currentLen);
                $connect->setRecv(substr($buffer,$headLen));
                $tmpData = $this->decode($tmpBuffer);
                echo "tmpData ".$tmpData.PHP_EOL;
                $connect->setTmpData($tmpData);
            }


        }


    }


    /*
    *发送WS协议，建立WS协议链接
    *@param string WS发送的请求协议内容
    */
    public function handle($buffer)
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



    /**
     * Websocket decode.
     *
     * @param string              $buffer
     * @param ConnectionInterface $connection
     * @return string
     */
    public function _decode($buffer)
    {
        $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data  = substr($buffer, 8);
        } else {
            if ($len === 127) {
                $masks = substr($buffer, 10, 4);
                $data  = substr($buffer, 14);
            } else {
                $masks = substr($buffer, 2, 4);
                $data  = substr($buffer, 6);
            }
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return $decoded;

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
     * @return bool
     */
    public function isHandle() {
        return $this->ishandle;
    }

}