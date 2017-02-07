<?php
/**
 * Pcntl.php
 * 进程控制类
 * 作者: 李红生 (549940183@qq.com)
 * 创建日期: 17/2/5 下午11:53
 * 修改记录:
 *
 * $Id$
 */
namespace customer\Lib;

class PcntlModel
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
    private $MasterId;

    /**
     * work进程
     * @var array
     */
    private $works = [];

    /**
     * 子进程工作方法
     * @var string
     */
    private $workRun;

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


    public function __construct()
    {
        $this->setDaemonize();
        $this->setMaxsize();
        $this->daemonize();
        $this->resetStd();
        $this->installSignal();
        $this->monitorWorkers();
    }

    /**
     * 设置子进程工作
     * @param $work
     * @param $run
     */
    public function setWork($work, $run)
    {
        $this->work    = $work;
        $this->workRun =  $run;
    }

    public function setMaxsize()
    {
        $this->MaxSize = Config::$MaxSize;
    }

    public function setDaemonize()
    {
        $this->Daemonize = Config::$Daemonize;
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

    //运行子进程程序
    public function start() {

        $i = 0;
        while ($i<PcntlModel::$MaxSize) {
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

    }

    /**
     * 子进程工作启动
     */
    private function forkWork() {
        $run = $this->workRun;
        $this->work->$run();
        $pid = posix_getpid();
        $this->reinstallSignal();
    }

    protected function forkOne() {
        $pid = pcntl_fork();
        if($pid === -1){
            throw new Exception("forkOneWorker fail ON LINE ".__LINE__);
        } else if($pid === 0) {
            $this->forkWork();
        }
        $this->works[$pid] = $pid;
    }

    /**
     * 重置输出
     * @throws Exception
     */
    protected function resetStd()
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
        // stop
        pcntl_signal(SIGINT, array($this, 'signalHandler'), false);
        // reload
        pcntl_signal(SIGUSR1, array($this, 'signalHandler'), false);
        // status
        pcntl_signal(SIGUSR2, array($this, 'signalHandler'), false);
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }


    protected function reinstallSignal()
    {
        // uninstall stop signal handler
        pcntl_signal(SIGINT, SIG_IGN, false);
        // uninstall reload signal handler
        pcntl_signal(SIGUSR1, SIG_IGN, false);
        // uninstall  status signal handler
        pcntl_signal(SIGUSR2, SIG_IGN, false);
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
                $this->stopAll();
                break;
            // Reload.
            case SIGUSR1:
                $this->reload();
                break;
            // Show status.
            case SIGUSR2:
                $this->writeStatisticsToStatusFile();
                break;
        }
    }

    protected function reload()
    {

    }

    protected function writeToStatusFile()
    {

    }


    /**
     * Stop.
     *
     * @return void
     */
    public function stopAll()
    {
        $this->Status = PcntlModel::STATUS_SHUTDOWN;

        if(!empty($this->works)) {
            foreach($this->works as $val) {
                posix_kill($val,SIGINT);
            }
        }
        $this->exitAndClearAll();

    }





    public function monitorWorkers()
    {

        while (1) {
            //等待信号处理器
            pcntl_signal_dispatch();
            $status = 0;
            $pid    = pcntl_wait($status, WUNTRACED); //子进程已经退出并且其状态未报告时返回

            pcntl_signal_dispatch();
            // 如果一个子进程已经退出
            if ($pid > 0) {
                if ($this->Status !== PcntlModel::STATUS_SHUTDOWN) {
                    if($this->works[$pid]) {
                        unset($this->works[$pid]);
                        $this->forkOne();
                        if ($status !== 0) {
                            $this->log("worker[:$pid] exit with status $status");
                        }
                    }
                } else {
                    $this->exitAndClearAll();
                }
            } else {
                // 主进程shutdown并且子进程全部exit
                if ($this->Status === PcntlModel::STATUS_SHUTDOWN && empty($this->works)) {
                    self::exitAndClearAll();
                }
            }
            //主进程工作ID
            if(!empty($this->masterWork)) {
                call_user_func($this->masterWork);
            }
        }

    }

    /**
     *
     */
    protected static function exitAndClearAll()
    {
        //清除工作
        exit(0);
    }





}