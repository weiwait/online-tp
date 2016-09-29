<?php
use base\ServiceFactory;

include_once "MCommonController.php";

class AdminController extends MCommonController
{
    public function syncmapdataAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        ServiceFactory::getService("Machine")->syncMapData();
        echo "ok";
    }

    public function appmapdataAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        //status,在线状态，1在线，2不在线，默认全部.2015/11/30增加在线过滤
        $status = $_GET['status'];
        $total = 0;
        $data = ServiceFactory::getService("App")->getAllMapData($status);
        /*
        for($i=0; $i<$total; $i++)
        {
            $data[] = array(floatval(rand(74, 134).".".rand(100, 999)), floatval(rand(24, 39).".".rand(100, 999)), 1);
        }
        */
        $total = count($data);
        $ret = array(
            "data" => $data,
            "total" => $total,
            "rt_loc_cnt" => 47764510,
            "errorno" => 0,
            "NearestTime" => "2014-08-29 15:20:00",
            "userTime" => "2014-08-29 15:32:11"
        );
        echo "var data = " . json_encode($ret);
    }

    public function machinemapdataAction()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
        //status,在线状态，1在线，2不在线，默认全部.2015/11/30增加在线过滤
        $status = $_GET['status'];
        $total = 0;
        $data = ServiceFactory::getService("Machine")->getAllMapData($status);
        /*
        for($i=0; $i<$total; $i++)
        {
            $data[] = array(floatval(rand(74, 134).".".rand(100, 999)), floatval(rand(24, 39).".".rand(100, 999)), 1);
        }
        */
        $total = count($data);
        $ret = array(
            "data" => $data,
            "total" => $total,
            "rt_loc_cnt" => 47764510,
            "errorno" => 0,
            "NearestTime" => "2014-08-29 15:20:00",
            "userTime" => "2014-08-29 15:32:11"
        );
        echo "var data = " . json_encode($ret);
    }

    public function mapdemoAction()
    {


    }

    public function appmapAction()
    {
        //status,在线状态，1在线，2不在线，默认全部.2015/11/30增加在线过滤
        $status = "/status/" . $_GET['status'];
        $this->getView()->status = $status;
    }

    public function machinemapAction()
    {
        //status,在线状态，1在线，2不在线，默认全部.2015/11/30增加在线过滤
        $status = "/status/" . $_GET['status'];
        $this->getView()->status = $status;
    }

    public function modifypasswordAction()
    {
        $errMsg = "";
        $this->checkLogin();
        if ("POST" == $_SERVER['REQUEST_METHOD']) {
            $oldPassword = trim($_REQUEST['oldPassword']);
            $newPassword = trim($_REQUEST['newPassword']);
            $newPassword1 = trim($_REQUEST['newPassword1']);

            if ($newPassword1 != $newPassword) {
                $errMsg = "2次输入的新密码不一致,请重新输入";
            } else {
                $detail = ServiceFactory::getService("User")->check($_SESSION['username'], $oldPassword);
                if (empty($detail)) {
                    $errMsg = "旧密码错误，请重新输入";
                } else {
                    ServiceFactory::getService("User")->updatePassword($_SESSION['id'], $newPassword);
                    $_SESSION['id'] = "";
                    $_SESSION['username'] = "";
                    session_destroy();
                    echo "<script>alert('修改成功，请重新登陆');top.window.location.href='/admin/login'</script>";
                    die;
                }
            }
        }
        $this->getView()->errMsg = $errMsg;
    }

    public function logoutAction()
    {
        $_SESSION['id'] = "";
        $_SESSION['username'] = "";
        session_destroy();
        echo "<script>top.window.location.href='/admin/login'</script>";
        die;
    }

    public function loginAction()
    {
        $errMsg = "";
        if ("POST" == $_SERVER['REQUEST_METHOD']) {
            $username = trim($_REQUEST['username']);
            $password = trim($_REQUEST['password']);

            $detail = ServiceFactory::getService("User")->check($username, $password);
            if (empty($detail)) {
                $errMsg = "用户名和密码错误";
            } else {
                $_SESSION['id'] = $detail['id'];
                $_SESSION['username'] = $detail['username'];
                echo "<script>window.location.href='/admin/index'</script>";
                die;
            }
        }
        $this->getView()->errMsg = $errMsg;
    }

    public function leftAction()
    {
        $this->checkLogin();
    }

    public function indexAction()
    {
        $this->checkLogin();

    }

    public function welcomeAction()
    {
        $this->checkLogin();

    }

    public function appmanageAction()
    {
        $this->checkLogin();
        $page = $_GET['page'];
        $pagesize = $_GET['pagesize'];
        //status,在线状态，1在线，2不在线，默认全部.2015/11/30增加在线过滤
        $status = $_GET['status'];

        if (isset($_REQUEST['tpMachineid'])) {
            $appid = ServiceFactory::getService("Machine")->getAppidList($_REQUEST['tpMachineid']);
            $pagesize = 999;
        } else {
            $appid = trim($_REQUEST['appid']);
            if (false !== strpos(",", $appid)) {
                $appid = explode(",", $appid);
            }
        }

        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }

        $data = array();
        $allPage = 0;
        $total = ServiceFactory::getService("App")->getAllCount($appid, $status);
        if ($total > 0) {
            $allPage = ceil($total / $pagesize);
            if ($page > $allPage) {
                $page = $allPage;
            }
            $offset = ($page - 1) * $pagesize;
            $limit = $pagesize;

            $data = ServiceFactory::getService("App")->getList($appid, $offset, $limit, $status);
        }

        $onlineTotal = ServiceFactory::getService("App")->getAppOnlineCount();
        $this->getView()->onlineTotal = $onlineTotal;
        $this->getView()->page = $page;
        $this->getView()->pagesize = $pagesize;
        $this->getView()->allPage = $allPage;
        $this->getView()->data = $data;
        $this->getView()->total = $total;
    }

    public function machinemanageAction()
    {
        $this->checkLogin();
        $page = $_GET['page'];
        $pagesize = $_GET['pagesize'];

        //status,在线状态，1在线，2不在线，默认全部.2015/11/30增加在线过滤
        $status = $_GET['status'];

        if (isset($_REQUEST['tpAppid'])) {
            $machineid = ServiceFactory::getService("App")->getMachineidList($_REQUEST['tpAppid']);
            $pagesize = 999;
        } else {
            $machineid = trim($_REQUEST['machineid']);
            if (false !== strpos(",", $machineid)) {
                $machineid = explode(",", $machineid);
            }
        }

        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }

        $data = array();
        $allPage = 0;
        $total = ServiceFactory::getService("Machine")->getAllCount($machineid, $status);
        if ($total > 0) {
            $allPage = ceil($total / $pagesize);
            if ($page > $allPage) {
                $page = $allPage;
            }
            $offset = ($page - 1) * $pagesize;
            $limit = $pagesize;

            $data = ServiceFactory::getService("Machine")->getList($machineid, $offset, $limit, $status);
        }

        $onlineTotal = ServiceFactory::getService("Machine")->getMachineOnlineCount();
        $this->getView()->onlineTotal = $onlineTotal;
        $this->getView()->page = $page;
        $this->getView()->pagesize = $pagesize;
        $this->getView()->allPage = $allPage;
        $this->getView()->data = $data;
        $this->getView()->total = $total;
    }

    public function getmachinelistAction()
    {
        $this->checkLogin();
        $appid = trim($_REQUEST['appid']);
        if (empty($appid)) {
            die("appid is empty");
        }
        $page = $_GET['page'];
        $pagesize = $_GET['pagesize'];

        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }

        $data = array();
        $allPage = 0;
        $total = 0;
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (!empty($tpAppid)) {
            $total = ServiceFactory::getService("App")->getAllCount($tpAppid);
            if ($total > 0) {
                $allPage = ceil($total / $pagesize);
                if ($page > $allPage) {
                    $page = $allPage;
                }
                $offset = ($page - 1) * $pagesize;
                $limit = $pagesize;

                $data = ServiceFactory::getService("App")->getList($tpAppid, $offset, $limit);
            }
        }
        $this->getView()->appid = $appid;
        $this->getView()->page = $page;
        $this->getView()->pagesize = $pagesize;
        $this->getView()->allPage = $allPage;
        $this->getView()->data = $data;
        $this->getView()->total = $total;
    }

    public function getapplistAction()
    {
        $this->checkLogin();
        $machineid = trim($_REQUEST['machineid']);
        if (empty($machineid)) {
            die("machineid is empty");
        }
        $page = $_GET['page'];
        $pagesize = $_GET['pagesize'];

        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }

        $data = array();
        $allPage = 0;
        $total = 0;
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (!empty($tpMachineid)) {
            $total = ServiceFactory::getService("Machine")->getAllCount($tpMachineid);
            if ($total > 0) {
                $allPage = ceil($total / $pagesize);
                if ($page > $allPage) {
                    $page = $allPage;
                }
                $offset = ($page - 1) * $pagesize;
                $limit = $pagesize;

                $data = ServiceFactory::getService("Machine")->getList($tpMachineid, $offset, $limit);
            }
        }
        $this->getView()->machineid = $machineid;
        $this->getView()->page = $page;
        $this->getView()->pagesize = $pagesize;
        $this->getView()->allPage = $allPage;
        $this->getView()->data = $data;
        $this->getView()->total = $total;
    }

    public function actionlogAction()
    {
        $this->checkLogin();
        $tpMachineid = $_REQUEST['tpMachineid'];
        $detail = ServiceFactory::getService("Machine")->getDetail($tpMachineid);
        if (empty($detail)) {
            die("can not get detail by tpMachineid " . $tpMachineid . "");
        }
        $type = $detail['type'];
        switch ($type) {
            case "02":
                $data = ServiceFactory::getService("Humidifier")->getActionLogListForAdmin($tpMachineid, 0, 999);
                $useStat = ServiceFactory::getService("Humidifier")->getHumidifierUseStat($tpMachineid);
                break;
			 case "10":
                $data = ServiceFactory::getService("Attendance")->getActionLogListForAdmin($tpMachineid, 0, 999);
                break;
            case getLightTag():
            case getRgbTag():
                $data = ServiceFactory::getService("Light")->getActionLogList($tpMachineid, 0, 999);
                $useStat = ServiceFactory::getService("Light")->getUseStat($tpMachineid);
                break;
            case getMosquitokillerTag():
                $data = ServiceFactory::getService("Mosquitokiller")->getActionLogList($tpMachineid, 0, 999);
                $useStat = ServiceFactory::getService("Mosquitokiller")->getUseStat($tpMachineid);
                break;
            default:
                $data = ServiceFactory::getService("Teapot")->getActionLogListForAdmin($tpMachineid, 0, 999);
                $useStat = ServiceFactory::getService("Teapot")->getTeapotUseStat($tpMachineid);
                break;
        }

        $this->getView()->useStat = $useStat;
        $this->getView()->data = $data;
        $this->getView()->type = $type;
    }

    public function currstateAction()
    {
        $this->checkLogin();
        $tpMachineid = $_REQUEST['tpMachineid'];

        $detail = ServiceFactory::getService("Machine")->getDetail($tpMachineid);
        if (empty($detail)) {
            die("can not get detail by tpMachineid " . $tpMachineid . "");
        }
        $type = $detail['type'];

        switch ($type) {
            case "01":
                $data = ServiceFactory::getService("Teapot")->getStateForAdmin($tpMachineid);
                break;
            case "02":
                $data = ServiceFactory::getService("Humidifier")->getStateForAdmin($tpMachineid);
                break;
            case $this->prefixMosquitokiller:
                $data = ServiceFactory::getService("Mosquitokiller")->getState($tpMachineid);
                break;
            case "03":
            case "05":
                $data = ServiceFactory::getService("Light")->getStateForAdmin($tpMachineid);
                break;
            default:
                //默认是teapot
                $data = ServiceFactory::getService("Teapot")->getStateForAdmin($tpMachineid);
                break;
        }

        $this->getView()->data = $data;
        $this->getView()->type = $type;
    }

    public function feedbackmanageAction()
    {
        $this->checkLogin();
        $page = $_GET['page'];
        $pagesize = $_GET['pagesize'];
        $appid = $_REQUEST['appid'];
        $tpAppid = $_REQUEST['tpAppid'];
        if (!empty($tpAppid)) {
            $pagesize = 999;
        } else if (!empty($appid)) {
            $tpAppid = ServiceFactory::getService("App")->getTpappid($appid);
            $pagesize = 999;
        }

        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        $pagesize = intval($pagesize);
        if ($pagesize < 1) {
            $pagesize = 10;
        }

        $data = array();
        $allPage = 0;
        $total = ServiceFactory::getService("Feedback")->getAllCount($tpAppid);
        if ($total > 0) {
            $allPage = ceil($total / $pagesize);
            if ($page > $allPage) {
                $page = $allPage;
            }
            $offset = ($page - 1) * $pagesize;
            $limit = $pagesize;

            $data = ServiceFactory::getService("Feedback")->getList($tpAppid, $offset, $limit);
        }

        $this->getView()->page = $page;
        $this->getView()->pagesize = $pagesize;
        $this->getView()->allPage = $allPage;
        $this->getView()->data = $data;
        $this->getView()->total = $total;
    }

    public function locationAction()
    {
        $this->checkLogin();
        $longitude = $_GET['longitude'];
        $latitude = $_GET['latitude'];
        $ip = $_GET['ip'];
        if (!empty($ip)) {
            $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=vH9exg7e34GZms3W15roBomy&ip=" . $ip . "&coor=bd09ll");
            //{"address":"CN|\u5e7f\u4e1c|\u5e7f\u5dde|None|CHINANET|1|None","content":{"address":"\u5e7f\u4e1c\u7701\u5e7f\u5dde\u5e02","address_detail":{"city":"\u5e7f\u5dde\u5e02","city_code":257,"district":"","province":"\u5e7f\u4e1c\u7701","street":"","street_number":""},"point":{"x":"113.30764968","y":"23.12004910"}},"status":0}
            $arr = json_decode($content, true);
            //echo "<pre>";
            //print_r($arr);
            //echo "</pre>";

            $longitude = $arr['content']['point']['x'];
            $latitude = $arr['content']['point']['y'];
        }

        $this->getView()->longitude = $longitude;
        $this->getView()->latitude = $latitude;
    }

    public function cleartestdataAction()
    {
        $this->checkLogin();
        $msg = "";

        if ("POST" == $_SERVER['REQUEST_METHOD']) {
            $content = $_REQUEST['content'];
            $content = trim($content);
            $arr = explode("\n", $content);
            foreach ($arr as $item) {
                $item = trim($item);
                if (empty($item)) {
                    continue;
                }
                $flag = ServiceFactory::getService("Admin")->deleteMachine($item);
                if ($flag) {
                    $msg .= "清理电器" . $item . "成功<br/>";
                } else {
                    $msg .= "<font color='red'><b>清理电器" . $item . "失败</b></font><br/>";
                }
            }
        }

        $this->getView()->msg = $msg;
    }

    public function feedbackdetailAction()
    {
        $this->checkLogin();
        $tpAppid = $_REQUEST['tpAppid'];

        if ("POST" == $_SERVER['REQUEST_METHOD']) {
            $content = $_POST['content'];
            ServiceFactory::getService("Feedback")->reply($tpAppid, $content);

        }

        $data = ServiceFactory::getService("Feedback")->getDetail($tpAppid);
        $this->getView()->data = $data;
        $this->getView()->tpAppid = $tpAppid;

        ServiceFactory::getService("Feedback")->updateIsRead($tpAppid);
    }

    public function getControlData()
    {
        return null;
    }

    public function specificationAction()
    {
        $this->getView();
    }
}
