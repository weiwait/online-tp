<?php
use base\ServiceFactory;
use utils\Common;
use constants\ErrConst;

class AttendanceController extends ControlController
{
    /**
     * 初始化
     */
    public function init()
    {
        parent::init();
    }

    //检查登录状态
    private function checkLogin()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        if (empty($_SESSION['userid'])) {
            echo json_encode("请登录");
            die();
        }

        return $_SESSION['id'];
    }

    //检查appid
    private function checkData()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $appid = trim($_REQUEST['appid']);
        if (empty($appid)) {
            $ret["data"] = "appid is empty";
            echo json_encode($ret);
            die();
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            echo json_encode("appid " . $appid . " have not reg");
            die();
        }
        ServiceFactory::getService("App")->active($tpAppid, true);

        return $tpAppid;
    }

    public function indexAction()
    {

    }

    //用户注册
    public function regAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $username = trim($_REQUEST['username']);
        if (empty($username)) {
            $ret['data'] = '用户名不能为空';
            echo json_encode($ret);
            die();
        }
        $phonenumber = trim($_REQUEST['phonenumber']);
        if (empty($phonenumber)) {
            $ret['data'] = '手机号不能为空';
            echo json_encode($ret);
            die();
        }
        $email = trim($_REQUEST['email']);
        if (empty($email)) {
            $ret['data'] = '邮箱不能为空';
            echo json_encode($ret);
            die();
        }
        $password = trim($_REQUEST['password']);
        if (empty($password)) {
            $ret['data'] = '密码不能为空';
            echo json_encode($ret);
            die();
        }

        $data = ServiceFactory::getService("Attendance")->reg($tpAppid, $username, $phonenumber, $email, $password);

        if ($data) {
            $ret = array(
                "status" => 1,
                "data" => $data
            );
        } else {
            $ret['data'] = '注册失败';
        }

        echo json_encode($ret);
    }

    //登录
    public function loginAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $phonenumber = trim($_REQUEST['phonenumber']);
        if (empty($phonenumber)) {
            $ret["data"] = "手机号不能为空";
            echo json_encode($ret);
            die();
        }

        $data = ServiceFactory::getService("Attendance")->getApp($tpAppid, $phonenumber);
        if ($data) {
            $_SESSION['parent_id'] = $data[0]['parent_id'];
            $_SESSION['user_id'] = $data[0]['id'];
            $_SESSION['user_appId'] = $data[0]['appid'];
            $ret = array(
                "status" => 1,
                "data" => $data
            );

        } else {
            $ret["data"] = "登录失败";
        }

        echo json_encode($ret);
    }

    //退出登录
    public function logoutAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 1,
            "data" => "ok"
        );

        $_SESSION['id'] = "";
        $_SESSION['username'] = "";
        session_destroy();

        echo json_encode($ret);
    }

    //修改密码
    public function modifypasswordAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $phonenumber = trim($_REQUEST['phonenumber']);
        if (empty($phonenumber)) {
            $ret["data"] = "手机号不能为空";
            echo json_encode($ret);
            die();
        }

        $password1 = trim($_REQUEST['password1']);
        $password2 = trim($_REQUEST['password2']);
        if (empty($password1) || empty($password2)) {
            $ret["data"] = "密码和确认密码不能为空";
            echo json_encode($ret);
            die();
        }
        if ($password1 != $password2) {
            $ret["data"] = "密码和确认密码不一致";
            echo json_encode($ret);
            die();
        }
        $password = $password1;

        $data = ServiceFactory::getService("Attendance")->modifypassword($tpAppid, $phonenumber, $password);
        if ($data) {
            $ret = array(
                "status" => 1,
                "data" => "ok"
            );
        } else {
            $ret["data"] = "修改密码失败";
        }

        echo json_encode($ret);
    }

    //APP定时更新地理位置
    public function updatelocationAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $tpAppid = $this->checkData();

        //获取数据
        $appid = trim($_REQUEST['appid']);
        $longitude = trim($_REQUEST['longitude']);
        $latitude = trim($_REQUEST['latitude']);
        $type = trim($_REQUEST['type']);
        $distance = trim($_REQUEST['distance']);
        $ip = trim($_SERVER['REMOTE_ADDR']);

        file_put_contents("attendance_location.log", date("Y-m-d H:i:s") . " " . $appid . " longitude=" . $longitude . ", latitude=" . $latitude . ", type=" . $type . ", distance=" . $distance . ", ip=" . $ip . "\n", FILE_APPEND);
    }

    //考勤机测试程序
    public function attendancetestAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        //echo '<pre>';

        //1、获取数据
        $appid = $_REQUEST['appid'];
        $longitude = $_REQUEST['longitude']; //经度
        $latitude = $_REQUEST['latitude']; //经度
        //1=表示APP正常在线上行地理位置
        //2=表示地理围栏进入触发
        //3=表示地理围栏出去触发
        //4=表示服务器远程推送触发
        $type = intval($_REQUEST['type']);
        $distance = intval($_REQUEST['distance']); //距离
        //$app_ip = trim($_SERVER['REMOTE_ADDR']);
        $app_ip = trim($_REQUEST['ip']);
        $date = trim($_REQUEST['date']);
        $time = strtotime($date);
        //print_r($_REQUEST);
        //2、记录日志
        file_put_contents("attendancetest.log", date("Y-m-d H:i:s") . " " . $appid . " longitude=" . $longitude . ", latitude=" . $latitude . ", type=" . $type . ", distance=" . $distance . ", ip=" . $app_ip . "\n", FILE_APPEND);

        //3、数据过滤
        if (empty($appid)) {
            $ret = array(
                "status" => 0,
                "data" => "appid is empty"
            );
            die(json_encode($ret));
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            $ret = array(
                "status" => 0,
                "data" => "appid " . $appid . " have not reg"
            );
            die(json_encode($ret));;
        }
        $phoneType = ServiceFactory::getService("App")->getPhoneType($appid);

        //4、获取绑定的所有机器
        $tpMachineidArray = ServiceFactory::getService("Attendance")->getBindMachineidList($tpAppid);//print_r($tpMachineidArray);
        if (empty($tpMachineidArray)) {
            $ret = array(
                "status" => 0,
                "data" => "请先绑定考勤机"
            );
            die(json_encode($ret));
        }

        //5、循环
        foreach ($tpMachineidArray as $key => $item) {
            $tpMachineid = $item['tp_machineid'];

            //判断是否为考勤机
            $machineid = ServiceFactory::getService("Attendance")->getMachineid($tpMachineid);
            if (substr($machineid, 0, 2) != '08') {
                continue;
            }

            //获考勤机的详细信息
            $machineDetail = ServiceFactory::getService("Machine")->getDetail($tpMachineid);//print_r($machineDetail);

            $machine_ip = $machineDetail['last_active_ip'];

            //IOS地理位置
            $machineLongitude = floatval($machineDetail['longitude']);
            $machineLatitude = floatval($machineDetail['latitude']);

            //安卓地理位置
            $machineLongitudeAndroid = floatval($machineDetail['longitude_android']);
            $machineLatitudeAndroid = floatval($machineDetail['latitude_android']);

            //计算距离
            if ($phoneType == 1) { //Android
                $distance = $this->getDistance($machineLongitudeAndroid, $machineLatitudeAndroid, $longitude, $latitude);
            }
            if ($phoneType == 2 && $distance == 0) { //IOS
                $distance = $this->getDistance($machineLongitude, $machineLatitude, $longitude, $latitude);
            }
            //echo $distance.'<br />';

            //获取最新该考勤机上一个考勤记录
            $data = ServiceFactory::getService("Attendance")->getLastTest($tpAppid, $tpMachineid);//print_r($data);

            //初次签到
            if (empty($data[0])) {
                if ($app_ip == $machine_ip) { //IP相同
                    ServiceFactory::getService("Attendance")->insertTest($tpAppid, $tpMachineid, $app_ip, $longitude, $latitude, $type, $distance, $time);
                } else if ($distance <= 50) {
                    ServiceFactory::getService("Attendance")->insertTest($tpAppid, $tpMachineid, $app_ip, $longitude, $latitude, $type, $distance, $time);
                } else {
                    $ret = array(
                        "status" => 0,
                        "data" => "empty data discarded"
                    );
                    echo json_encode($ret);
                    exit;
                }

                continue;
            }

            $punch_id = $data[0]['id'];
            $punch_createtime = $data[0]['createtime'];
            $punch_last_active_type = $data[0]['last_active_type'];

            //是否同一天
            //echo (date('Ymd', $punch_createtime) != date('Ymd',$time)).'<br />';
            if (date('Ymd', $punch_createtime) != date('Ymd', $time)) {
                if ($app_ip == $machine_ip) {
                    ServiceFactory::getService("Attendance")->insertTest($tpAppid, $tpMachineid, $app_ip, $longitude, $latitude, $type, $distance, $time);
                } else if ($distance <= 50) {
                    ServiceFactory::getService("Attendance")->insertTest($tpAppid, $tpMachineid, $app_ip, $longitude, $latitude, $type, $distance, $time);
                } else {
                    $ret = array(
                        "status" => 0,
                        "data" => "not one day data discarded"
                    );
                    echo json_encode($ret);
                    exit;
                }

                continue;
            }

            //5分钟内的数据不做处理
            //echo time() - $punch_createtime.'<br />';
            if ($time - $punch_createtime <= 300) {
                $ret = array(
                    "status" => 0,
                    "data" => "5 min data discarded"
                );
                echo json_encode($ret);

                continue;
            }

            if ($distance <= 50 && $punch_last_active_type != 0) {
                ServiceFactory::getService("Attendance")->insertTest($tpAppid, $tpMachineid, $app_ip, $longitude, $latitude, $type, $distance, $time);
            } else if ($distance > 50 && $punch_last_active_type == 0) {
                ServiceFactory::getService("Attendance")->updateTest($tpAppid, $punch_id, $app_ip, $type, $distance, $time);
            } else {
                $ret = array(
                    "status" => 0,
                    "data" => "other data discarded"
                );
                echo json_encode($ret);
                exit;
            }
        }//end of foreach

        $ret = array(
            "status" => 1,
            "data" => "ok"
        );
        die(json_encode($ret));
    }

    public function enterAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $longitude = $_REQUEST['longitude']; //经度
        $latitude = $_REQUEST['latitude']; //经度
        $ip = trim($_SERVER['REMOTE_ADDR']);
        //$type = 5;
        $distance = 50;
        $ret = array(
            "status" => 0,
            "data" => ""
        );
        if (empty($appid)) {
            //Result::showError("appid is empty");
            $ret['data'] = 'appid is empty';
            die(json_encode($ret));
        }
        if ("" == $longitude || "" == $latitude) {
            //Result::showError("longitude or latitude is empty");
            $ret['data'] = 'longitude or latitude is empty';
            die(json_encode($ret));
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            //Result::showError("appid ".$appid." have not reg");
            $ret['data'] = "appid " . $appid . " have not reg";
            die(json_encode($ret));
        }
        $phoneType = ServiceFactory::getService("App")->getPhoneType($appid);
        ServiceFactory::getService("App")->active($tpAppid, true);
        /*$res = ServiceFactory::getService("App")->updateLocation($tpAppid, $longitude, $latitude, $type, $distance);
        if (!$res) {
            //Result::showError("system error");
            $ret['data'] = "system error";
            die(json_encode($ret));
        }*/
        //获取绑定的机器
        $tpMachineidArray = ServiceFactory::getService("Attendance")->getBindMachineidList($tpAppid);
        if (empty($tpMachineidArray)) {
            //Result::showError("请先绑定考勤机");
            $ret['data'] = "请先绑定考勤机";
            die(json_encode($ret));
        }
        foreach ($tpMachineidArray as $key => $item) {
            $tpMachineid = $item['tp_machineid'];

            //判断是否为考勤机
            $machineid = ServiceFactory::getService("Attendance")->getMachineid($tpMachineid);
            if (substr($machineid, 0, 2) != '08') {
                continue;
            }

            //获考勤机的详细信息
            $machineDetail = ServiceFactory::getService("Machine")->getDetail($tpMachineid);//print_r($machineDetail);
            $machine_ip = $machineDetail['last_active_ip'];
            $machineLongitude = floatval($machineDetail['longitude']);
            $machineLatitude = floatval($machineDetail['latitude']);
            $machineLongitudeAndroid = floatval($machineDetail['longitude_android']);
            $machineLatitudeAndroid = floatval($machineDetail['latitude_android']);
            if ("1" == $phoneType) {
                $distance = $this->getDistance($longitude, $latitude, $machineLongitudeAndroid, $machineLatitudeAndroid);
            } else if ("2" == $phoneType) {
                $distance = $this->getDistance($longitude, $latitude, $machineLongitude, $machineLatitude);
            }
            if ($ip != trim($machine_ip) && $distance > 50) {
                $ret['data'] = "在考勤范围内才能打卡";
                die(json_encode($ret));
            }
            $this->insertOrUpdateCheckIn($tpAppid, $tpMachineid, $ip, $longitude, $latitude, $distance);
        }
        $ret['data'] = "ok";
        $ret['status'] = "1";
        die(json_encode($ret));
    }

    public function leaveAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $longitude = $_REQUEST['longitude']; //经度
        $latitude = $_REQUEST['latitude']; //经度
        $ip = trim($_SERVER['REMOTE_ADDR']);
        //$type = 5;
        $distance = 50;
        $ret = array(
            "status" => 0,
            "data" => ""
        );
        if (empty($appid)) {
            //Result::showError("appid is empty");
            $ret['data'] = 'appid is empty';
            die(json_encode($ret));
        }
        if ("" == $longitude || "" == $latitude) {
            //Result::showError("longitude or latitude is empty");
            $ret['data'] = 'longitude or latitude is empty';
            die(json_encode($ret));
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            //Result::showError("appid ".$appid." have not reg");
            $ret['data'] = "appid " . $appid . " have not reg";
            die(json_encode($ret));
        }
        $phoneType = ServiceFactory::getService("App")->getPhoneType($appid);
        ServiceFactory::getService("App")->active($tpAppid, true);
        /*$res = ServiceFactory::getService("App")->updateLocation($tpAppid, $longitude, $latitude, $type, $distance);
        if (!$res) {
            //Result::showError("system error");
            $ret['data'] = "system error";
            die(json_encode($ret));
        }*/
        //获取绑定的机器
        $tpMachineidArray = ServiceFactory::getService("Attendance")->getBindMachineidList($tpAppid);
        if (empty($tpMachineidArray)) {
            //Result::showError("请先绑定考勤机");
            $ret['data'] = "请先绑定考勤机";
            die(json_encode($ret));
        }
        foreach ($tpMachineidArray as $key => $item) {
            $tpMachineid = $item['tp_machineid'];

            //判断是否为考勤机
            $machineid = ServiceFactory::getService("Attendance")->getMachineid($tpMachineid);
            if (substr($machineid, 0, 2) != '08') {
                continue;
            }

            //获考勤机的详细信息
            $machineDetail = ServiceFactory::getService("Machine")->getDetail($tpMachineid);//print_r($machineDetail);
            $machine_ip = $machineDetail['last_active_ip'];
            $machineLongitude = floatval($machineDetail['longitude']);
            $machineLatitude = floatval($machineDetail['latitude']);
            $machineLongitudeAndroid = floatval($machineDetail['longitude_android']);
            $machineLatitudeAndroid = floatval($machineDetail['latitude_android']);
            if ("1" == $phoneType) {
                $distance = $this->getDistance($longitude, $latitude, $machineLongitudeAndroid, $machineLatitudeAndroid);
            } else if ("2" == $phoneType) {
                $distance = $this->getDistance($longitude, $latitude, $machineLongitude, $machineLatitude);
            }
            if ($ip != trim($machine_ip) && $distance > 50) {
                $ret['data'] = "在考勤范围内才能打卡";
                die(json_encode($ret));
            }
            //获取最新该考勤机一个考勤记录
            //$data = ServiceFactory::getService("Attendance")->getPunchLast($tpAppid, $tpMachineid);//print_r($data);
            //$punch_id = $data[0]['id'];

            //ServiceFactory::getService("Attendance")->updatePunch($tpAppid, $punch_id, $ip, $type, $distance);
            $this->insertOrUpdateCheckIn($tpAppid, $tpMachineid, $ip, $longitude, $latitude, $distance, false);

        }
        //Result::showOk("ok");
        $ret['data'] = "ok";
        $ret['status'] = "1";
        die(json_encode($ret));
    }

    private function goodReturn($data)
    {
        $return = array(
            "status" => 1,
            "data" => $data
        );
        die(json_encode($return));
    }

    private function badReturn($data)
    {
        $return = array(
            "status" => 0,
            "data" => $data
        );
        die(json_encode($return));
    }

    private function insertOrUpdateCheckIn($tpAppid, $tpMachineid, $ip, $longitude, $latitude, $distance, $isStart = true)
    {
        $time = time();
        $isInsert = true;
        $data = ServiceFactory::getService("Attendance")->getLastCheckIn($tpAppid, $tpMachineid)[0];
        if ($data) {
            $createTime = $data["createtime"];
            $endTime = $data["last_active_time"];
            if ($isStart && $endTime && ($time - $endTime) < 300) {
                ServiceFactory::getService("Attendance")->restartCheckIn($tpAppid, $data["id"]);
                return;
            }
            if (date("Ymd", $createTime) == date("Ymd", $time)) {
                if (!$data["last_active_time"] && $isStart) {
                    $this->badReturn("尚未结束打卡");
                }
                if ($data["last_active_time"] && !$isStart) {
                    $this->badReturn("尚未开始打卡");
                }
                if (!$data["last_active_time"]) {
                    $isInsert = false;
                }
            } else {
                if (!$isStart) {
                    $this->badReturn("尚未开始打卡");
                }
            }
        } else if (!$isStart) {
            $this->badReturn("尚未开始打卡");
        }
        if ($isInsert) {
            ServiceFactory::getService("Attendance")->addCheckIn($tpAppid, $tpMachineid, $ip, $longitude, $latitude, $distance);
        } else {
            ServiceFactory::getService("Attendance")->updateCheckIn($tpAppid, $data["id"], $ip, $distance);
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

    //新增考勤人
    public function addpunchAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        //获取APP的数据
        $appid = trim($_REQUEST['appid']);
        //$username = trim($_REQUEST['username']);

        $phonenumber = trim($_REQUEST['phonenumber']);
        if (empty($phonenumber)) {
            $ret["data"] = "手机号不能为空";
            echo json_encode($ret);
            die();
        }

        /*if (empty($username)) {
            $ret["data"] = "用户名不能为空";
            echo json_encode($ret);
            die();
        }*/

        //$data = ServiceFactory::getService("Attendance")->addpunch($tpAppid, $username, $phonenumber);
        $data = ServiceFactory::getService("Attendance")->addpunch($tpAppid, $phonenumber);

        echo json_encode($data);
    }

    //接受考勤(成为考勤人下级)
    public function acceptpunchAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $appid = trim($_REQUEST['appid']);
        if (empty($appid)) {
            $ret["data"] = "appid不能为空";
            echo json_encode($ret);
            die();
        }
        $phonenumber = trim($_REQUEST['phonenumber']);
        if (empty($phonenumber)) {
            $ret["data"] = "手机号不能为空";
            echo json_encode($ret);
            die();
        }
        $is_accept = trim($_REQUEST['is_accept']);
        if (!in_array($is_accept, array('-1', '1'))) {
            $ret["data"] = "请选择接受(1)或拒接(-1)";
            echo json_encode($ret);
            die();
        }

        $data = ServiceFactory::getService("Attendance")->acceptpunch($appid, $phonenumber, $is_accept);
        if ($data) {
            $ret = array(
                "status" => 1,
                "data" => "ok"
            );
        } else {
            $ret["data"] = "appid已存在";
        }

        echo json_encode($ret);
    }

    public function getPunchMonthTestAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $tpAppid = $this->checkData();
        $time = $_REQUEST['time'];
        if (empty($time)) {
            $ret["data"] = "时间不能为空";
            echo json_encode($ret);
            die();
        }
        $machineid = trim($_REQUEST['machineid']);
        if (empty($machineid)) {
            $ret["data"] = "machineid is empty";
            echo json_encode($ret);
            die();
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            echo json_encode("machineid " . $machineid . " have not reg");
            die();
        }
        $ret = array(
            "status" => 1,
            "data" => ""
        );

        $dataOne['day'] = "2016-08-08";
        $dataOne['time_total'] = 1234;
        $dataOne['status'] = 0;
        $data[0] = $dataOne;

        $dataTwo['day'] = "2016-08-09";
        $dataTwo['time_total'] = 1234;
        $dataTwo['status'] = 1;
        $data[1] = $dataTwo;

        $dataThree['day'] = "2016-08-10";
        $dataThree['time_total'] = 1234;
        $dataThree['status'] = 2;
        $data[2] = $dataThree;

        $ret['data'] = $data;
        echo json_encode($ret);
    }

    //获取用户考勤记录(月)
    public function getPunchMonthAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $time = $_REQUEST['time'];
        if (empty($time)) {
            $ret["data"] = "时间不能为空";
            echo json_encode($ret);
            die();
        }

//        $machineid = trim($_REQUEST['machineid']);
//        if (empty($machineid)) {
//            $ret["data"] = "machineid is empty";
//            echo json_encode($ret);
//            die();
//        }
//
//        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
//        if (empty($tpMachineid)) {
//            echo json_encode("machineid " . $machineid . " have not reg");
//            die();
//        }

        $data = ServiceFactory::getService("Attendance")->getPunchMonth($tpAppid, $tpMachineid='', $time);
        $new_data = array();


        $i = 0;
        foreach ($data as $key => $value) {

            if ($key == 0) {

                $Ymd = date('Y-m-d', $value['createtime']);

                $createtime = $value['createtime'];

                continue;
            }


            if (date('Y-m-d', $value['createtime']) == $Ymd) {
                $new_data[$i]['day'] = $Ymd;
                if (!empty($value['last_active_time'])) {
                    $new_data[$i]['time_total'] += $value['last_active_time'] - $createtime;

                }
            } else {
                $Ymd = date('Y-m-d', $value['createtime']);
                $createtime = $value['createtime'];
                $i++;

                $new_data[$i]['day'] = $Ymd;
                $new_data[$i]['time_total'] += $value['last_active_time'] - $value['createtime'];
            }


        }


        $min_5 = 5 * 60;
        $min_30 = 30 * 60;

        $status = array(
            'normal' => 0,
            'late' => 1,
            'absence' => 2,
            'other' => 3,
        );

        foreach ($new_data as $key => $value) {
            if ($value['time_total'] >= $min_30) {
                $new_data[$key]['status'] = $status['absence'];
            } else if ($value['time_total'] >= $min_5) {
                $new_data[$key]['status'] = $status['late'];
            } else {
                $new_data[$key]['status'] = $status['normal'];
            }
        }

        $new_data = array_values($new_data);


        if ($data) {
            $ret = array(
                "status" => 1,
                "data" => $new_data
            );
        } else {
            $ret["data"] = "获取失败";
        }
        echo json_encode($ret);
    }

    public function getPunchDayTestAction()
    {

    }

    //获取用户考勤记录(日)
    public function getPunchDayAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $time = $_REQUEST['time'];
        if (empty($time)) {
            $ret["data"] = "time is empty";
            echo json_encode($ret);
            die();
        }

//        $machineid = trim($_REQUEST['machineid']);
//        if (empty($machineid)) {
//            $ret["data"] = "machineid is empty";
//            echo json_encode($ret);
//            die();
//        }
//
//        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
//        if (empty($tpMachineid)) {
//            echo json_encode("machineid " . $machineid . " have not reg");
//            die();
//        }

        $data = ServiceFactory::getService("Attendance")->getPunchDay($tpAppid, $tpMachineid = '', $time);

        $ret = array(
            "status" => 1,
            "data" => $data
        );

        echo json_encode($ret);
    }

    public function getCheckInMonthAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $ret = array(
            "status" => 0,
            "data" => ""
        );
        $tpAppid = $this->checkData();
        $time = $_REQUEST['time'];
        if (empty($time)) {
            $ret["data"] = "时间不能为空";
            echo json_encode($ret);
            die();
        }
        $machineid = trim($_REQUEST['machineid']);
        if (empty($machineid)) {
            $ret["data"] = "machineid is empty";
            echo json_encode($ret);
            die();
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            echo json_encode("machineid " . $machineid . " have not reg");
            die();
        }
        $data = ServiceFactory::getService("Attendance")->getCheckInMonth($tpAppid, $tpMachineid, $time);
        $new_data = array();
        $i = 0;
        foreach ($data as $key => $value) {
            if ($key == 0) {
                $Ymd = date('Y-m-d', $value['createtime']);
                $createtime = $value['createtime'];
                continue;
            }
            if (date('Y-m-d', $value['createtime']) == $Ymd) {
                $new_data[$i]['day'] = $Ymd;
                if (!empty($value['last_active_time'])) {
                    $new_data[$i]['time_total'] += $value['last_active_time'] - $createtime;
                }
            } else {
                $Ymd = date('Y-m-d', $value['createtime']);
                $createtime = $value['createtime'];
                $i++;
                $new_data[$i]['day'] = $Ymd;
                $new_data[$i]['time_total'] += $value['last_active_time'] - $value['createtime'];
            }
        }
        $min_5 = 5 * 60;
        $min_30 = 30 * 60;
        $status = array(
            'normal' => 0,
            'late' => 1,
            'absence' => 2,
            'other' => 3,
        );
        foreach ($new_data as $key => $value) {
            if ($value['time_total'] >= $min_30) {
                $new_data[$key]['status'] = $status['absence'];
            } else if ($value['time_total'] >= $min_5) {
                $new_data[$key]['status'] = $status['late'];
            } else {
                $new_data[$key]['status'] = $status['normal'];
            }
        }
        $new_data = array_values($new_data);
        if ($data) {
            $ret = array(
                "status" => 1,
                "data" => $new_data
            );
        } else {
            $ret["data"] = "获取失败";
        }
        echo json_encode($ret);
    }

    public function getCheckInDayAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $ret = array(
            "status" => 0,
            "data" => ""
        );
        $tpAppid = $this->checkData();
        $time = $_REQUEST['time'];
        if (empty($time)) {
            $ret["data"] = "time is empty";
            echo json_encode($ret);
            die();
        }
        $machineid = trim($_REQUEST['machineid']);
        if (empty($machineid)) {
            $ret["data"] = "machineid is empty";
            echo json_encode($ret);
            die();
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            echo json_encode("machineid " . $machineid . " have not reg");
            die();
        }
        $data = ServiceFactory::getService("Attendance")->getCheckInDay($tpAppid, $tpMachineid, $time);
        $ret = array(
            "status" => 1,
            "data" => $data
        );
        echo json_encode($ret);
    }

    //获取下级考勤人列表
    public function getPunchListAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $data = ServiceFactory::getService("Attendance")->getPunchList($tpAppid);

        echo json_encode($data);
    }

    //图片上传
    public function uploadAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $pathinfo = pathinfo($_FILES['file']['name']);
        $extension = $pathinfo['extension'];

        $tmp_name = $_FILES['file']['tmp_name']; // 文件上传后得临时文件名
        $name = $_FILES['file']['name']; // 被上传文件的名称
        // $size = $_FILES['file']['size']; // 被上传文件的大小
        //$type = $_FILES['file']['type']; // 被上传文件的类型

        $uploaddir = dirname(dirname(dirname(__FILE__)));
        $dir = $uploaddir . '/public/upload';
        //$type = explode(".", $name);
        //$type = @$type[1];
        $rename = $dir . "/" . time() . "." . $extension;

        //echo $rename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $rename)) {
            $filename = "upload/" . time() . "." . $extension;
            $res = ServiceFactory::getService("Attendance")->upload($filename, $tpAppid);
            //$ret['data'] = 'http://api.sunsyi.com:8081/' . $filename;
            $ret['data'] = 'ok';
        }

        echo json_encode($ret);
    }

    //获取头像
    public function getheadurlAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $headurl = ServiceFactory::getService("Attendance")->getheadurl($tpAppid);

        $uploaddir = dirname(dirname(dirname(__FILE__)));
        $url = $uploaddir . '/public/' . $headurl;
        //file_get_contents($url,true); 可以读取远程图片，也可以读取本地图片
        $img = file_get_contents($url, true);
        //使用图片头输出浏览器
        header("Content-Type: image/jpeg;text/html; charset=utf-8");
        echo $img;
        exit;
    }

    //重设密码
    public function getpasswordAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        echo json_encode($ret);
    }

    //设置工作时间
    public function setworktimeAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => "ok"
        );

        $tpAppid = $this->checkData();

        $week = trim($_REQUEST['week']);
        $time = trim($_REQUEST['time']);

        if (empty($week)) {
            $ret["data"] = "week empty";
            echo json_encode($ret);
            die();
        }
        if (empty($time)) {
            $ret["data"] = "time empty";
            echo json_encode($ret);
            die();
        }

        $res = ServiceFactory::getService("Attendance")->setworktime($tpAppid, $week, $time);

        echo json_encode($ret);
    }

    //获取工作时间
    public function getworktimeAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $data = ServiceFactory::getService("Attendance")->getworktime($tpAppid);

        if (empty($data)) {
            $ret['data'] = '获取失败';
            die(json_encode($ret));
        }

        $new_data['week'] = $data[0]['week'];
        $time = unserialize($data[0]['time']);
        $time = implode(',', $time);
        $new_data['time'] = $time;

        $ret = array(
            "status" => 1,
            "data" => $new_data
        );
        echo json_encode($ret);
    }

    //设置个人工作时间
    public function setworktimeappAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => "ok"
        );

        $tpAppid = $this->checkData();

        $phonenumber = trim($_REQUEST['phonenumber']);
        $week = trim($_REQUEST['week']);
        $time = trim($_REQUEST['time']);

        if (empty($phonenumber)) {
            $ret["data"] = "phonenumber empty";
            echo json_encode($ret);
            die();
        }
        if (empty($week)) {
            $ret["data"] = "week empty";
            echo json_encode($ret);
            die();
        }
        if (empty($time)) {
            $ret["data"] = "time empty";
            echo json_encode($ret);
            die();
        }

        $res = ServiceFactory::getService("Attendance")->setworktimeapp($tpAppid, $phonenumber, $week, $time);

        echo json_encode($ret);
    }

    //获取工作时间
    public function getworktimeappAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $data = ServiceFactory::getService("Attendance")->getworktimeapp($tpAppid);

        if (empty($data)) {
            $ret['data'] = '获取失败';
            die(json_encode($ret));
        }

        $new_data['week'] = $data[0]['work_week'];
        $time = unserialize($data[0]['work_time']);
        $time = implode(',', $time);
        $new_data['time'] = $time;

        $ret = array(
            "status" => 1,
            "data" => $new_data
        );
        echo json_encode($ret);
    }

    //设置名称
    public function setnameAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => "ok"
        );

        $tpAppid = $this->checkData();

        $machineid = trim($_REQUEST['machineid']);
        $name = trim($_REQUEST['name']);

        if (empty($machineid)) {
            $ret["data"] = "machineid empty";
            echo json_encode($ret);
            die();
        }
        if (empty($name)) {
            $ret["data"] = "name empty";
            echo json_encode($ret);
            die();
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);

        $data = ServiceFactory::getService("Attendance")->setname($tpAppid, $tpMachineid, $name);

        if (!$data) {
            $ret['data'] = '设置考勤机名称失败';
        }

        echo json_encode($ret);
    }

    //获取考勤机名称
    public function getnameAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 1,
            "data" => "ok"
        );

        $tpAppid = $this->checkData();

        $machineid = trim($_REQUEST['machineid']);


        if (empty($machineid)) {
            $ret["data"] = "machineid empty";
            echo json_encode($ret);
            die();
        }


        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);

        $data = ServiceFactory::getService("Attendance")->getname($tpAppid, $tpMachineid);

        if (!$data) {
            $ret = array(
                "status" => 0,
                'data' => '获取考勤机名称失败'
            );
            die(json_encode($ret));
        }

        $ret = array(
            "status" => 1,
            "data" => $data['machine_name']
        );
        echo json_encode($ret);
    }

    //获取消息列表
    public function getmsgAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 1,
            "data" => ""
        );

        $tpAppid = $this->checkData();

        $data = ServiceFactory::getService("Attendance")->getmsg($tpAppid);

        if (!$data) {
            $ret = array(
                "status" => 0,
                'data' => '暂无消息'
            );
        } else {
            $ret['data'] = $data;
        }

        echo json_encode($ret);
    }

    //获取消息内容
    public function getmsginfoAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 0,
            "data" => ""
        );

        $id = intval($_REQUEST['msg_id']);

        if (empty($id)) {
            $ret["data"] = "msg_id empty";
            echo json_encode($ret);
            die();
        }

        $tpAppid = $this->checkData();

        $data = ServiceFactory::getService("Attendance")->getmsginfo($tpAppid, $id);

        if (!$data) {
            $ret['data'] = '消息不存在';
        } else {
            $ret = array(
                "status" => 1,
                'data' => $data
            );
        }

        echo json_encode($ret);
    }

    //标记消息已读
    public function msgreadAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $ret = array(
            "status" => 1,
            "data" => "ok"
        );

        $id = intval($_REQUEST['msg_id']);

        if (empty($id)) {
            $ret["data"] = "msg_id empty";
            echo json_encode($ret);
            die();
        }

        $tpAppid = $this->checkData();

        $data = ServiceFactory::getService("Attendance")->msgread($tpAppid, $id);

        if (!$data) {
            $ret['data'] = '操作失败';
        }

        echo json_encode($ret);
    }

    //APP绑定标签
    public function labelbindAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        //记录标签日志
        file_put_contents("labelbind.log", date("Y-m-d H:i:s") . " labelid=" . $labelid . " machineid=" . $machineid . " ip=" . $ip . " longitude=" . $longitude . " latitude=" . $latitude . "\n", FILE_APPEND);

        $ret = array(
            "status" => 1,
            "data" => "ok"
        );

        $labelid = $_REQUEST['labelid'];

        if (empty($labelid)) {
            $ret["data"] = "labelid empty";
            echo json_encode($ret);
            die();
        }

        $tpAppid = $this->checkData();

        $res = ServiceFactory::getService("Attendance")->labelbind($tpAppid, $labelid);

        if (!$res) {
            $ret['data'] = '操作失败';
        }

        echo json_encode($ret);
    }

    //考勤机发送状态
    public function labelcheckAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();

        $labelid = $_REQUEST['labelid'];
        $machineid = $_REQUEST['machineid'];
        $ip = trim($_SERVER['REMOTE_ADDR']);
        $longitude = $_REQUEST['longitude'];
        $latitude = $_REQUEST['latitude'];

        //记录标签日志
        file_put_contents("label.log", date("Y-m-d H:i:s") . " labelid=" . $labelid . " machineid=" . $machineid . " ip=" . $ip . " longitude=" . $longitude . " latitude=" . $latitude . "\n", FILE_APPEND);

        if (empty($labelid)) {
            $ret = array(
                "status" => 0,
                "data" => "labelid is empty"
            );
            die(json_encode($ret));
        }

        if (empty($machineid)) {
            $ret = array(
                "status" => 0,
                "data" => "machineid is empty"
            );
            die(json_encode($ret));
        }

        $tpLabelid = ServiceFactory::getService("Machine")->getTpMachineid($labelid);
        if (empty($tpLabelid)) {
            echo json_encode("labelid " . $labelid . " have not reg");
            die();
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            echo json_encode("machineid " . $machineid . " have not reg");
            die();
        }

        //获取最新一条数据
        $last_label = ServiceFactory::getService("Attendance")->getLastLabel($tpLabelid, $tpMachineid);//print_r($last_label);

        //数据为空,记录进入记录
        if (empty($last_label)) {
            $res = ServiceFactory::getService("Attendance")->insertLabel($tpLabelid, $tpMachineid, $ip, $longitude, $latitude);
            if ($res) {
                $ret = array(
                    "status" => 1,
                    "data" => "ok"
                );
                die(json_encode($ret));
            } else {
                $ret = array(
                    "status" => 0,
                    "data" => "error"
                );
                die(json_encode($ret));
            }
        }

        $timediff = ServiceFactory::getService("Attendance")->timediff($last_label['create_time'], time());//print_r($timediff);
        if ($timediff['min'] <= 6) { //和上一次离开时间间隔小于等于6分钟，更新记录
            $res = ServiceFactory::getService("Attendance")->updateLabel($tpLabelid, $tpMachineid, $ip, $last_label['id'], $longitude, $latitude);
            if ($res) {
                $ret = array(
                    "status" => 1,
                    "data" => "ok"
                );
                die(json_encode($ret));
            } else {
                $ret = array(
                    "status" => 0,
                    "data" => "error"
                );
                die(json_encode($ret));
            }
        } else { //和上一次离开时间间隔大于6分钟，更新记录
            $res = ServiceFactory::getService("Attendance")->insertLabel($tpLabelid, $tpMachineid, $ip, $longitude, $latitude);
            if ($res) {
                $ret = array(
                    "status" => 1,
                    "data" => "ok"
                );
                die(json_encode($ret));
            } else {
                $ret = array(
                    "status" => 0,
                    "data" => "error"
                );
                die(json_encode($ret));
            }
        }
    }

    public function colleagueAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $parentId = $_SESSION['parent_id'];
        $data = ServiceFactory::getService('Attendance')->getColleague($parentId);
        header('ContentType:text/json');
        $this->getResponse()->setBody(json_encode($data));
    }
}