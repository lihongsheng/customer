<?php
/**
 * .php
 *
 * 作者: Bright (dannyzml@qq.com)
 * 创建日期: 17/2/15 上午11:58
 * 修改记录:
 *
 * $Id$
 */
namespace Cli\Controller;

use Cli\Model\Getway;
use Cli\Model\Work;
use customer\Lib\Config;
use customer\Lib\Controller;
use customer\Lib\TextSocket;
use customer\Lib\WebSocket;

use Cli\Model\Register;

class Index extends Controller
{

    public function startServerAction()
    {
        //echo 'hello word;'.PHP_EOL;
        $getway = new Getway();
        $getway->run();
    }

    public function startRegisterAction()
    {
        $register = new Register();
        $register->run();
       /* $link['s'] = TextSocket::createAndListen(Config::RegisterIp,Config::RegisterPort);
        while(true){
            $links = $link;
            $intLink = socket_select($links,$write=null,$except=null,null);

            if($links === false) {
                echo socket_strerror(socket_last_error()).PHP_EOL;
            }


            foreach($links as $val) {
                if($val == $link['s']){
                    $link[] = socket_accept($val);
                    echo ';;;;'.PHP_EOL;
                } else {
                    socket_recv($val,$buffer ,2048,0);
                    echo $buffer;
                }
            }

        }*/

    }

    public function startWorkAction()
    {
       /* sleep(5);
        $registerIp = Config::RegisterIp == '0.0.0.0' ? '127.0.0.1' : Config::RegisterIp;
        $link =  TextSocket::clientListen($registerIp,Config::RegisterPort);
        $nos = socket_send($link,'{"linkType":"ping","eventType":"ping"}',strlen('{"linkType":"ping","eventType":"ping"}'),0);
        while(true){
            sleep(2);
            $nos =socket_write($link,'{"linkType":"ping","eventType":"ping"}',strlen('{"linkType":"ping","eventType":"ping"}'));
            echo $nos.PHP_EOL;
        }*/

        $work = new Work();
        $work->run();
    }

    public function testAction()
    {
        $msgKey = msg_get_queue('57000');
       // msg_send($msgKey,1,['ml'=>';;']);
        msg_receive($msgKey,0,$type,1024,$message,true,MSG_IPC_NOWAIT);
        echo ';;;';
        var_dump($message);
        msg_remove_queue($msgKey);
    }
}