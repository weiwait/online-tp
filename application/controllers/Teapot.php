<?php
use base\ServiceFactory;
use base\DaoFactory;
use utils\Common;
use utils\Result;

include_once "MCommonController.php";

class TeapotController extends MCommonController
{
    //重复接口了
    public function getactionlogAction()
    {
        $this->getactionloglistAction();
    }

    public function getactionloglistAction()
    {
        //这个是从电器维度的，是不知道appid的
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        $page = trim($_REQUEST['page']);
        $pagesize = trim($_REQUEST['pagesize']);

        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        if ($pagesize < 1) {
            $pagesize = 10;
        }

        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("Teapot")->getActionLogNum($tpMachineid, $tpAppid);
        if (empty($total)) {
            $ret = array(
                "status" => "1",
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


        $data = ServiceFactory::getService("Teapot")->getActionLogList($tpMachineid, $tpAppid, $offset, $limit);
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

    public function actionlogAction()
    {
        //这个是从电器维度的，是不知道appid的
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = "";
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        $operation = trim($_REQUEST['operation']);
        $starttime = trim($_REQUEST['starttime']);
        $level = trim($_REQUEST['level']);
        $temp = trim($_REQUEST['temp']);
        $boil = trim($_REQUEST['boil']);
        $purify = trim($_REQUEST['purify']);
        $keepwarm = trim($_REQUEST['keepwarm']);
        $endtime = trim($_REQUEST['endtime']);
        $energy = trim($_REQUEST['energy']);
        $energy = strtoupper($energy);

        if (false !== strpos($energy, "KW")) {
            $energy = str_replace("KW", "", $energy);
            $energy = floatval($energy);
            $energy = $energy * 1000;
            $energy = $energy . "W";
        }

        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        //fei 这个不能严格要求的，切记
        /*
        if(empty($appid))
        {
            Result::showError("appid is empty");
        }
        */
        $operation = intval($operation);
        $boil = intval($boil);
        $purify = intval($purify);
        $keepwarm = intval($keepwarm);
        if (empty($starttime)) {
            Result::showError("starttime is empty");
        }
        if (empty($level)) {
            Result::showError("level is empty");
        }
        if (empty($temp)) {
            Result::showError("temp is empty");
        }
        if (empty($endtime)) {
            Result::showError("endtime is empty");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        $tpAppid = 0;
        if (!empty($appid) && "manual" != $appid) {
            $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
            if (empty($tpAppid)) {
                Result::showError("appid " . $appid . " have not reg");
            }

            $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
            if (!$flag) {
                Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
            }
        }

        ServiceFactory::getService("Machine")->active($tpMachineid);

        $ret = ServiceFactory::getService("Teapot")->actionLog($tpMachineid, $machineid, $tpAppid, $appid, $operation, $starttime, $level, $temp, $boil, $purify, $keepwarm, $endtime, $energy);
        if ($ret) {
            ServiceFactory::getService("TeapotStat")->updateStat($tpMachineid);
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    /**
     * @desc 更新状态
     */
    public function updatestateAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $level = trim($_REQUEST['level']);
        $temp = trim($_REQUEST['temp']);
        $hub = trim($_REQUEST['hub']);
        $state = trim($_REQUEST['state']);

        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        if (empty($level)) {
            Result::showError("level is empty");
        }
        if (empty($temp)) {
            Result::showError("temp is empty");
        }
        $state = intval($state);
        $hub = intval($hub);

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        ServiceFactory::getService("Machine")->active($tpMachineid);

        //更正last_tpappid
        if (0 == $state) {
            //fei 2014-11-26 这里就不去更正了，就让数据保留最后操作人的id
            //空闲
            //ServiceFactory::getService("Teapot")->updateLastTpappid($tpMachineid, 0, ""); 
        }

        if (1 != $state) {
            //fei 2015-01-14 当前状态不是加热，加热原来的状态是加热,那就说明加热完成，需要推送消息
            $lastStateDetail = ServiceFactory::getService("Teapot")->getState($tpMachineid);
            if ($lastStateDetail) {
                $lastState = intval($lastStateDetail['state']);
                if (1 == $lastState) {
                    //现在的状态不是加热，但是原来的状态是加热，这就说明加热完毕
                    $lastOrderid = ServiceFactory::getService("Teapot")->getLastOrderid($tpMachineid);
                    if ($lastOrderid) {
                        $orderDetailArray = ServiceFactory::getService("TeapotOrder")->getDetail($tpMachineid, $lastOrderid);

                        if ($orderDetailArray && 1 === intval($orderDetailArray['end_remind'])) {
                            $appid = $orderDetailArray['appid'];
                            $lastTpAppid = $orderDetailArray['tp_appid'];
                            if ($appid) {
                                $title = "水壶加热完毕";
                                $content = "";
                                if ($lastTpAppid) {
                                    //FIXME 这里不一样的！！！
                                    ServiceFactory::getService("PushMsg")->add("teapot_end_remind", $lastTpAppid, $appid, $title, $content, $tpMachineid);
                                }

                            }
                        }
                    }
                }
            }
        }

        $ret = ServiceFactory::getService("Teapot")->updateState($tpMachineid, $machineid, $level, $temp, $hub, $state);
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function requestresultAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $orderid = trim($_REQUEST['orderid']);
        $result = trim($_REQUEST['result']);

        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        if (empty($orderid)) {
            Result::showError("orderid is empty");
        }
        if (empty($result)) {
            Result::showError("result is empty");
        }

        $result = strtolower($result);
        if (!in_array($result, array("orderok", "cancelorderok", "stopheatok"))) {
            Result::showError("result " . $result . " is illegal, eg: orderok, cancelorderok, stopheatok");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        ServiceFactory::getService("Machine")->active($tpMachineid);

        //检查orderid是否存在
        $flag = ServiceFactory::getService("TeapotOrder")->isExist($tpMachineid, $orderid);
        if (!$flag) {
            Result::showError("orderid " . $orderid . " is not exist");
        }

        $flag = ServiceFactory::getService("TeapotOrder")->isDone($tpMachineid, $orderid);
        if ($flag) {
            Result::showError("orderid " . $orderid . " have been done");
        }

        $ret = ServiceFactory::getService("TeapotOrder")->requestResult($tpMachineid, $orderid, $result);
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function heatAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        $temp = trim($_REQUEST['temp']);
        $boil = trim($_REQUEST['boil']);
        $purify = trim($_REQUEST['purify']);
        $keepwarm = trim($_REQUEST['keepwarm']);
        $heattime = trim($_REQUEST['heattime']);
        $costtime = intval($_REQUEST['costtime']);
        $week = trim($_REQUEST['week']);
        $orderid = trim($_REQUEST['orderid']);

        //消息推送
        $startRemind = trim($_REQUEST['startremind']);
        $endRemind = trim($_REQUEST['endremind']);
        $noWaterRemind = trim($_REQUEST['nowaterremind']);
        $noWaterRemindThreshold = trim($_REQUEST['nowaterremindthreshold']);

        if ($startRemind) {
            $startRemind = 1;
        } else {
            $startRemind = 0;
        }
        if ($endRemind) {
            $endRemind = 1;
        } else {
            $endRemind = 0;
        }
        if ($noWaterRemind) {
            $noWaterRemind = 1;
        } else {
            $noWaterRemind = 0;
        }
        $noWaterRemindThreshold = floatval($noWaterRemindThreshold);

        $boil = intval($boil);
        $purify = intval($purify);
        $keepwarm = intval($keepwarm);
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($temp)) {
            Result::showError("temp is empty");
        }
        if (empty($costtime)) {
            Result::showError("costtime is empty");
        }
        //week
        if (7 != strlen($week) && 1 != strlen($week)) {
            Result::showError("week " . $week . " is illegal, week strlen should be 7");
        }

        if (7 == strlen($week)) {
            for ($i = 0; $i < 7; ++$i) {
                if ("0" != $week[$i] && "1" != $week[$i]) {
                    Result::showError("week " . $week . " is illegal");
                }
            }
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        }

        //fei 2014-10-09 假如是立刻加热，那就是需要判断热水壶的状态当前是否加热中，假如是假如加热中则返回失败
        if ("0" == $heattime) {
            //立即加热
            if (false == ServiceFactory::getService("Teapot")->canStartToHeat($tpMachineid)) {
                //当前状态不允许加热 
                Result::showError("machineid " . $machineid . " is busy");
            }
        } else {
            // 判断预约是否重叠了
            // costtime只影响预约
            if (false == ServiceFactory::getService("TeapotOrder")->canOrder($tpMachineid, $week, $heattime, $costtime)) {
                //该时间段已经被预约
                Result::showError("in this time machineid " . $machineid . " have been ordered");
            }
        }

        //fei 2014-11-15 orderid可以由app生成
        if (empty($orderid)) {
            $orderid = ServiceFactory::getService("TeapotOrder")->createOrderid($tpMachineid);
            if (empty($orderid)) {
                Result::showError("system error, createOrderId fail");
            }
        }

        //加入SCCKET功能
        if ($heattime == 0) {
            $_REQUEST['operation'] = 1;
            $_REQUEST['orderid'] = $orderid;
            $this->startMachine(false);
        }

        //增加order
        $ret = ServiceFactory::getService("TeapotOrder")->add($tpMachineid, $orderid, $machineid, $tpAppid, $appid, $temp, $boil,
            $purify, $keepwarm, $heattime, $costtime, $week, "heat",
            $startRemind, $endRemind, $noWaterRemind, $noWaterRemindThreshold
        );
        if ($ret) {
            //更新最后加热时间
            if (0 == $heattime) {
                ServiceFactory::getService("Teapot")->updateLastHeatTime($tpMachineid);

                //服务端更新状态，其实这个是不准的,app端会出现 加热 ->空闲-->加热的短暂乱现象
                ServiceFactory::getService("Teapot")->updateStateToHeating($tpMachineid, $machineid);

                //更新最后操作的appid
                //fei 2015-04-12 这里不能注释
                ServiceFactory::getService("Teapot")->updateLastTpappid($tpMachineid, $tpAppid, $orderid);
            } else {
                //TODO 预约的怎么样更新数据库的那2个字段呢???
            }

            //Result::showOk($orderid);
            //fei 2014-10-03 app要求返回当前的状态,不过这个状态不代表接受命令后的状态 
            $data = ServiceFactory::getService("Teapot")->getState($tpMachineid);
            if ($data) {
                $onlineFlag = ServiceFactory::getService("Machine")->isActive($tpMachineid);
                $data['appid'] = $appid;
                $data['orderid'] = $orderid;
                $data['isonline'] = $onlineFlag ? "online" : "offline";
                $ret = array(
                    "status" => 1,
                    "data" => $data,
                );
                $ret = json_encode($ret);
                Result::output($ret);
            } else {
                Result::showError("system error");
            }
        } else {
            Result::showError("system error");
        }
    }

    public function stopheatAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        $orderid = trim($_REQUEST['orderid']);
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        }

        //TODO这里需要判断是否是他触发的加热
        $targetOrderid = "";
        $rawTpappid = ServiceFactory::getService("TeapotOrder")->getCurrHeatTpappidByDb($tpMachineid, $targetOrderid);
        if (empty($rawTpappid)) {
            Result::showError("no app order machineid " . $machineid . " to heat");
        }

        if ($rawTpappid != $tpAppid) {
            Result::showError("You can not stop this heat action, it is ordered by other app[" . $rawTpappid . "]");
        }

        if (empty($orderid)) {
            $orderid = ServiceFactory::getService("TeapotOrder")->createOrderid($tpMachineid);
            if (empty($orderid)) {
                Result::showError("system error, createOrderId fail");
            }
        }

        //加入SOCKET命令
        $_REQUEST['operation'] = 1;
        $_REQUEST['orderid'] = $orderid;
        $this->stopMachine(false);

        //fei 2014-10-04 将-999 改成0
        //$tpMachineid, $orderid, $machineid, $tpAppid, $appid, $temp, $boil, $purify, $keepwarm, $heattime, $costtime, $week, $action
        $ret = ServiceFactory::getService("TeapotOrder")->add($tpMachineid, $orderid, $machineid, $tpAppid, $appid, "0", 0, 0, 0, 0, 0, 0, "stopheat");
        if ($ret) {

            //更新最后加热时间
            ServiceFactory::getService("Teapot")->updateLastHeatTime($tpMachineid);

            //Result::showOk($orderid);
            //fei 2014-10-03 app要求返回当前的状态,不过这个状态不代表接受命令后的状态 
            $data = ServiceFactory::getService("Teapot")->getState($tpMachineid);
            if ($data) {
                $ret = array(
                    "status" => 1,
                    "data" => $data,
                );
                $ret = json_encode($ret);
                Result::output($ret);
            } else {
                Result::showError("system error");
            }
        } else {
            Result::showError("system error");
        }
    }

    public function cancelheatAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        $orderid = trim($_REQUEST['orderid']);
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }
        if (empty($orderid)) {
            Result::showError("orderid is empty");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        $detail = ServiceFactory::getService("TeapotOrder")->getDetail($tpMachineid, $orderid);
        if (!$detail) {
            Result::showError("orderid " . $orderid . " is not exist");
        }

        if ($detail['appid'] != $appid) {
            Result::showError("You do not have permission to stopheat orderid " . $orderid . ", it is created by other appid");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        }

        //fei 2014-10-12 这里现在只是修改了数据库，没有通知teapot
        //fei 2014-11-26 这里修改成通知电器
        $ret = ServiceFactory::getService("TeapotOrder")->cancelOrder($tpMachineid, $tpAppid, $orderid);
        if ($ret) {
            ServiceFactory::getService("Teapot")->updateLastHeatTime($tpMachineid);
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    public function getstateAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $data = ServiceFactory::getService("Teapot")->getState($tpMachineid);
        if ($data) {

            //fei 2014-11-19 获取对应的appid
            $onlineFlag = ServiceFactory::getService("Machine")->isActive($tpMachineid);

            $targetOrderid = "";
            $currAppid = "";
            $currTpAppid = ServiceFactory::getService("TeapotOrder")->getCurrHeatTpappidByDb($tpMachineid, $targetOrderid);
            if (!empty($currTpAppid)) {
                $currAppid = ServiceFactory::getService("App")->getAppid($currTpAppid);
            }

            $data['isonline'] = $onlineFlag ? "online" : "offline";
            $data['appid'] = $currAppid;
            $data['orderid'] = $targetOrderid;

            $ret = array(
                "status" => 1,
                "data" => $data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
        } else {
            Result::showError("system error");
        }
    }

    public function getorderlistAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        $page = trim($_REQUEST['page']);
        $pagesize = trim($_REQUEST['pagesize']);
        $page = intval($page);
        $pagesize = intval($pagesize);

        if ($page < 1) {
            $page = 1;
        }
        if ($pagesize < 1) {
            $pagesize = 10;
        }

        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        if (empty($appid)) {
            Result::showError("appid is empty");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            Result::showError("appid " . $appid . " have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if (!$flag) {
            Result::showError("appid " . $appid . " have not bind machineid " . $machineid . "");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("TeapotOrder")->getOrderNum($tpMachineid, $tpAppid);
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
        if ($page > $allPage) {
            $page = $allPage;
        }
        $offset = ($page - 1) * $pagesize;
        $limit = $pagesize;

        $data = ServiceFactory::getService("TeapotOrder")->getOrderList($tpMachineid, $tpAppid, $offset, $limit);
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
        } else {
            Result::showError("system error");
        }
    }

    public function requestAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        //$appid = $_REQUEST['appid'];
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
        /*
        if(empty($appid))
        {
            Result::showError("appid is empty");
        }
        */
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        /*
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg"); 
        }
        */
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        /*
        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have bind machineid ".$machineid."");
        }
        */

        ServiceFactory::getService("Machine")->active($tpMachineid);

        $total = ServiceFactory::getService("TeapotOrder")->getOrderNum($tpMachineid);
        if ($total <= 0) {
            $ret = array(
                "status" => "1",
                "total" => "0",
                "page" => trim($page),
                "pagesize" => trim($pagesize),
                "apponlineflag" => trim(ServiceFactory::getService("Machine")->haveAppOnline($tpMachineid)),
                "data" => array(),
            );
            $ret = json_encode($ret);
            Result::output($ret);
            return true;
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
                "status" => "1",
                "total" => trim($total),
                "page" => trim($page),
                "pagesize" => trim($pagesize),
                "apponlineflag" => trim(ServiceFactory::getService("Machine")->haveAppOnline($tpMachineid)),
                "data" => $data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
        } else {
            Result::showError("system error");
        }
    }

    public function statAction()
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
            Result::showError("appid " . $appid . " have bind machineid " . $machineid . "");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $data = ServiceFactory::getService("TeapotStat")->getStat($tpMachineid);
        if ($data) {
            Result::showOk($data);
        } else {
            Result::showError("system error");
        }
    }

    /**
     * @desc 热水壶回调服务器(反馈)
     */
    public function runtimeAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        $state = $_REQUEST['state']; //0    //1:预约开始  2：手动取消  3：没有开始（即预约时间到来时，电器正在工作中） 
        $orderid = $_REQUEST['orderid'];
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

        /*
        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have bind machineid ".$machineid."");
        }
        */

        ServiceFactory::getService("Machine")->active($tpMachineid);

        if (1 == $state) {
            //预约开始
            ServiceFactory::getService("Teapot")->updateLastTpappid($tpMachineid, $tpAppid, $orderid);

            //开启 启动推送才通知的
            //FIXME 这里应该再做判断，全部交给app_machine_config控制
            //$orderDetailArray = ServiceFactory::getService("TeapotOrder")->getDetail($tpMachineid, $orderid);
            //if(!empty($orderDetailArray) && 1 === intval($orderDetailArray['start_remind']))
            //{
            ServiceFactory::getService("PushMsg")->pushTeapotStart($tpAppid, $tpMachineid);
            //}
        }

        $data = ServiceFactory::getService("Teapot")->runtime($tpMachineid, $tpAppid, $orderid, $state);
        if ($data) {

            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }

    }

    /**
     * 获取控制电器的参数值
     * @return mixed
     */
    function getControlData()
    {
        //        if (!trim($_REQUEST['lightness'])) {
        //            $lastControlData = $this->getService()->getState($this->getSingleTPMachineid());
        //            $lightness = $lastControlData['lightness'];
        //            $temperature = $lastControlData['temperature'];
        //            $red = $lastControlData['red'];
        //            $green = $lastControlData['green'];
        //            $blue = $lastControlData['blue'];
        //        } else {
        //            $lightness = trim($_REQUEST['lightness']);
        //            $temperature = trim($_REQUEST['temperature']);
        //            $red = trim($_REQUEST['red']);
        //            $green = trim($_REQUEST['green']);
        //            $blue = trim($_REQUEST['blue']);
        //        }
        $appid = $this->getParam('appid', '0');
        $temp = $this->getParam('temp', '0');
        $boil = $this->getParam('boil', '0');
        $purify = $this->getParam('purify', '0');
        $keepwarm = $this->getParam('keepwarm', '0');
        $operation = $this->getParam('operation', '0');
        $orderid = $this->getParam('orderid', '0');
        return array(
            "appid" => $appid,
            "temp" => $temp,
            "boil" => $boil,
            "purify" => $purify,
            "keepwarm" => $keepwarm,
            "operation" => $operation,
            "orderid" => $orderid
        );
        // operation字段表示命令来自哪里： 0表示手动 1 表示APP 2 表示预约；
        // temp字段表示加热温度：0~100（摄氏度）；
        // boil表示是否煮沸：0 表示不煮沸；1表示煮沸；
        // purify表示净化时间：0~10（分钟）；0表示不净化；
        // keepwarm表示保温时间：0~60（分钟）；
        // appid表示手机ID号；
    }
}
