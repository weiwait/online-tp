<?php
use base\ServiceFactory;
use base\DaoFactory;
use utils\Common;
use utils\Result;

class HumidifierController extends FrontController {
	/**
	 * 初始化
	 */
	public function init() 
    {
        //check_admin();
		parent::init ();
	}

    /**
     * @desc 保存配置
     */
    public function saveconfigAction()
    {
        global $globalTpAppid, $globalTpMachineid;

        $machineid = trim($_REQUEST['machineid']); 
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }

        $appid = trim($_REQUEST['appid']); 
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg");
        }
        //用于调试的
        $globalTpMachineid = $tpMachineid;
        $globalTpAppid = $tpAppid;

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have not bind machineid ".$machineid."");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        //$summerGrade1Top = trim($_REQUEST['summergrade1top']);
        //$summerGrade2Top = trim($_REQUEST['summergrade2top']);
        //$summerGrade3Top = trim($_REQUEST['summergrade3top']);
        //$summerGrade1Bottom = trim($_REQUEST['summergrade1bottom']);
        //$summerGrade2Bottom = trim($_REQUEST['summergrade2bottom']);
        //$summerGrade3Bottom = trim($_REQUEST['summergrade3bottom']);

        //$winterGrade1Top = trim($_REQUEST['wintergrade1top']);
        //$winterGrade2Top = trim($_REQUEST['wintergrade2top']);
        //$winterGrade3Top = trim($_REQUEST['wintergrade3top']);
        //$winterGrade1Bottom = trim($_REQUEST['wintergrade1bottom']);
        //$winterGrade2Bottom = trim($_REQUEST['wintergrade2bottom']);
        //$winterGrade3Bottom = trim($_REQUEST['wintergrade3bottom']);

        $drymode = trim($_REQUEST['drymode']);
        $wetmode = trim($_REQUEST['wetmode']);
        //$enableAi = trim($_REQUEST['enableai']);
        $enableUserNearStart = trim($_REQUEST['enableusernearstart']);
        $enableUserFarStop = trim($_REQUEST['enableuserfarstop']);

        //消息推送
        $startAndStopRemind = trim($_REQUEST['startandstopremind']);
        $noWaterRemind = trim($_REQUEST['nowaterremind']);
        $tooDryRemind = trim($_REQUEST['toodryremind']);
        if($startAndStopRemind)
        {
            $startAndStopRemind = 1;
        }
        else
        {
            $startAndStopRemind = 0;
        }
        
        if($noWaterRemind)
        {
            $noWaterRemind = 1;
        }
        else
        {
            $noWaterRemind = 0;
        }

        if($tooDryRemind)
        {
            $tooDryRemind = 1;
        }
        else
        {
            $tooDryRemind = 0;
        }

        $enableAi = $enableAi?1:0;
        $enableUserNearStart = $enableUserNearStart?1:0;
        $enableUserFarStop = $enableUserFarStop?1:0;

        $data = array(
            "tp_machineid"=>$tpMachineid,
            "drymode"=>$drymode,
            "wetmode"=>$wetmode,
            //"enable_ai"=>$enableAi,
            //"enable_user_near_start"=>$enableUserNearStart,
            //"enable_user_far_stop"=>$enableUserFarStop,
            "last_update_time"=>time(),
            //"start_and_stop_remind"=>$startAndStopRemind,
            //"no_water_remind"=>$noWaterRemind,
        );

        $flag = ServiceFactory::getService("Humidifier")->update($tpMachineid, $data); 
        if($flag)
        {
            ServiceFactory::getService("App")->updateConfig($tpAppid, $tpMachineid, array(
                "humidifier_no_water_remind"=>$noWaterRemind,
                "humidifier_start_remind"=>$startAndStopRemind,
                "humidifier_end_remind"=>$startAndStopRemind,
                "humidifier_too_dry_remind"=>$tooDryRemind,
                "enable_user_near_start"=>$enableUserNearStart,
                "enable_user_far_stop"=>$enableUserFarStop,
            ));
            Result::showOk("ok");
        }
        else
        {
            Result::showError("system error");
        }
    }

    /**
     * @desc 获取配置
     */
    public function getconfigAction()
    {
        global $globalTpAppid, $globalTpMachineid;
        $machineid = trim($_REQUEST['machineid']); 
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }

        $appid = trim($_REQUEST['appid']); 
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have not bind machineid ".$machineid."");
        }
        $globalTpMachineid = $tpMachineid;
        $globalTpAppid = $tpAppid;

        ServiceFactory::getService("App")->active($tpAppid);

        
        $data = ServiceFactory::getService("Humidifier")->getConfig($tpMachineid);
        if($data)
        {
            //在这里重新获取app的智能配置
            $startStopDetail = ServiceFactory::getService("App")->getHumidifierStartStop($tpAppid, $tpMachineid);
            $data['enableusernearstart'] = $startStopDetail['enableUserNearStart'];
            $data['enableuserfarstop'] = $startStopDetail['enableUserFarStop'];
            $data['toodryremind'] = $startStopDetail['tooDryRemind'];
            $data['nowaterremind'] = $startStopDetail['noWaterRemind'];
            $data['startandstopremind'] = $startStopDetail['startAndStopRemind'];
            $data['enableai'] = 0;

            Result::showOk($data);
        }
        else
        {
            Result::showError("system error");
        }
    }

    /**
     * @desc 电器来获取是否需要开启
     */
    public function requestAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }

        ServiceFactory::getService("Machine")->active($tpMachineid);
        
        $detail = ServiceFactory::getService("Humidifier")->getWork($tpMachineid);

        //心跳快慢速模式
        if(empty($detail['top']))
        {
            $detail['top'] = '70%';
        }
        if(empty($detail['bottom']))
        {
            $detail['bottom'] = '40%';
        }

        Result::showOk($detail);
    }

    /**
     * @desc 关闭加湿器
     */
    public function stopAction()
    {
        $machineid = trim($_REQUEST['machineid']); 
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }

        $appid = trim($_REQUEST['appid']); 
        $tpAppid = 0;
        if($appid != "00000000-0000-0000-0000-000000000000")
        {
            $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
            if(empty($tpAppid))
            {
                Result::showError("appid ".$appid." have not reg");
            }

            $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
            if(!$flag)
            {
                Result::showError("appid ".$appid." have not bind machineid ".$machineid."");
            }

            ServiceFactory::getService("App")->active($tpAppid);
        }

        //停止工作
        //$flag = ServiceFactory::getService("Humidifier")->stopWork($tpMachineid);
        $data = array(
            "tp_machineid"=>$tpMachineid,
            "run"=>0,
            "starttime"=>0,
            "grade"=>0,
            "anion"=>0,
        );
        $flag = ServiceFactory::getService("Humidifier")->addWork($tpMachineid, $data);
        if($flag)
        {
            if(0 != $tpAppid)
            {
                //ServiceFactory::getService("Humidifier")->updateStateOnly($tpMachineid, 0);

                //推送关闭消息
                //ServiceFactory::getService("PushMsg")->pushHumidifierStop($tpMachineid);
            }
            Result::showOk("ok");
        }
        else
        {
            Result::showError("system error");    
        }
    
    }

    /**
     * @desc 启动加湿器
     */
    public function startAction()
    {
        $machineid = trim($_REQUEST['machineid']); 
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }

        $appid = $_REQUEST['appid']; 
        //假如是手动模式 tpAppid就是0
        $tpAppid = 0;
        if($appid != "00000000-0000-0000-0000-000000000000")
        {
            $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
            if(empty($tpAppid))
            {
                Result::showError("appid ".$appid." have not reg");
            }

            $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
            if(!$flag)
            {
                Result::showError("appid ".$appid." have not bind machineid ".$machineid."");
            }

            ServiceFactory::getService("App")->active($tpAppid);
            //$isSummerMode = trim($_REQUEST['issummermode']);
            $grade = trim($_REQUEST['grade']);
            $grade = intval($grade);
            $anion = trim($_REQUEST['anion']);
            $anion = intval($anion);
            $delay = trim($_REQUEST['delay']);
        }
        else
        {
            //手工开的，默认是开负离子
            $grade = 3;
            $anion = 1; 
            $delay = 0;
        }

        if($grade < 1)
        {
            $grade = 1;
        }
        $starttime = time() + intval($delay);
        $run = 0;
        if(0 == $delay)
        {
            $run = 1; 
        }
   
        $data = array(
            "tp_machineid"=>$tpMachineid,
            "run"=>$run,
            "starttime"=>$starttime,
            "grade"=>$grade,
            "anion"=>$anion,
            //"is_summer_mode"=>$isSummerMode,
        );
        $flag = ServiceFactory::getService("Humidifier")->addWork($tpMachineid, $data);
        if($flag)
        {
            ServiceFactory::getService("Humidifier")->setLastGrade($tpMachineid, $grade, $anion);
            ServiceFactory::getService("Humidifier")->updateStateOnly($tpMachineid, 1, $tpAppid, $appid);
            Result::showOk("ok");
        }
        else
        {
            Result::showError("system error");    
        }
    }

    /**
     * @desc 获取使用使用记录列表
     */
    public function getactionloglistAction()
    {
        //这个是从电器维度的，是不知道appid的
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        $page = trim($_REQUEST['page']);
        $pagesize = trim($_REQUEST['pagesize']);

        $page = intval($page);
        if($page < 1)
        {
            $page = 1;
        }
        if($pagesize < 1)
        {
            $pagesize = 10; 
        }

        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }
        if(empty($appid))
        {
            Result::showError("appid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have not bind machineid ".$machineid."");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("Humidifier")->getActionLogNum($tpMachineid, $tpAppid);
        if(empty($total))
        {
            $ret = array(
                "status"=>"1",
                "total"=>0,
                "page"=>$page,
                "pagesize"=>$pagesize,
                "data"=>array(),
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }
        $allPage = ceil($total/$pagesize);
        if($page > $allPage)
        {
            $page = $allPage; 
        }
        $offset = ($page - 1) * $pagesize;
        $limit = $pagesize;

        $data = ServiceFactory::getService("Humidifier")->getActionLogList($tpMachineid, $tpAppid, $offset, $limit);
        if($data)
        {
            $ret = array(
                "status"=>1,
                "total"=>$total,
                "page"=>$page,
                "pagesize"=>$pagesize,
                "data"=>$data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }
        else
        {
            Result::showError("system error");
        }
    
    }

    /**
     * @desc 保存使用记录
     */
	public function actionlogAction() 
    {
        //这个是从电器维度的，是不知道appid的
        \Yaf_Dispatcher::getInstance()->disableView();
        //Result::showOk("ok");
        //die;
        $appid = "";
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        $operation = trim($_REQUEST['operation']);
        $starttime = trim($_REQUEST['starttime']);
        $endtime = trim($_REQUEST['endtime']);
        $startlevel = trim($_REQUEST['startlevel']);
        $endlevel = trim($_REQUEST['endlevel']);
        $tophumidity = trim($_REQUEST['tophumidity']);
        $middlehumidity = trim($_REQUEST['middlehumidity']);
        $bottomhumidity = trim($_REQUEST['bottomhumidity']);
        $starthumidity = trim($_REQUEST['starthumidity']);
        $endhumidity = trim($_REQUEST['endhumidity']);
        $energy = trim($_REQUEST['energy']);

        $energy = strtoupper($energy);
        if(false !== strpos($energy, "KW"))
        {
            $energy = str_replace("KW", "", $energy); 
            $energy = floatval($energy);
            $energy = $energy * 1000;
            $energy = $energy . "W";
        }

        if(empty($machineid))
        {
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
        if(empty($starttime))
        {
            //Result::showError("starttime is empty");
            Result::showOk("ok");
        }
        if(empty($endtime))
        {
            Result::showError("endtime is empty");
        }
        if(empty($startlevel))
        {
            Result::showError("startlevel is empty");
        }
        if(empty($endlevel))
        {
            Result::showError("endlevel is empty");
        }
        if(empty($starthumidity))
        {
            Result::showError("starthumidity is empty");
        }
        if(empty($endhumidity))
        {
            Result::showError("endhumidity is empty");
        }
        if(empty($tophumidity))
        {
            Result::showError("tophumidity is empty");
        }
        if(empty($middlehumidity))
        {
            Result::showError("middlehumidity is empty");
        }
        if(empty($bottomhumidity))
        {
            Result::showError("bottomhumidity is empty");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }

        $tpAppid = 0;
        if(!empty($appid))
        {
            $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
            if(empty($tpAppid))
            {
                Result::showError("appid ".$appid." have not reg");
            }

            $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
            if(!$flag)
            {
                Result::showError("appid ".$appid." have not bind machineid ".$machineid."");
            }
        }

        ServiceFactory::getService("Machine")->active($tpMachineid);

        //actionLog($tpMachineid, $machineid, $tpAppid, $appid, $operation, $starttime, $costtime, $humidity, $energy)

        $realStartTime = 0;

        $costtime = $this->calcCostTime($starttime, $endtime, $realStartTime);
        $humidity = "".intval($bottomhumidity)."%~".intval($tophumidity)."%";

        if(empty($appid))
        {
            $lastAppid = ServiceFactory::getService("Humidifier")->getLastAppid($tpMachineid); 
            if("00000000-0000-0000-0000-000000000000" == $lastAppid)
            {
                $operation = 0; 
            }
            else if("11111111-0000-1111-1111-111111111111" == $lastAppid)
            {
                //智能开 
                $operation = 3;
            }
            else if("11111111-1111-1111-1111-000000000000" == $lastAppid)
            {
                //预约开的
                $operation = 2;
            }
        }

        //添加使用记录
        $ret = ServiceFactory::getService("Humidifier")->actionLog($tpMachineid, $machineid, $tpAppid, $appid, $operation, $realStartTime, $costtime, $humidity, $energy);
        if($ret)
        {
            //ServiceFactory::getService("HumidifierStat")->updateStat($tpMachineid);
            Result::showOk("ok");
        }
        else
        {
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
        $humidity = trim($_REQUEST['humidity']);
        $level = trim($_REQUEST['level']);
        $state = trim($_REQUEST['state']);

        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }
        if(empty($humidity))
        {
            Result::showError("humidity is empty");
        }
        if(empty($level))
        {
            Result::showError("level is empty");
        }
        $state = intval($state);

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }

        ServiceFactory::getService("Machine")->active($tpMachineid);

        //fei 2015-03-01 这里这里添加消息推送

        //获取电器原来的状态
        $oldStateArray = ServiceFactory::getService("Humidifier")->getState($tpMachineid);
        $oldState = intval($oldStateArray['state']);
        $lastStartTime = intval($oldStateArray['laststarttime']);
        $lastTpAppid = intval($oldStateArray['lasttpappid']);
        $lastAppid = intval($oldStateArray['lastappid']);

        $oldStateArray['level'] = str_replace(array("l", "L"), array("", ""), $oldStateArray['level']);
        $lastLevel = intval($oldStateArray['level']);

        //file_put_contents("/tmp/pushmsg", date("Y-m-d H:is")." new state=".$state.", old state=".$oldState."\n", FILE_APPEND);

        //更新状态
        $ret = ServiceFactory::getService("Humidifier")->updateState($tpMachineid, $machineid, $humidity, $level, $state);
        if($ret)
        {
            Result::showOk("ok");
        }
        else
        {
            Result::showError("system error");
        }
    }

    /**
     * @desc 获取加湿器状态
     */
    public function getstateAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }
        if(empty($appid))
        {
            Result::showError("appid is empty");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg");
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg");
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have not bind machineid ".$machineid."");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $data = ServiceFactory::getService("Humidifier")->getState($tpMachineid);
        if($data)
        {
            $ret = array(
                "status"=>1,
                "data"=>$data,
            ); 
            $ret = json_encode($ret); 
            Result::output($ret);
        }
        else
        {
            Result::showError("system error");
        }
    }

    /**
     * @desc 获取加湿器统计信息
     */
    public function statAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        if(empty($appid))
        {
            Result::showError("appid is empty");
        }
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg"); 
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg"); 
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have bind machineid ".$machineid."");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $data = ServiceFactory::getService("HumidifierStat")->getStat($tpMachineid);
        if($data)
        {
            Result::showOk($data); 
        }
        else
        {
            Result::showError("system error"); 
        }
    }

    /**
     * @desc 添加预约
     */
    public function orderAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        $heattime = $_REQUEST['heattime'];
        $week = $_REQUEST['week'];
        $action = trim($_REQUEST['action']);
        $grade = intval($_REQUEST['grade']);
        $anion = intval($_REQUEST['anion']);
        $startRemind = $_REQUEST['startremind'];
        $endRemind = $_REQUEST['endremind'];
        $noWaterRemind = $_REQUEST['nowaterremind'];
        $orderid = trim($_REQUEST['orderid']);

        if(7 != strlen($week))
        {
            Result::showError("week length must be 7 char");
        }

        if(6 != strlen($heattime))
        {
            Result::showError("heattime length must be 6 char, not support 0");
        }

        $action = strtolower($action);
        if(!in_array($action, array("run", "stop")))
        {
            Result::showError("bad action '".$action."'");
        }

        if(empty($anion))
        {
            $anion = 0; 
        }
        else
        {
            $anion = 1;
        }

        if(empty($appid))
        {
            Result::showError("appid is empty");
        }
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg"); 
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg"); 
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have bind machineid ".$machineid."");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        //fei 2014-11-15 orderid可以由app生成
        if(empty($orderid))
        {
            $orderid = ServiceFactory::getService("HumidifierOrder")->createOrderid($tpMachineid);
            if(empty($orderid))
            {
                Result::showError("system error, createOrderId fail");
            }
        }

        $data = ServiceFactory::getService("HumidifierOrder")->add($tpMachineid, $machineid, $tpAppid, $appid, $orderid, $heattime, $week, $action, $grade, $startRemind, $endRemind, $noWaterRemind, $anion);
        if($data)
        {
            $ret = array(
                "status"=>1,
                "data"=>"ok",
                "orderid"=>$orderid,
            );
            $ret = json_encode($ret);
            Result::output($ret);
        }
        else
        {
            Result::showError("system error"); 
        }
    }

    /**
     * @desc 删除预约
     */
    public function cancelorderAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        $orderid = trim($_REQUEST['orderid']);

        if(empty($appid))
        {
            Result::showError("appid is empty");
        }
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg"); 
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg"); 
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have bind machineid ".$machineid."");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        if(!ServiceFactory::getService("HumidifierOrder")->isExist($tpMachineid, $orderid))
        {
            Result::showError("order ".$orderid." is not exist");
        }
   
        $data = ServiceFactory::getService("HumidifierOrder")->cancelOrder($tpMachineid, $tpAppid, $orderid);
        if($data)
        {
            Result::showOk("ok"); 
        }
        else
        {
            Result::showError("system error"); 
        }
    }

    /**
     * @desc 获取预约的详情
     */
    public function getorderAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        $orderid = trim($_REQUEST['orderid']);

        if(empty($appid))
        {
            Result::showError("appid is empty");
        }
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg"); 
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg"); 
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have bind machineid ".$machineid."");
        }

        ServiceFactory::getService("App")->active($tpAppid);
   
        $data = ServiceFactory::getService("HumidifierOrder")->getDetail($tpMachineid, $orderid);
        if($data)
        {
            Result::showOk($data); 
        }
        else
        {
            Result::showError("system error"); 
        }
    }

    /**
     * @desc 获取预约列表
     */
    public function getorderlistAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        $page = trim($_REQUEST['page']);
        $pageSize = trim($_REQUEST['pagesize']);

        $page = intval($page);
        $pageSize = intval($pageSize);
        if($page < 1)
        {
            $page = 1;
        }
        if($pageSize < 1)
        {
            $pageSize = 10;
        }

        $total = 0;

        if(empty($appid))
        {
            Result::showError("appid is empty");
        }
        if(empty($machineid))
        {
            Result::showError("machineid is empty");
        }

        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if(empty($tpAppid))
        {
            Result::showError("appid ".$appid." have not reg"); 
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if(empty($tpMachineid))
        {
            Result::showError("machineid ".$machineid." have not reg"); 
        }

        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $tpMachineid);
        if(!$flag)
        {
            Result::showError("appid ".$appid." have bind machineid ".$machineid."");
        }

        ServiceFactory::getService("App")->active($tpAppid);

        $total = ServiceFactory::getService("HumidifierOrder")->getOrderNum($tpMachineid, $tpAppid);
        if(empty($total))
        {
            $ret = array(
                "status"=>1,
                "page"=>$page,
                "pagesize"=>$pageSize,
                "total"=>0,
                "data"=>array(),
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }

        $allPage = ceil($total/$pageSize);
        if($page > $allPage)
        {
            $page = $allPage;
        }

        $offset = ($page - 1)*$pageSize;
        $limit = $pageSize;
   
        $data = ServiceFactory::getService("HumidifierOrder")->getOrderList($tpMachineid, $tpAppid, $offset, $limit);
        if($data)
        {
            $ret = array(
                "status"=>1,
                "page"=>$page,
                "pagesize"=>$pageSize,
                "total"=>$total,
                "data"=>$data,
            );
            $ret = json_encode($ret);
            Result::output($ret);
            die;
        }
        else
        {
            Result::showError("system error"); 
        }
    }

    private function calcCostTime($startTime, $endTime, &$realStartTime)
    {

        $ymd = date("Y-m-d");
        
        $startHour = intval(substr($startTime, 0, 2)); 
        $startMin = intval(substr($startTime, 2, 2)); 
        $startSec = intval(substr($startTime, 4, 2)); 

        $endHour = intval(substr($endTime, 0, 2)); 
        $endMin = intval(substr($endTime, 2, 2)); 
        $endSec = intval(substr($endTime, 4, 2)); 

        $start = strtotime($ymd." ".$startHour.":".$startMin.":".$startSec."");
        $end = strtotime($ymd." ".$endHour.":".$endMin.":".$endSec."");

        $realStartTime = $start;

        $ret = $end - $start;

        if($ret < 0)
        {
            $ret += 86400; 
        }
        return $ret;
    }

}

