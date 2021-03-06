<?php
/**
 * MutliProcess.php
 *
 * 多进程管理
 * 作者: 李红生 (54991083@qq.com)
 * 创建日期: 17/6/26 下午10:38
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

use customer\Lib\Router;

class MutliProcess
{
    /**
     * 启动状态
     *
     * @var int
     */
    const STATUS_STARTING = 1;

    /**
     * 运行状态
     *
     * @var int
     */
    const STATUS_RUNNING = 2;

    /**
     * 关闭状态
     *
     * @var int
     */
    const STATUS_SHUTDOWN = 4;

    /**
     * 重启状态
     *
     * @var int
     */
    const STATUS_RELOADING = 8;

    /**
     * 是否守护进程
     * @var bool
     */
    private $Daemonize = false;

    /**最多开启的工作进程数
     * @var int
     */
    private $MaxSize;

    /**masterId 主进程ID
     * $var int
     */
    public $MasterId;

    /**
     * work进程
     * @var array
     */
    private $works = [];


    /**
     * 子进程工作方法
     * @var string
     */
    //private $workRun;

    /**
     * 子进程工作对象
     * @var object
     */
    private $work;

    /**
     * 主进程工作
     * @var function
     */
    public $masterWork;


    /**
     * 标准重定向输出的文件
     * @var string
     */
    protected $StdoutFile = '/tmp/pcntl.log';



    /**
     * 存储PID文件的地方
     * @var string
     */
    protected $PidFile = 'multiWork.php';


    protected $Status = 1;

    /**
     * MutliProcess constructor.
     * @param int $maxSize
     * @param bool $daemonize
     * @param Router $router
     */
    public function __construct($maxSize = 4, $daemonize = false, Router $router)
    {
        //设置最大启动的子进程数
        $this->setMaxsize($maxSize);
        //是否变成守护进程
        $this->setDaemonize($daemonize);

        //pidfile
        //$this->PidFile = CACHE_PATH.$this->PidFile;
        //基于modlue_method_action命名缓存文件
        $this->PidFile = CACHE_PATH.$router->getModule().'_'.$router->getMethod().'_'.$router->getAction().'.php';

        //监控状态
        $this->statusHandle();

        //是否变成守护进程
        $this->daemonize();
        //重定向输出，标准输出(echo,var_*,error,输出到文件)
        $this->resetStd();
        $this->setProcessTitle("work::master");
        $this->installSignal();

    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if(isset($this->$name)) {
            return $this->$name;
        }
    }


    /**
     * 设置进程名字
     *
     * @param string $title
     * @return void
     */
    protected  function setProcessTitle($title)
    {

        if(function_exists("cli_set_process_title")) {
            cli_set_process_title($title);
        } else if (extension_loaded('proctitle') && function_exists('setproctitle')) {
            @setproctitle($title);
        }

    }



    /**
     * 外部状态处理
     * 如 stop,restart,start,reload
     */
    protected function statusHandle() {

        $status = $_SERVER['argv'][2] ? $_SERVER['argv'][2] : $argv[2];
        switch ($status) {
            case "stop":
                echo "it is stop ....... ".PHP_EOL;
                $this->killAllProcess();
                exit(0);
                break;
            case "restart":
                echo "it is stop ....... ".PHP_EOL;
                $this->killAllProcess();
                echo "it is start ....... ".PHP_EOL;
                break;
            /*case "start":
                $this->start();
                break;
            case "restart":
                $this->retart();
                break;
            case "reload":
                $this->reload();
                break;*/
            default:
        }
    }

    /**
     * 杀死所有进程
     */
    protected function killAllProcess() {
        $pids = $this->getAllPid();
        //var_dump($pids);
        if(isset($pids['masterPid']) && $pids['masterPid']) {
            //发送 kill 信号
            posix_kill($pids['masterPid'],SIGINT);
        }
        sleep(5);
        if(isset($pids['masterPid']) && $pids['masterPid']) {
            posix_kill($pids['masterPid'], SIGKILL);
        }
        if(isset($pids['sonPid'])) {
            foreach ($pids['sonPid'] as $pid) {
                posix_kill($pid, SIGKILL);
            }
        }
    }


    /**
     * 设置最大进程数
     * @param $maxSize
     */
    private function setMaxsize($maxSize)
    {
        $this->MaxSize = $maxSize;
    }

    /**
     * @param $daemonize
     */
    public function setDaemonize($daemonize)
    {
        $this->Daemonize = $daemonize;
    }


    /**
     * 进程守护
     */
    public function daemonize()
    {
        if(!$this->Daemonize) {
            return false;
        }
        $pid = pcntl_fork();//创建子进程
        if($pid == -1) {
            throw new \Exception('创建进程失败');
        } else if($pid > 0) {
            //让父进程退出。以便开启新的会话
            exit(0);
        }
        //建立一个有别于终端的新的会话,脱离当前会话终端，防止退出终端的时候，进程被kill
        if(-1 === posix_setsid()) {
            throw new \Exception('创建会话失败');
        }
        $pid = pcntl_fork();
        if($pid == -1) {
            throw new \Exception('创建进程失败');
        } else if($pid > 0) {
            //父进程推出剩下的子进程为独立进程，归为系统管理此进程
            exit(0);
        }
    }


    /**
     * 重置输出
     * @throws Exception
     */
    public function resetStd()
    {
        if (!$this->Daemonize) {
            return;
        }
        global $STDOUT, $STDERR;
        $handle = fopen($this->StdoutFile, "a");
        if ($handle) {
            unset($handle);
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen($this->StdoutFile, "a");
            $STDERR = fopen($this->StdoutFile, "a");
        } else {
            throw new Exception('can not open stdoutFile ' . $this->StdoutFile);
        }
    }


    /**
     * 注册信号处理函数
     *
     * @return void
     */
    protected function installSignal()
    {
        //
        pcntl_signal(SIGINT, array($this, 'signalHandler'),false);
        pcntl_signal(SIGTERM, SIG_IGN,  array($this, 'signalHandler'),false);
        // 用户自定义信号
        pcntl_signal(SIGUSR1, array($this, 'signalHandler'), false);
        //  用户自定义信号
        pcntl_signal(SIGUSR2, array($this, 'signalHandler'), false);
        // ignore
        //pcntl_signal(SIGPIPE, SIG_IGN, false);


    }


    /**
     * 重装信号处理函数
     * 如果子进程有自己的处理方式，需要重装信号处理函数
     *
     * @return void
     */
    protected function reInstallSignal()
    {
        // 取消父进程注册的信号处理函数
        pcntl_signal(SIGINT, SIG_IGN, false);
        pcntl_signal(SIGTERM, SIG_IGN, false);

        pcntl_signal(SIGUSR1, SIG_IGN, false);
        pcntl_signal(SIGUSR2, SIG_IGN, false);
        //pcntl_signal(SIGPIPE, SIG_IGN, false);


    }



    /**
     * 信号处理
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {

        switch ($signal) {
            // Stop.
            case SIGINT:
                $this->stop();
                break;
            case SIGTERM:
                $this->stop();
                break;
            // Reload.
            case SIGUSR1:
                //$this->reload();
                break;
            // Show status.
            case SIGUSR2:
                //$this->writeStatisticsToStatusFile();
                break;
        }
    }


    /**
     * 子进程工作启动
     */
    private function forkWork() {

        $pid = posix_getpid();
        $this->reinstallSignal();
        $this->work->run();

    }


    /**
     * 创建一个新进程
     */
    protected function forkOne() {
        $pid = pcntl_fork();
        if($pid === -1){
            if(count($this->works)) {
                echo "forkOneWorker fail ON LINE ".__LINE__;
            } else {
                echo "no child pcntl and forkOneWorker fail ON LINE " . __LINE__;
                exit(0);
            }

        } else if($pid === 0) {
            $this->forkWork();
        }
        //存储所有的子进程 ID
        $this->works[$pid] = $pid;
    }


    /**
     *
     * @throws \Exception
     */
    public function monitorWorkers()
    {
        $this->Status = self::STATUS_RUNNING;
        $this->setPidFile();

        while (true) {
            //等待信号处理器
            pcntl_signal_dispatch();
            $status = -1;
            $pid    = pcntl_wait($status, WNOHANG); //子进程已经退出并且其状态未报告时返回

            pcntl_signal_dispatch();
            // 如果一个子进程已经退出
            if ($pid > 0 && $this->MaxSize) {
                //如果不是不是stop状态
                if ($this->Status !== self::STATUS_SHUTDOWN) {
                    if($this->works[$pid]) {
                        unset($this->works[$pid]);
                        $this->forkOne();
                        if ($status !== 0) {

                            // $this->log("worker[:$pid] exit with status $status");
                            //throw new \Exception("worker[:$pid] exit with status $status");
                            echo PHP_EOL."worker[:$pid] exit with status $status".PHP_EOL;
                        }
                        $this->setPidFile();
                    }
                } else {
                    //$this->exitAndClearAll();
                    //删除 已经停止的 进程pid数据
                    echo 'kill ::::'.$pid.PHP_EOL;
                    unset($this->works[$pid]);
                }
            } else {
                // 主进程shutdown并且子进程全部exit
                if ($this->Status === self::STATUS_SHUTDOWN && empty($this->works)) {
                    $this->exitAndClearAll();
                }
            }
            //主进程工作ID
            if(!empty($this->masterWork)) {
                call_user_func($this->masterWork);
            }

            pcntl_signal_dispatch();
        }

    }


    /**
     * 停止进程
     */
    public function stop() {

        $this->Status = self::STATUS_SHUTDOWN;
        $this->stopAllChild();
        echo "STOP SUCCESS";

        /**
         * 做一些清理工作
         */
    }




    /**
     * 停止子进程
     */
    protected function stopAllChild() {

            if(!empty($this->works)) {

                foreach($this->works as $val) {
                    file_put_contents("/tmp/kill.log)", "kill ".$val.PHP_EOL,FILE_APPEND);
                    posix_kill($val,SIGINT);
                    /*sleep(2);*/
                    //posix_kill($val,SIGKILL);
                }
            }
    }


    /**
     *
     */
    protected  function exitAndClearAll()
    {
        //清除工作
        echo "清楚工作".PHP_EOL;
        $str = "<?php return [];";
        file_put_contents($this->PidFile,$str);
        exit(0);
    }


    //运行子进程程序
    public function start() {

        $i = 0;
        while ($i<$this->MaxSize) {
            $i++;
            $pid = pcntl_fork();
            if($pid === 0) {
                $this->forkWork();
            } else if ($pid === -1) {
                if($i>0) {
                    $i--;
                }
            }
            //子进程ID
            $this->works[$pid] = $pid;
        }
        //主进程ID
        $this->MasterId = posix_getpid();
        $this->monitorWorkers();

    }


    /**
     * 设置子进程工作
     * @param $work
     */
    public function setWork($work)
    {
        $this->work    = $work;
    }



    protected function setPidFile() {
        $data = [
            'masterPid' => $this->MasterId,
            'sonPid'    => $this->works,
        ];

        $str = "<?php return ".var_export($data,true).";";
        $isWrite = file_put_contents($this->PidFile,$str);
        if($isWrite === false) {
            exit("write fail in the ".$this->PidFile);
        }
    }


    protected function getAllPid() {

        $tmp =  include_once $this->PidFile;
        return $tmp;
    }

}