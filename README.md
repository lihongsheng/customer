# customer

打算做开源的客服系统的项目...........<br/>
由于利用 晚上时间，且是一个月的时间内初步完成，项目还有很多不足处，后期会慢慢优化。<br/>
初步支持：<br/>
    1:初步实现了命令参数式控制<br/>
    2:进程控制及监控<br/>
    3:测试websocket及基于文本的进程间通信<br/>
    4:模块间的划分<br/>
    5:定时器任务<br/>
    php index.php /chat/multi/work stop<br/>
    php index.php /chat/multi/work restart<br/>
php 进程及网络框架 <br>
这是个个人项目，而且现在的代码还是很粗糙，但是不妨碍你做一些常驻进程的事情。打算做开源的客服系统的项目。起始于17年年初，花了一个月的下班晚上时间写的项目，后续在个人精力允许下持续改进。感谢workerman,swoole,reactphp这些优秀的PHP项目<br/>
这个项目做了这些工作<br/>
     在编写项目中学习到了以下知识<br/>
         1:多进程编程及进程间IPC通信<br/>
           基于多进程服务模式：leader-follower做一些常驻进程的服务，MQ客户端，生产及消费者【做大批量数据迁移的特别有用】<br/>
         2:了解了websocket协议等<br/>
            写了一个简单的websocket协议处理类，及简单的文本协议处理，在此项目中 协议类统一在customer/Lib/Protocol 目录下<br/>
            写一个基于websocket协议在线聊天demo,由于是多进程监听端口，在不同进程间的需要通信解决方案是放在redis的list里当作集合使用，有主进程消费队列，来分发到子进程，实现不同进程间的用户消息互通。在同一个进程间的直接发送消息到客户端【个人就一台阿里低配ECS】。构建高并发的消息服务：前端有个网关层用于维护客户端链接， 有一个注册中心，一个处理业务逻辑的集群。业务消息基于json结构：用户数据定义：<br/>
                  uid为redis键值对key<br/>
                  包含信息<br/>
                  name姓名<br/>
                  status 状态<br/>
                 pid 所在进程ID<br/>
                 IP 所在服务器IP<br/>
         3:了解解决了TCP 流的粘包问题测试了websocket,基于换行符的文本消息协议 <br/>

      项目目录：<br/>
        application 主要是项目代码及业务逻辑代码.基于mvc机制
        customer/Lib 基础类库 <br/>
        Queue：队列类，提供了基于msg扩展的一个队列可以在同一服务器间夸进程传递消息，和基于redis的list的一个队列 <br/>
        Db：数据库类 <br/>
        Connect:Tcp,Udp处理链接类 <br/>
        Events：libevent,event,select的事件库 <br/>
        Protocol：消息协议类，websocket,text <br/>
        index.php 入口类 <br/>
