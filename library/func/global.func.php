<?php
use base\ServiceFactory;

define("G_UCT_TIME", time() - date('Z'));

function getLightTag()
{
    return '03';
}

function getRgbTag()
{
    return '05';
}

function getMosquitokillerTag()
{
    return '06';
}

function getLightService()
{
    return ServiceFactory::getService('Light');
}

function getMosquitokillerService()
{
    return ServiceFactory::getService('Mosquitokiller');
}

function operation($op)
{
    $op = intval($op);
    switch ($op) {
        case 0:
            return "手动";
            break;
        case 1:
            return "App";
            break;
        case 2:
            return "预约";
            break;
        case 3:
            return "智能";
            break;
        default:
            return "App";
            break;
    }
}

function teapotState($state)
{
    $state = intval($state);
    switch ($state) {
        case "0":
            return "空闲";
            break;
        case "1":
            return "加热";
            break;
        case "2":
            return "净化";
            break;
        case "3":
            return "保温";
            break;
        case "4":
            return "冷却中";
            break;
        default:
            return "空闲";
            break;
    }
}

function humidifierState($state)
{
    $state = intval($state);
    switch ($state) {
        case "0":
            return "空闲";
            break;
        case "1":
            return "加湿中";
            break;
        default:
            return "空闲";
            break;
    }
}

function lightState($state)
{
    $state = intval($state);
    switch ($state) {
        case "0":
            return "空闲";
            break;
        case "1":
            return "工作中";
            break;
        default:
            return "空闲";
            break;
    }
}

function mosquitokillerState($state)
{
    $state = intval($state);
    switch ($state) {
        case "0":
            return "空闲";
            break;
        case "1":
            return "工作中";
            break;
        default:
            return "空闲";
            break;
    }
}

function ip($ip)
{
    $ip = trim($ip);
    if (empty($ip)) {
        return "";
    } else if ("127.0.0.1" == $ip) {
        return $ip . " 本地";
    } else if ("192.168" == substr($ip, 0, 7)) {
        return $ip . " 内网";
    } else {
        $QQWry = new \utils\QQWry;
        $ifErr = $QQWry->QQWry($ip);
        $str = $QQWry->Country . $QQWry->Local;
        $str = mb_convert_encoding($str, "UTF-8", "GBK");
        return $ip . " " . $str;
    }
}

function machineType($machineid)
{
    $len = strlen($machineid);
    if (2 != $len) {
        $machineid = substr($machineid, 0, 2);
    }
    switch ($machineid) {
        case "01":
            return "茶壶";
            break;
        case "02":
            return "加湿器";
            break;
        case "03":
            return "冷暖灯";
            break;
        case "04":
            return "路由器";
            break;
        case "05":
            return "RGB灯";
            break;
        case "06":
            return "灭蚊器";
            break;
        case "08":
            return "考勤机";
            break;
        case "09":
            return "寻物器";
            break;
        case "10":
            return "标签";
            break;
        case "13":
            return "开关";
            break;
        default:
            return "其他";
            break;
    }
}

function secondToHHMMSS($second)
{
    $second = intval($second);
    $h = intval($second / 3600);

    $second -= $h * 3600;
    $m = intval($second / 60);

    $second -= $m * 60;

    $h = substr("00" . $h, -2);
    $m = substr("00" . $m, -2);
    $s = substr("00" . $second, -2);
    return $h . ":" . $m . ":" . $s;

}

function formatNum($number)
{
    return number_format($number, 6, '.', '');
}

function saveSql($sql)
{
    //file_put_contents("/tmp/sql.log", date("Y-m-d H:i:s") . " " . $sql . "\n", FILE_APPEND);
}

function saveWork($tpMachineid, $reason)
{
    $msg = "tpMachineid=" . $tpMachineid . ", reason=" . $reason . "";
    file_put_contents("/tmp/work.log", date("Y-m-d H:i:s") . " " . $msg . "\n", FILE_APPEND);
}

/**
 * @desc SOCKET连接
 */
function machineControlDo($call)
{
    $machineid = $call['machineid'];
    $call = json_encode($call);
    $call = str_replace('\\', '', $call);
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_connect($socket, '127.0.0.1', 1234);
    $in = $machineid . "::";
    $in .= "HTTP/1.1 200 OK\r\n";
    $in .= "Date:" . gmdate("D, d M Y H:i:s T", time()) . "\r\n";
    $in .= "Content-Length:" . strlen($call) . "\r\n\r\n";
    $in .= $call;
    socket_write($socket, $in, strlen($in));
    socket_close($socket);
}

/**
 * 为了避免传送过多值给电器,简化的data的key
 * @desc 传送data
 * @param $data
 * @param $socketData
 */
function machineControl($socketData, $machineid, $subControl = null)
{
    $machineids = explode(",", $machineid);
    if ($machineids . count() > 1) {
        $machineid = $machineids[0];
    }
    $data[$machineid] = $socketData;
    $call = array(
        "url" => '/machine/' . ($subControl ? $subControl : 'control'),
        "status" => "1",
        "machineid" => $machineid,
        "data" => $data
    );
    machineControlDo($call);
}

/**
 * @desc 开启机器
 * @param $data
 * @param $socketData
 */
function startMachine($socketData, $machineid)
{
    if (!$socketData) {
        $socketData = array(
            "run" => "1"
        );
    } else {
        $socketData['run'] = '1';
    }
    machineControl($socketData, $machineid);
}

/**
 * @desc 关闭机器
 * @param $data
 * @param $socketData
 */
function stopMachine($socketData, $machineid)
{
    if (!$socketData) {
        $socketData = array(
            "run" => "0"
        );
    } else {
        $socketData['run'] = '0';
    }
    machineControl($socketData, $machineid);
}
