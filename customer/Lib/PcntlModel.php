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
     * 是否守护进程
     * @var bool
     */
    private static $Daemonize = false;

    /**最多开启的工作进程数
     * @var int
     */
    private static $MaxSize;

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
        PcntlModel::setDaemonize();
        PcntlModel::setMaxsize();
        PcntlModel::daemonize();
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

    public static function setMaxsize()
    {
        self::$MaxSize = Config::$MaxSize;
    }

    public static function setDaemonize()
    {
        self::$Daemonize = Config::$Daemonize;
    }

    /*cli模式下运行
	*进程守护化函数，使进程脱离当前终端控制，以便后台独立运行。
	执行后需要通过 ps - kill 杀死此进程，
	或者 运行 posix_getpid() 获取 当前进程ID 然后kill
	如果不是service 服务，只是运行时间比较长，最好是在业务程序里加上 exit退出进程。
	*/
    public static function daemonize()
    {
        if(!self::$Daemonize) {
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
            if($pid == 0) {
                $run = $this->workRun;
                $this->work->$run();
                $pid = posix_getpid();
                $this->works[$pid] = $pid;
            }else if ($pid == -1) {
                if($i>0) {
                    $i--;
                }
            }

        }
        /*
         * 主进程工作ID
         */
        if(!empty($this->masterWork)) {
            call_user_func($this->masterWork, $this);
        }
        //$p = pcntl_wait($status);
    }


}