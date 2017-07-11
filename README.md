# customer

php 进程及网络框架：
   这是个个人兴趣项目,而且现在的代码还是很粗糙，但是不妨碍你做一些常驻进程的事情。这本来是想做成一个开源的客服系统的项目，无奈搁置两年。与今年年初，花了一个月的下班晚上时间写了一个测试的程序，然后后续改进。感谢workerman,swoole,reactphp这些优秀的PHP项目
       这个项目做了这些工作
           多进程管理：你可以做依据leader-follower模型，来做一些工作，比如生产者（主进程）---消费者（子进程），比如一些数据量大且耗时的操作，比如做一些应用的客服端（MQ的消费者）在chat 里的Multi 提供了这个例子,多进程管理类在customer/Lib/MutliProcess。
           提供了websocket,及文本协议（依据 行 作为结束符）。你可以做依据于websocket的一些服务，当然也可以依据文本协议做进程间的通信。本项目例子提供了一个leader-follower模型，子进程基于event模式，主进程基于select（用于主进程与子进程间通信，解决了客户端链接不在同一个子进程间的，依据父进程传递消息）的websocket的IM例子（及其简陋）
           解决了TCP 流的粘包问题（没经过复杂网络环境，只是解决了websocket，及文本协议的粘包问题，应为只提供了这两个协议）
           现在只是提供 基于命令行的stop 比如 php index.php /chat/multi/work stop 停止这个服务
   基于MVC模式。
   项目文件结构
        application 主要是项目代码所在如同mvc
             application下的bootstrap.php 启动初始化的一些工作
        cache 缓存数据目录，这个里面现在只有一个 multiWork.php 用来记录主子进程ID
        customer/Lib 基础类库
              Queue队列类，提供了基于msg扩展的一个队列可以在同一服务器间夸进程传递消息，和基于redis的list的一个队列
              Db
              Connect
              Events
              Protocol 协议类
        index.php 入口类
       
   
   基于leader-follower模型和websocket协议的测试IM实例说明
      多进程监听 端口
      及测试 多进程间的用户传递消息的
     多进程间传递消息项目方案：
 
         主进程创建一个用于进程间通信的内部socket,子进程链接父进程的socket，及监听对外的socket，
             （可以依据socket于建立分布式，或者依据REDIS建立消息的分布式转发）
         父进程，轮询REDIS队列，有本服务器消息处理的时候，查询用户所在进程，进行消息投递，
         并有子进程把消息投递到客户端（分布式的可以有子进程发送不在此服务器的消息到父进程，
         由父进程发送消息到中心服务器去调度到别的服务器进行，消息间的夸服务器传递）
            主进程负责监控子进程，及其他事项，所以主进程的socket依据于select,子进程的socket依据于event
         为方便测试依据 依据于redis建立此项目
         数据定义：
            用户数据定义：
               uid为redis键值对key
                  包含信息
                  name姓名
                  status 状态
                  pid 所在进程ID
                  IP 所在服务器IP
             用户消息队列数据定义：（可依据服务器IP建立多个消息队列，服务器依据IP读取对应的队列的数据）
                 JSON格式：
                    {uid:用户UID,msg:消息内容}
