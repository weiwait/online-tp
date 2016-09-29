<?php
use base\ServiceFactory;
use base\DaoFactory;
use utils\Common;
use utils\Result;

include_once "MCommonController.php";

class MachineController extends MCommonController
{
    public function regAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = $_REQUEST['machineid'];
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $tpMachineid = $this->getTPMachineid();
        if ($tpMachineid) {
            ServiceFactory::getService("Machine")->active($tpMachineid, $_REQUEST['ip']);
        }

        $existFlag = ServiceFactory::getService("Machine")->isExist($machineid);
        if ($existFlag) {
            Result::showOk("machineid " . $machineid . " have reg");
        }

        $ret = ServiceFactory::getService("Machine")->reg($machineid);

        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function elecsignAction()
    {
        $machineid = $_REQUEST['machineid'];
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        //判断是否为标签
        file_put_contents("elecsign.log", date("Y-m-d H:i:s") . " " . " request=" . var_export($_REQUEST, true) . " type=" . substr($machineid, 0, 2) . "\n", FILE_APPEND);

        $machineid_arr = explode(',', $machineid);
        $machineid = $machineid_arr[0];
        $labelid = $machineid_arr[1];

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);


        // 更换到主考勤机
        $tpMachineid = ServiceFactory::getService("Machine")->getMachineGroupMaster($tpMachineid);

        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }
        $tpLabelid = ServiceFactory::getService("Machine")->getTpMachineid($labelid);
        if (empty($tpMachineid)) {
            Result::showError("labelid " . $labelid . " have not reg");
        }
        ServiceFactory::getService("Machine")->active($tpLabelid, $_REQUEST['ip']);

        //非标签
        if (substr($labelid, 0, 2) != '10') {
            //$tpLabelid = $tpMachineid;
            //$tpMachineid = '1118';
            $ret = array(
                "status" => 0,
                "data" => "machineid is not label"
            );
            die(json_encode($ret));
        }

        $ip = $_REQUEST['ip'];

        //获取考勤机的经纬度
        $machine_detail = ServiceFactory::getService("Machine")->getDetail($tpMachineid);
        $longitude = $machine_detail['longitude'];
        $latitude = $machine_detail['latitude'];

        //获取最新一条数据
        $last_label = ServiceFactory::getService("Attendance")->getLastLabel($tpLabelid, $tpMachineid);

        //数据为空,记录进入记录
        if (empty($last_label)) {
            $res = $this->insertNewLable($tpLabelid, $tpMachineid, $ip, $longitude, $latitude);
            if ($res) {
                ServiceFactory::getService("Attendance")->insertPunch($tpLabelid, $tpMachineid, $ip, $longitude, $latitude, 5, '0', time());
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

        $time_dirr = time() - $last_label['leave_time'];
        if ($time_dirr <= 600) { //和上一次离开时间间隔小于等于6分钟，，更新记录
            $res = $this->updateNewLabel($tpLabelid, $tpMachineid, $ip, $last_label['id'], $longitude, $latitude);

            $data = ServiceFactory::getService("Attendance")->getPunchLast($tpLabelid, $tpMachineid);
            $punch_id = $data[0]['id'];
            ServiceFactory::getService("Attendance")->updatePunch($tpLabelid, $punch_id, $ip, 5, '0', time());

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
        } else { //和上一次离开时间间隔大于6分钟，插入记录
            $res = $this->insertNewLable($tpLabelid, $tpMachineid, $ip, $longitude, $latitude);
            if ($res) {
                ServiceFactory::getService("Attendance")->insertPunch($tpLabelid, $tpMachineid, $ip, $longitude, $latitude, 5, '0', time());
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

    /**
     * 标签心跳,暂时也更新推送手机消息
     * @param $tpLabelid
     * @param $tpMachineid
     * @param $ip
     * @param $lastLabelId
     * @param $longitude
     * @param $latitude
     * @throws Yaf_Exception_StartupError
     */
    private function updateNewLabel($tpLabelid, $tpMachineid, $ip, $lastLabelId, $longitude, $latitude)
    {
//        ServiceFactory::getService("Attendance")->pushLabelNotification($tpLabelid);
        return ServiceFactory::getService("Attendance")->updateLabel($tpLabelid, $tpMachineid, $ip, $lastLabelId, $longitude, $latitude);
    }

    /**
     * 标签进入开始,推送手机消息
     * @param $tpLabelid
     * @param $tpMachineid
     * @param $ip
     * @param $longitude
     * @param $latitude
     * @return mixed
     * @throws Yaf_Exception_StartupError
     */
    private function insertNewLable($tpLabelid, $tpMachineid, $ip, $longitude, $latitude)
    {
//        ServiceFactory::getService("Attendance")->pushLabelNotification($tpLabelid);
        return ServiceFactory::getService("Attendance")->insertLabel($tpLabelid, $tpMachineid, $ip, $longitude, $latitude);
    }

    //返回注册machineid
    public function newregAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = $_REQUEST['machineid'];
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        $machineid = substr($machineid, 0, 14);
        $count = ServiceFactory::getService("Machine")->countreg($machineid);
        $machineid .= sprintf("%06d", $count);

        $existFlag = ServiceFactory::getService("Machine")->isExist($machineid);
        // if($existFlag)
        // {
        //     Result::showOk("machineid ".$machineid." have reg"); 
        // }

        // $ret = ServiceFactory::getService("Machine")->reg($machineid);

        if (!$existFlag) {
            Result::showOk($machineid);
        } else {
            Result::showError("system error");
        }
    }

    public function updatelocationAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        file_put_contents("test2.log", "test", FILE_APPEND);
        $ret = ServiceFactory::getService("Machine")->updateLocation();
        Result::showOk($ret);
    }


    /**
     * @desc 电器更新状态
     */
    public function updatestateAction()
    {
        $this->checkMachineid();
        $substr = $this->getTypePrefix();
        if ($this->prefixMosquitokiller == $substr) {
            $this->forward("mosquitokiller", "updatestate");
        } else if ($this->prefixLight == $substr || $this->prefixRgb == $substr) {
            $this->forward("light", "updatestate");
        } else if ($this->prefixHumidifier == $substr) {
            $this->forward("humidifier", "updatestate");
        } else if ($this->prefixSwitch == $substr) {
            $this->forward("switch", "updatestate");
        } else if ($this->prefixTeapot == $substr) {
            $this->forward("teapot", "updatestate");
        }
    }

    /**
     * @desc 电器更新状态
     */
    public function orderAction()
    {
        $this->checkMachineid();
        $substr = $this->getTypePrefix();
        if ($this->prefixMosquitokiller == $substr) {
            $this->forward("mosquitokiller", "order");
        } else if ($this->prefixLight == $substr || $this->prefixRgb == $substr) {
            $this->forward("light", "order");
        } else if ($this->prefixHumidifier == $substr) {
            $this->forward("humidifier", "order");
        } else if ($this->prefixSwitch == $substr) {
            $this->forward("switch", "order");
        } else if ($this->prefixTeapot == $substr) {
            $this->forward("teapot", "order");
        }
    }

    /**
     * @desc 电器更新状态
     */
    public function cancelorderAction()
    {
        $this->checkMachineid();
        $substr = $this->getTypePrefix();
        if ($this->prefixMosquitokiller == $substr) {
            $this->forward("mosquitokiller", "cancelorder");
        } else if ($this->prefixLight == $substr || $this->prefixRgb == $substr) {
            $this->forward("light", "cancelorder");
        } else if ($this->prefixHumidifier == $substr) {
            $this->forward("humidifier", "cancelorder");
        } else if ($this->prefixSwitch == $substr) {
            $this->forward("switch", "cancelorder");
        } else if ($this->prefixTeapot == $substr) {
            $this->forward("teapot", "cancelorder");
        }
    }

    /**
     * @desc 获取电器预约
     */
    public function getorderAction()
    {
        $this->checkMachineid();
        $substr = $this->getTypePrefix();
        if ($this->prefixMosquitokiller == $substr) {
            $this->forward("mosquitokiller", "getorder");
        } else if ($this->prefixLight == $substr || $this->prefixRgb == $substr) {
            $this->forward("light", "getorder");
        } else if ($this->prefixHumidifier == $substr) {
            $this->forward("humidifier", "getorder");
        } else if ($this->prefixSwitch == $substr) {
            $this->forward("switch", "getorder");
        } else if ($this->prefixTeapot == $substr) {
            $this->forward("teapot", "getorder");
        }
    }

    /**
     * @desc 获取电器预约列表
     */
    public function getorderlistAction()
    {
        $this->checkMachineid();
        $substr = $this->getTypePrefix();
        if ($this->prefixMosquitokiller == $substr) {
            $this->forward("mosquitokiller", "getorderlist");
        } else if ($this->prefixLight == $substr || $this->prefixRgb == $substr) {
            $this->forward("light", "getorderlist");
        } else if ($this->prefixHumidifier == $substr) {
            $this->forward("humidifier", "getorderlist");
        } else if ($this->prefixSwitch == $substr) {
            $this->forward("switch", "getorderlist");
        } else if ($this->prefixTeapot == $substr) {
            $this->forward("teapot", "getorderlist");
        }
    }

    /**
     * @desc 电器来获取是否需要开启
     */
    public function renewlistAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $ip = trim($_REQUEST['ip']);
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $arr = explode(",", $machineid);
        foreach ($arr as $item) {
            $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($item);
            $this->machineStateUpdate($item, $tpMachineid);
            if (!empty($tpMachineid)) {
                ServiceFactory::getService("Machine")->active($tpMachineid, $ip);
            }
        }

        Result::showOk("ok");
    }

    private function machineStateUpdate($machineid, $tpMachineid)
    {
        if (substr($machineid, 0, 2) == '06') {
            ServiceFactory::getService("Mosquitokiller")->updateWaterLevel($tpMachineid);
        }
    }

    public function getControlData()
    {
        return null;
    }
}
