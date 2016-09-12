<?php
use Workerman\Worker;

require_once './Autoloader.php';
// 初始化一个worker容器，监听1234端口
$worker = new Worker('tcp://0.0.0.0:1234');
// 进程数设置为1
$worker->count = 1;
// 新增加一个属性，用来保存uid到connection的映射
$worker->uidConnections = array();
//将打印信息保存到文件
Worker::$stdoutFile = '/tmp/worker.log';
// 当有客户端发来消息时执行的回调函数
$worker->onMessage = function ($connection, $data) use ($worker) {
    echo date("Y-m-d H:i:s", time()) . " $data\r\n";
    $mydata = json_decode($data, true);
    // 判断当前客户端是否已经验证,既是否设置了uid
    if (!empty($mydata['url'])) {
        $url = "http://127.0.0.1:8081/" . $mydata['url'];
        $ch = curl_init();
        $mydata['data']['ip'] = $connection->getRemoteIp();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $mydata['data']);
        if ($mydata['url'] == '/machine/renewlist' && !empty($mydata['data']['machineid'])) {
            curl_exec($ch);
            curl_close($ch);
            // 没验证的话把第一个包当做uid（这里为了方便演示，没做真正的验证）
            $machineid = explode(',', $mydata['data']['machineid']);
            if (isset($connection->uid)) {
                $old_machineid = explode(',', $connection->uid);
                $del_machineid = array_diff($old_machineid, $machineid);
                //剔除失效id
                foreach ($del_machineid as $del) {
                    unset($worker->uidConnections[$del]);
                }
            }
            //记录id绑定链接
            foreach ($machineid as $value) {
                $worker->uidConnections[$value] = $connection;
            }
            $connection->uid = $mydata['data']['machineid'];

            /* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
             * 实现针对特定uid推送数据
             */
            // $worker->uidConnections[$connection->uid] = $connection;
            return $connection->send('login success, your uid is ' . $connection->uid);
        }

        $return = curl_exec($ch);
        curl_close($ch);
        $arr = json_decode($return, true);
        $arr1['url'] = $mydata['url'];
        $arr = array_merge($arr1, $arr);
        $return = json_encode($arr);
        $return = str_replace("\\", '', $return);
        $in = "HTTP/1.1 200 OK\r\n";
        $in .= "Date:" . gmdate("D, d M Y H:i:s T", time()) . "\r\n";
        $in .= "Content-Length:" . strlen($return) . "\r\n\r\n";
        $in .= $return;
        return $connection->send($in);
    }

    // 其它罗辑，针对某个uid发送 或者 全局广播
    // 假设消息格式为 uid:message 时是对 uid 发送 message
    // uid 为 all 时是全局广播
    @list($recv_uid, $message) = explode('::', $data);
    // 全局广播
    if ($recv_uid == 'all') {
        broadcast($message);
    } // 给特定uid发送
    else {
        /*$recv_uids = explode(",", $recv_uid);
        if (count($recv_uids) > 1) {
            $recv_uid = $recv_uids[0];
        }*/
        sendMessageByUid($recv_uid, $message);
        return $connection->send('ok');
    }
};

// 当有客户端连接断开时
$worker->onClose = function ($connection) use ($worker) {
    global $worker;
    if (isset($connection->uid)) {
        // 连接断开时删除映射
        $machineid = explode(',', $connection->uid);
        foreach ($machineid as $value) {
            unset($worker->uidConnections[$value]);
        }
        // unset($worker->uidConnections[$connection->uid]);
    }
};

// 向所有验证的用户推送数据
function broadcast($message)
{
    global $worker;
    foreach ($worker->uidConnections as $connection) {
        $connection->send($message);
    }
}

// 针对uid推送数据
function sendMessageByUid($uid, $message)
{
    global $worker;
    if (isset($worker->uidConnections[$uid])) {
        $connection = $worker->uidConnections[$uid];
        $connection->send($message);
    }
}

// 运行所有的worker（其实当前只定义了一个）
Worker::runAll();