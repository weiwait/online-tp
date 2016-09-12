<?php
use base\ServiceFactory;
use utils\Result;

require_once "MCommonController.php";

class AppController extends MCommonController
{
    /**
     * 初始化
     */
    public function init()
    {
        //check_admin();
        parent::init();
    }

    public function regAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $existFlag = ServiceFactory::getService("App")->isExist($appid);
        if ($existFlag) {
            Result::showOk("appid " . $appid . " have reg");
        }

        $ret = ServiceFactory::getService("App")->reg($appid);

        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function bindAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if ($flag) {
            //Result::showError("appid ".$appid." have bind machineid ".$machineid."");
            //fei 2014-10-04 允许重复绑定
            Result::showOk("appid " . $appid . " have bind machineid " . $machineid . "");
        } else {
            $ret = ServiceFactory::getService("App")->bind($tpAppid, $tpMachineid);
            if ($ret) {
                Result::showOk("ok");
            } else {
                Result::showError("system error");
            }
        }
    }

    public function unbindAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        } else {
            $ret = ServiceFactory::getService("App")->unBind($tpAppid, $tpMachineid);
            if ($ret) {
                Result::showOk("ok");
            } else {
                Result::showError("system error");
            }
        }
    }

    public function getmachinelistAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $page = $_REQUEST['page'];
        $pagesize = $_REQUEST['pagesize'];
        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);
        $phoneType = ServiceFactory::getService("App")->getPhoneType($appid);

        $total = ServiceFactory::getService("App")->getMachineNum($tpAppid);
        if ($total <= 0) {
            $ret = array(
                "status" => 1,
                "total" => 0,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => array(),
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }
        $allPage = ceil($total / $pagesize);
        if ($page > $allPage) {
            $page = $allPage;
        }
        $offset = ($page - 1) * $pagesize;
        $limit = $pagesize;

        $data = ServiceFactory::getService("App")->getMachineList($tpAppid, $offset, $limit, $phoneType);
        if (empty($data)) {
            Result::showError("system error");
        } else {
            $ret = array(
                "status" => 1,
                "total" => $total,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => $data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
        }
    }

    public function getmachinenumAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }
        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("App")->getMachineNum($tpAppid);
        Result::showOk($total);
    }

    public function deletemachineAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        }

        $ret = ServiceFactory::getService("App")->deleteMachine($tpAppid, $tpMachineid);
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function checkversionAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $version = $_REQUEST['version'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($version)) {
            Result::showError("version is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $data = ServiceFactory::getService("App")->getNewVersion($tpAppid, $version);
        $ret = array(
            "status" => 1,
            "data" => $data,
        );
        $ret = json_encode($ret);
        Result::output($ret);
    }

    public function feedbackAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $content = $_REQUEST['content'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($content)) {
            Result::showError("content is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }
        ServiceFactory::getService("App")->active($tpAppid);

        //$content = htmlentities($content);
        $content = str_replace(array("<", ">"), array("&lt;", "&gt;"), $content);

        $ret = ServiceFactory::getService("App")->feedback($tpAppid, $content);
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function requestAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        $page = $_REQUEST['page'];
        $pagesize = $_REQUEST['pagesize'];
        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have bind machineid " . $machineid . "");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("TeapotOrder")->getOrderNum($tpMachineid);
        if ($total <= 0) {
            $ret = array(
                "status" => 1,
                "total" => 0,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => array(),
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }
        $allPage = ceil($total / $pagesize);
        if ($page > $allPage) {
            $page = $allPage;
        }
        $offset = ($page - 1) * $pagesize;
        $limit = $pagesize;

        $data = ServiceFactory::getService("TeapotOrder")->getOrderList($tpMachineid, "", $offset, $limit);
        if ($data) {
            $ret = array(
                "status" => 1,
                "total" => $total,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => $data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        } else {
            Result::showError("system error");
        }
    }

    public function clearallrequestAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $ret = ServiceFactory::getService("TeapotOrder")->clearAllRequest($tpMachineid);
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public static $TESTPUSH = true;

    public static function yaojunLog($appid, $str, $type)
    {
        if (AppController::$TESTPUSH && $appid == 'C1E9B944-EB75-4C89-8FF2-B6F814A76C3E') {
            if ($type != 4) {
                $str = "------other-----" . $str;
            }
            file_put_contents("yaojun.log", date("Y-m-d H:i:s") . $str . "\n", FILE_APPEND);
            return;
        }
        AppController::liuLog($appid, $str, $type);
        AppController::wangLog($appid, $str, $type);
    }

    public static function liuLog($appid, $str, $type)
    {
        if (AppController::$TESTPUSH && $appid == '88538244-638A-49ED-BDB3-D5128330F40E') {
            if ($type != 4) {
                $str = "------other-----" . $str;
            }
            file_put_contents("liu.log", date("Y-m-d H:i:s") . $str . "\n", FILE_APPEND);
        }
    }

    public static function wangLog($appid, $str, $type)
    {
        if (AppController::$TESTPUSH && $appid == '1460347011477710') {
            if ($type != 4) {
                $str = "------other-----" . $str;
            }
            file_put_contents("wang.log", date("Y-m-d H:i:s") . $str . "\n", FILE_APPEND);
        }
    }

    public function updatelocationAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $this->getParam('appid');
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }
        $phoneType = ServiceFactory::getService("App")->getPhoneType($appid);
        ServiceFactory::getService("App")->active($tpAppid, true);

        $longitude = $this->getParam('longitude'); //经度
        $latitude = $this->getParam('latitude'); //经度
        //1=表示APP正常在线上行地理位置
        //2=表示地理围栏进入触发
        //3=表示地理围栏出去触发
        //4=表示服务器远程推送触发
        $type = intval($this->getParam('type'));
        //距离
        $distance = intval($this->getParam('distance'));
        $ip_temp = $this->getParam('ip');
        $time_temp = $this->getParam('time');
        $app_ip = $ip_temp ? $ip_temp : trim($_SERVER['REMOTE_ADDR']);
        $time = $time_temp ? $time_temp : time();
        AppController::yaojunLog($appid, "{{{ longitude=" . $longitude . ", latitude=" . $latitude . ", appid=" . $appid . ", time=" . $time . ", type=" . $type . ", distance=" . $distance . ", ip=" . $app_ip, $type);
        //        if ($appid == '88538244-638A-49ED-BDB3-D5128330F40E') {
        //            file_put_contents("88538244-638A-49ED-BDB3-D5128330F40E.log", date("Y-m-d H:i:s") . " " . $appid . " longitude=" . $longitude . ", latitude=" . $latitude . ", type=" . $type . ", distance=" . $distance . ", ip=" . $app_ip . "\n", FILE_APPEND);
        //        }
        //        if ($appid == '4B15A95F-EAAE-4F21-AAE4-D53E92657E5A') {
        //            file_put_contents("4B15A95F-EAAE-4F21-AAE4-D53E92657E5A.log", date("Y-m-d H:i:s") . " " . $appid . " longitude=" . $longitude . ", latitude=" . $latitude . ", type=" . $type . ", distance=" . $distance . ", ip=" . $app_ip . "\n", FILE_APPEND);
        //        }
        $ret = ServiceFactory::getService("App")->updateLocation($tpAppid, $longitude, $latitude, $type, $distance);
        if ($ret) {
            $tpMachineidArray = ServiceFactory::getService("Attendance")->getBindMachineidList($tpAppid);
            if (!empty($tpMachineidArray)) {
                foreach ($tpMachineidArray as $key => $item) {
                    $tpMachineid = $item['tp_machineid'];
                    //判断是否为考勤机
                    $machineid = ServiceFactory::getService("Attendance")->getMachineid($tpMachineid);
                    if (substr($machineid, 0, 2) != '08') {
                        continue;
                    }
                    //获考勤机的详细信息
                    $machineDetail = ServiceFactory::getService("Machine")->getDetail($tpMachineid);
                    //计算距离
                    if ($phoneType == 1) { //Android
                        //安卓地理位置
                        $machineLongitudeAndroid = floatval($machineDetail['longitude_android']);
                        $machineLatitudeAndroid = floatval($machineDetail['latitude_android']);
                        $distance = $this->getDistance($machineLongitudeAndroid, $machineLatitudeAndroid, $longitude, $latitude);
                    }
                    if ($phoneType == 2 && $distance == 0) { //IOS
                        //IOS地理位置
                        $machineLongitude = floatval($machineDetail['longitude']);
                        $machineLatitude = floatval($machineDetail['latitude']);
                        $distance = $this->getDistance($machineLongitude, $machineLatitude, $longitude, $latitude);
                    }
                    $isIn = $app_ip == $machineDetail['last_active_ip'] || $distance <= 50;
                    //存储数据到临时表
                    //ServiceFactory::getService("Attendance")->insertPunchAttendance($tpAppid, $tpMachineid, $app_ip, $longitude, $latitude, $type, $distance);
                    //获取最新该考勤机上一个考勤记录
                    $data = ServiceFactory::getService("Attendance")->getPunchLast($tpAppid, $tpMachineid);
                    $punch_createtime = $data ? $data[0]['createtime'] : null;
                    //是初次或者不是同一天
                    if (empty($data) || date('Ymd', $punch_createtime) != date('Ymd', $time)) {
                        if ($isIn) {
                            ServiceFactory::getService("Attendance")->insertPunch($tpAppid, $tpMachineid, $app_ip, $longitude, $latitude, $type, $distance, $time);
                            AppController::yaojunLog($appid, ">>>in log", $type);
                        }
                        continue;
                    } else if ($time - $punch_createtime > 300) {
                        $punch_last_active_type = $data[0]['last_active_type'];
                        if ($isIn && $punch_last_active_type != 0) {
                            ServiceFactory::getService("Attendance")->insertPunch($tpAppid, $tpMachineid, $app_ip, $longitude, $latitude, $type, $distance, $time);
                            AppController::yaojunLog($appid, ">>>other in log", $type);
                        } else if (!$isIn && $punch_last_active_type == 0) {
                            $punch_id = $data[0]['id'];
                            ServiceFactory::getService("Attendance")->updatePunch($tpAppid, $punch_id, $app_ip, $type, $distance, $time);
                            AppController::yaojunLog($appid, "<<<out log", $type);
                        }
                    }
                }
            } else {
                Result::showError("请先绑定考勤机");
            }
            AppController::yaojunLog($appid, "}}}", $type);
            Result::showOk("ok");
        } else {
            AppController::yaojunLog($appid, "}}}error..." . "tpAppid:" . $tpAppid . "-longitude:" . $longitude . "-latitude:" . $latitude . "-type:" . $type . "-distance:" . $distance, $type);
            Result::showError("system error");
        }
    }

    /**
     * 计算两点地理坐标之间的距离
     * @param  Decimal $longitude1 起点经度
     * @param  Decimal $latitude1 起点纬度
     * @param  Decimal $longitude2 终点经度
     * @param  Decimal $latitude2 终点纬度
     * @param  Int $unit 单位 1:米 2:公里
     * @param  Int $decimal 精度 保留小数位数
     * @return Decimal
     */
    function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 1, $decimal = 2)
    {

        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI = 3.1415926;

        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;

        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI / 180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if ($unit == 2) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);

    }

    function timediff($begin_time, $end_time)
    {
        if ($begin_time < $end_time) {
            $starttime = $begin_time;
            $endtime = $end_time;
        } else {
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        $timediff = $endtime - $starttime;
        $days = intval($timediff / 86400);
        $remain = $timediff % 86400;
        $hours = intval($remain / 3600);
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        $secs = $remain % 60;
        $res = array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);
        return $res;
    }

    public function feedbackdetailAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $page = $_REQUEST['page'];
        $pagesize = $_REQUEST['pagesize'];

        $page = intval($page);
        $pagesize = intval($pagesize);
        if ($page < 1) {
            $page = 1;
        }
        if ($pagesize < 1) {
            $pagesize = 10;
        }

        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("Feedback")->getCountByTpappid($tpAppid);
        if (0 == $total) {
            $ret = array(
                "status" => 1,
                "total" => 0,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => array(),
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }

        $allPage = ceil($total / $pagesize);
        $offset = ($page - 1) * $pagesize;
        $limit = $pagesize;
        $data = ServiceFactory::getService("Feedback")->getDetailList($tpAppid, $offset, $limit);
        if (false === $data) {
            Result::showError("system error");
        } else {
            $ret = array(
                "status" => 1,
                "total" => $total,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => $data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
        }
    }

    public function getunreadmsgnumAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("AppMsg")->getCount($tpAppid, true);
        $total = intval($total);

        Result::showOk($total);
    }

    public function getmsgnumAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("AppMsg")->getCount($tpAppid);
        $total = intval($total);

        Result::showOk($total);
    }

    public function addmsgAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        $content = $_REQUEST['content'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have bind machineid " . $machineid . "");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $ret = ServiceFactory::getService("AppMsg")->addMsg($tpAppid, $appid, $tpMachineid, $machineid, $content);
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function getunreadmsglistAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $page = $_REQUEST['page'];
        $pagesize = $_REQUEST['pagesize'];
        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("AppMsg")->getCount($tpAppid, true);
        if ($total <= 0) {
            $ret = array(
                "status" => 1,
                "total" => 0,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => array(),
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }
        $allPage = ceil($total / $pagesize);
        if ($page > $allPage) {
            $page = $allPage;
        }
        $offset = ($page - 1) * $pagesize;
        $limit = $pagesize;

        $data = ServiceFactory::getService("AppMsg")->getList($tpAppid, $offset, $limit, true);
        if ($data) {
            $ret = array(
                "status" => 1,
                "total" => $total,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => $data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        } else {
            Result::showError("system error");
        }
    }

    public function getmsglistAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $page = $_REQUEST['page'];
        $pagesize = $_REQUEST['pagesize'];
        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("AppMsg")->getCount($tpAppid);
        if ($total <= 0) {
            $ret = array(
                "status" => 1,
                "total" => 0,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => array(),
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }
        $allPage = ceil($total / $pagesize);
        if ($page > $allPage) {
            $page = $allPage;
        }
        $offset = ($page - 1) * $pagesize;
        $limit = $pagesize;

        $data = ServiceFactory::getService("AppMsg")->getList($tpAppid, $offset, $limit);
        if ($data) {
            $ret = array(
                "status" => 1,
                "total" => $total,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => $data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        } else {
            Result::showError("system error");
        }

    }

    public function updatemsgstatusAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $id = $_REQUEST['id'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($id)) {
            Result::showError("id is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $idArray = array();
        $tmpArray = explode(",", $id);
        foreach ($tmpArray as $item) {
            $item = intval($item);
            if (empty($item)) {
                Result::showError("bad id " . $id . "");
            }

            $idArray[] = $item;
        }

        $ret = ServiceFactory::getService("AppMsg")->updateMsgStatus($tpAppid, $idArray);
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function deletemsgAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $id = $_REQUEST['id'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($id)) {
            Result::showError("id is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $idArray = array();
        $tmpArray = explode(",", $id);
        foreach ($tmpArray as $item) {
            $item = intval($item);
            if (empty($item)) {
                Result::showError("bad id " . $id . "");
            }

            $idArray[] = $item;
        }

        $ret = ServiceFactory::getService("AppMsg")->deleteMsg($tpAppid, $idArray);
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function getnearmachineAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $onlineFlag = $_REQUEST['onlineflag'];
        $bindFlag = $_REQUEST['bindflag'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        //fei 2015-04-12 现在只显示在线的
        $onlineFlag = "online";

        if (!in_array($onlineFlag, array("online", "offline", "all"))) {
            Result::showError("bad onlineflag " . $onlineFlag . "");
        }
        if (!in_array($bindFlag, array("bind", "unbind", "all"))) {
            Result::showError("bad bindflag " . $bindFlag . "");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);


        $data = ServiceFactory::getService("App")->getNearMachine($tpAppid, $onlineFlag, $bindFlag);
        $total = count($data);
        $ret = array(
            "status" => "1",
            "total" => trim($total),
            "onlineFlag" => $onlineFlag,
            "bindFlag" => $bindFlag,
            "data" => $data,
        );
        $ret = json_encode($ret);
        Result::output($ret);
    }

    public function appidAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        //echo "D424CE91-BBC7-4C92-BFAF-DB2062D8F093"; 
        //udid=D424CE91-BBC7-4C92-BFAF-DB2062D8F093

        $version = $_REQUEST['version'];
        //if("1.14" == $version)
        if (0) {
            $arr = array(
                "udid" => "D424CE91-BBC7-4C92-BFAF-DB2062D8F093",
            );
        } else {
            $arr = array(
                "udid" => "",
            );
        }
        echo json_encode($arr);
    }

    public function opengpsAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $flag = ServiceFactory::getService("App")->checkOpenGps($tpAppid);
        if ($flag) {
            $data = "open";
        } else {
            $data = "close";
        }

        $ret = array(
            "status" => "1",
            "data" => $data, //open或者是close
        );
        $ret = json_encode($ret);
        Result::output($ret);
    }

    /**
     * 获取控制电器的参数值
     * @return mixed
     */
    function getControlData()
    {
        return null;
    }
}