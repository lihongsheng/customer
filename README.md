# customer

php网络框架：
   测试用例在 application下的chat里
   
   先正在做多进程间监听socekt的测试用例书写
   方案说明再下：
  测试多进程监听 端口
  及测试 多进程间的用户传递消息的
     多进程间传递消息
     项目方案：
 
         主进程创建一个用于进程间通信的内部socket,子进程链接父进程的socket，及监听对外的socket，
             （可以依据socket于建立分布式，或者依据REDIS建立消息的分布式转发）
         父进程，轮询REDIS队列，有本服务器消息处理的时候，查询用户所在进程，进行消息投递，并有子进程把消息投递到客户端（分布式的可以有子进程发送不在此服务器的消息到父进程，由父进程发送消息到中心服务器去调度到别的服务器进行，消息间的夸服务器传递）
            主进程负责监控子进程，及其他事项，所以主进程的socket依据于select,子进程的socket依据于event
         为方便测试依据 依据于redis建立此项目
         数据定义：
            用户数据定义：
               uid为redis键值对key
                  包含信息
                  name姓名
                  status 状态
                  channel 所在频道 IP-进程ID
             用户消息队列数据定义：（可依据服务器IP建立多个消息队列，服务器依据IP读取对应的队列的数据）
                 JSON格式：
                    {uid:用户UID,msg:消息内容}