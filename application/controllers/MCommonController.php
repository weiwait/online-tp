<?php
use base\ServiceFactory;
use utils\Result;
use base\DaoFactory;

abstract class MCommonController extends FrontController
{
    protected $prefixLight = "03";
    protected $prefixRgb = "05";
    protected $prefixMosquitokiller = "06";
    protected $prefixHumidifier = "02";
    protected $prefixTeapot = "01";
    protected $prefixSwitch = "13";
    protected $serviceName;

    protected $DEMO = true;
    protected $TYPE_PHONE = 1;
    protected $TYPE_NUMBER = 2;

    /**
     * 获取json
     * @param $object
     */
    protected function getJson($object)
    {
        return json_encode($object);
    }

    /**
     * 获取object
     * @param $json
     */
    protected function getObject($json)
    {
        return json_decode($json);
    }

    /**
     * 乱码问题
     */
    protected function preventEncode()
    {
        header("content-type:text/html; charset=utf-8");
    }

    /**
     * 返回错误
     * @param $msg
     */
    protected function error($msg)
    {
        $this->preventEncode();
        echo urldecode($this->getJson(array(
            "status" => 0,
            "data" => urlencode($msg),
        )));
        die;
    }

    /**
     * 数组urlencode
     * @param $data
     */
    protected function accUrlencode($data)
    {
        if (!is_array($data)) {
            return urlencode($data);
        }
        foreach ($data as $key => $val) {
            $data[$key] = $this->accUrlencode($val);
        }
        return $data;
    }

    /**
     * 返回数据
     * @param $msg
     */
    protected function show($data)
    {
        $this->preventEncode();
        echo urldecode($this->getJson(array(
            "status" => 1,
            "data" => $this->accUrlencode($data),
        )));
        die;
    }

    /**
     * 获取参数值
     */
    protected function getParam($key)
    {
        $value = $_REQUEST[$key];
        if (!$value) {
            $value = $this->getRequest()->getParam($key, null);
        }
        return trim($value);
    }

    protected function getValidParam($key, $name = null, $type = null, $notNull = null, $reg = null, $errorMessage)
    {
        $value = $this->getParam($key);
        if ($notNull && '0' != $value && !$value) {
            $this->error($name . "不能为空");
        }
        if ($type || $reg) {
            if (!$this->isValid($value, $type, $reg)) {
                if ($errorMessage) {
                    $this->error($name . $errorMessage);
                }
                $this->error($name . "格式不正确");
            }
        }
        return $value;
    }

    /**
     * 判断数据格式是否正确
     * @param $value
     * @param $type
     */
    protected function isValid($value, $type, $reg = null)
    {
        if ($reg) {
            return preg_match($reg, $value);
        } else if ($type == $this->TYPE_PHONE) {
            return preg_match("/^1[34578]{1}\\d{9}$/", $value);
        } else if ($type == $this->TYPE_NUMBER) {
            return preg_match("/^[0-9]*$/", $value);;
        }
        return true;
    }

    /**
     * DEMO版本处理
     */
    protected function checkIsDemo()
    {
        if (!$this->getParam('demo')) {
            $this->error("应用处于DEMO阶段,请在请求后面加参数demo=true");
        }
    }

    /**
     * 初始化
     */
    public function init()
    {
        parent::init();
    }

    /**
     * 获取控制电器的参数值
     * @return mixed
     */
    abstract function getControlData();

    /**
     * @desc 获取类型前缀
     */
    protected function getTypePrefix()
    {
        return substr($this->getMachineid(), 0, 2);
    }

    /**
     * @desc 获取应用id
     */
    protected function getAppid()
    {
        return trim($_REQUEST['appid']);
    }

    /**
     * @return 是否为合法appid
     */
    protected function isValidAppid()
    {
        return "00000000-0000-0000-0000-000000000000" != $_REQUEST['appid'];
    }

    /**
     * @desc 获取TP应用id
     */
    protected function getTPAppid()
    {
        return ServiceFactory::getService("App")->getTpAppid($this->getAppid());
    }

    /**
     * @desc 获取机器id
     */
    protected function getMachineid()
    {
        return trim($_REQUEST['machineid']);
    }

    /**
     * @desc 检查
     */
    protected function checkOrder()
    {
        $this->checkMachineid();
        if ($this->isValidAppid()) {
            $this->checkAppid();
        }
        $heattime = $_REQUEST['heattime'];
        $week = $_REQUEST['week'];
        $action = trim($_REQUEST['action']);
        if (7 != strlen($week)) {
            Result::showError("week length must be 7 char");
        }
        if (6 != strlen($heattime)) {
            Result::showError("heattime length must be 6 char, not support 0");
        }
        $action = strtolower($action);
        if (!in_array($action, array("run", "stop"))) {
            Result::showError("bad action '" . $action . "'");
        }
    }

    /**
     * @desc 检查APP号
     */
    protected function checkAppid()
    {
        $this->disableView();
        $appid = $this->getAppid();
        if (empty($appid)) {
            $this->showError("appid is empty");
        }
        $tpAppid = ServiceFactory::getService("App")->getTpAppid($appid);
        if (empty($tpAppid)) {
            $this->showError("appid " . $appid . " have not reg");
        }
        $flag = ServiceFactory::getService("App")->isBind($tpAppid, $this->getTPMachineid());
        if (!$flag) {
            $this->showError("appid " . $appid . " have not bind machineid " . $this->getMachineid() . "");
        }
        ServiceFactory::getService("App")->active($tpAppid);
    }

    /**
     * @return bool 是否为多个电器
     */
    protected function isMultiMachine()
    {
        if (count(explode(",", $this->getMachineid())) > 1) {
            return true;
        }
        return false;
    }

    /**
     * @desc 检查电器号
     */
    protected function checkMachineid()
    {
        $this->disableView();
        $machineid = trim($_REQUEST['machineid']);
        if (empty($machineid)) {
            $this->showError("machineid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            $this->showError("machineid " . $machineid . " have not reg");
        }
    }

    /**
     * @desc 去掉页面显示
     */
    protected function disableView()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
    }

    /**
     * 获取service
     */
    protected function getService()
    {
        return ServiceFactory::getService($this->getServiceName());
    }

    /**
     * 获取名字
     * @return mixed
     */
    protected function getServiceName()
    {
        return $this->_name;
    }

    /**
     * 获取名字
     * @return mixed
     */
    protected function getName()
    {
        return machineType($this->getMachineid());
    }

    /**
     * @desc 获取TP机器id
     */
    protected function getTPMachineid()
    {
        return ServiceFactory::getService("Machine")->getTpMachineid($this->getMachineid());
    }

    /**
     * @desc 获取单个TP机器id
     */
    protected function getSingleTPMachineid()
    {
        $machineids = explode(",", $this->getMachineid());
        return ServiceFactory::getService("Machine")->getTpMachineid($machineids[0]);
    }

    /**
     * @desc SOCKET连接
     */
    /*protected function socketDo($call)
    {
        $call = json_encode($call);
        $call = str_replace('\\', '', $call);
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, '127.0.0.1', 1234);
        $in = $call['machineid'] . "::";
        $in .= "HTTP/1.1 200 OK\r\n";
        $in .= "Date:" . date("D, d M Y H:i:s T", time()) . "\r\n";
        $in .= "Content-Length:" . strlen($call) . "\r\n\r\n";
        $in .= $call;
        socket_write($socket, $in, strlen($in));
        socket_close($socket);
    }*/

    /**
     * @desc 传送data
     * @param $data
     * @param $socketData
     */
    /*protected function socketData($socketData, $machineid = null)
    {
        if (!$machineid) {
            $machineid = $this->getMachineid();
        }
        $data[$machineid] = $socketData;
        $call = array(
            "url" => '/machine/control',
            "status" => "1",
            "machineid" => $machineid,
            "data" => $data
        );
        $this->socketDo($call);
    }*/

    /**
     * @desc 产生预约id
     */
    protected function generateOrderid()
    {
        $tpMachineid = $this->getTPMachineid();
        $i = 0;
        while ($i < 20) {
            $orderid = date("YmdHis") . rand(10, 99);
            $sql = "select orderid from light_order where tp_machineid='" . $tpMachineid . "' and orderid='" . $orderid . "' limit 1";
            $data = DaoFactory::query($tpMachineid, $sql);
            if (empty($data)) {
                return $orderid;
            }
            ++$i;
        }
        return 0;
    }

    /**
     * @return 增加预约
     */
    public function addOrder()
    {
        $this->checkOrder();
        $appid = $_REQUEST['appid'];
        $machineid = $_REQUEST['machineid'];
        $heattime = $_REQUEST['heattime'];
        $week = $_REQUEST['week'];
        $action = trim($_REQUEST['action']);
        $orderid = trim($_REQUEST['orderid']);
        if (empty($orderid)) {
            $orderid = $this->generateOrderid();
            if (empty($orderid)) {
                $this->showError("system error, createOrderId fail");
            }
        }
        $controlData = $this->getControlData();
        $data = $this->getOrderService()->add($this->getTPMachineid(), $machineid, $this->getTPAppid(), $appid, $orderid, $heattime, $week, $action, $controlData == null ? $controlData : json_encode($controlData));
        if ($data) {
            $ret = array(
                "status" => 1,
                "data" => "ok",
                "orderid" => $orderid,
            );
            $ret = json_encode($ret);
            $this->output($ret);
        } else {
            $this->showError("system error");
        }
    }

    /**
     * @return 删除预约
     */
    protected function deleteOrder()
    {
        $this->checkMachineid();
        if ($this->isValidAppid()) {
            $this->checkAppid();
        }
        $orderid = trim($_REQUEST['orderid']);
        $tpMachineid = $this->getTPMachineid();
        if (!$this->getOrderService()->isExist($tpMachineid, $orderid)) {
            $this->showError("order " . $orderid . " is not exist");
        }
        $data = $this->getOrderService()->delete($tpMachineid, $orderid);
        if ($data) {
            $this->showOk("ok");
        } else {
            $this->showError("system error");
        }
    }

    private function getOrderService()
    {
        return ServiceFactory::getService("MachineOrder");
    }

    /**
     * 提示错误
     * @param $string
     */
    protected function showError($string)
    {
        Result::showError($string);
    }

    /**
     * 结果数据
     * @param $string
     */
    protected function output($string)
    {
        Result::output($string);
    }

    /**
     * 提示错误
     * @param $string
     */
    protected function showOk($string)
    {
        Result::showOk($string);
    }

    /**
     * 关闭电器
     */
    protected function stopMachine()
    {
        if ($this->isMultiMachine()) {
            $this->disableView();
        } else {
            $this->checkMachineid();
            if ($this->isValidAppid()) {
                $this->checkAppid();
            }
        }
        stopMachine($this->getControlData(), $this->getMachineid());
        $this->showOk("ok");
    }

    /**
     * 开始电器
     */
    protected function startMachine()
    {
        if ($this->isMultiMachine()) {
            $this->disableView();
        } else {
            $this->checkMachineid();
            if ($this->isValidAppid()) {
                $this->checkAppid();
            }
        }
        startMachine($this->getControlData(), $this->getMachineid());
        $this->showOk("ok");
    }

    /**
     * 发消息给电器
     */
    protected function machineControll($controllData = null, $subControl = null)
    {
        if ($this->isMultiMachine()) {
            $this->disableView();
        } else {
            $this->checkMachineid();
            if ($this->isValidAppid()) {
                $this->checkAppid();
            }
        }
        machineControl($controllData ? $controllData : $this->getControlData(), $this->getMachineid(), $subControl);
        $this->showOk("ok");
    }

    /**
     * 检查登录
     */
    protected function checkLogin()
    {
        if (empty($_SESSION['id'])) {
            echo "<script>window.location.href='/admin/login'</script>";
            die;
        }
    }

    /**
     * 添加使用记录
     * @param $operation
     * @param $starttime
     * @param $costtime
     */
    /*protected function addActionLog($operation, $starttime, $costtime)
    {
        $this->getService()->actionLog($this->getTPMachineid(), $this->getMachineid(), $this->getTPAppid(), $this->getAppid(), $operation, $starttime, $costtime);
    }*/

    /**
     * 请求日志记录
     */
    protected function log()
    {
        $name = $this->getServiceName();
        file_put_contents("/tmp/" . $name . ".log", date("Y-m-d H:i:s") . " " . json_encode($_REQUEST) . "\n", FILE_APPEND);
    }

    /**
     * 打印日志记录
     */
    protected function logString($string)
    {
        $name = $this->getServiceName();
        file_put_contents("/tmp/" . $name . ".log", date("Y-m-d H:i:s") . " " . $string . "\n", FILE_APPEND);
    }

    /**
     * @desc 正确返回
     * @param $data
     */
    protected function goodReturn($data)
    {
        $return = array(
            "status" => 1,
            "data" => $data
        );
        die(json_encode($return));
    }

    /**
     * @desc 错误返回
     * @param $data
     */
    protected function badReturn($data)
    {
        $return = array(
            "status" => 0,
            "data" => $data
        );
        die(json_encode($return));
    }

    /**
     * @desc 分页查询
     */
    protected function getPageList($isOrder, $nameOfGetPageNumFuction, $nameOfGetPageListFuction)
    {
        $this->checkValidMachineAndApp();
        $pagesize = trim($_REQUEST['pagesize']);
        $page = $this->getIntvalPage();
        if ($page < 1) {
            $page = 1;
        }
        if ($pagesize < 1) {
            $pagesize = 10;
        }
        $tpMachineid = $this->getTPMachineid();
        $service = $isOrder ? $this->getOrderService() : $this->getService();
        $total = $service->$nameOfGetPageNumFuction($tpMachineid);
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
        $data = $service->$nameOfGetPageListFuction($tpMachineid, $offset, $limit);
        if ($data) {
            $ret = array(
                "status" => 1,
                "total" => $total,
                "page" => $page,
                "pagesize" => $pagesize,
                "data" => $data,
            );
            $ret = json_encode($ret);
            $this->output($ret);
            die;
        } else {
            $this->showError("system error");
        }
    }

    /**
     * @desc 获取状态
     */
    protected function getIntvalState()
    {
        return intval($_REQUEST['state']);
    }

    /**
     * @desc 获取PAGE
     */
    protected function getIntvalPage()
    {
        return intval($_REQUEST['page']);
    }

    /**
     * @desc 检查机器和设备号（需要设备号可以为空）
     */
    protected function checkValidMachineAndAppOther()
    {
        $machineid = trim($_REQUEST['machineid']);
        $appid = trim($_REQUEST['appid']);
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }
        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }
        if (!empty($appid)) {
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
    }

    /**
     * @desc 机器发送
     */
    protected function checkValidMachineAndAppOtherMachine()
    {
        $this->disableView();
        $machineid = $this->getMachineid();
        if (empty($machineid)) {
            Result::showError("machineid is empty");
        }

        $tpMachineid = ServiceFactory::getService("Machine")->getTpMachineid($machineid);
        if (empty($tpMachineid)) {
            Result::showError("machineid " . $machineid . " have not reg");
        }

        ServiceFactory::getService("Machine")->active($tpMachineid, $_REQUEST['ip']);
    }

    /**
     * @desc 检查机器和设备号
     */
    protected function checkValidMachineAndApp()
    {
        $this->checkMachineid();
        $this->checkAppid();
    }

    /**
     * @desc 返回数据
     */
    protected function returnData($ret)
    {
        if ($ret) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    /**
     * @desc 返回JSON数据
     */
    protected function returnJSON($data)
    {
        if ($data) {
            $ret = array(
                "status" => 1,
                "data" => $data,
            );
            $ret = json_encode($ret);
            $this->output($ret);
        } else {
            $this->showError("system error");
        }
    }

    /**
     * @desc 获取预约详情
     */
    protected function getOrder()
    {
        $this->checkValidMachineAndApp();
        $this->returnJSON($this->getOrderService()->getOrder($this->getTPMachineid(), trim($_REQUEST['orderid'])));
    }

    /**
     * @desc 分页查询
     */
    protected function getActionLogList()
    {
        $this->getPageList(false, 'getActionLogNum', 'getActionLogList');
    }

    /**
     * @desc 获取预约列表
     */
    protected function getOrderList()
    {
        $this->getPageList(true, 'getOrderNum', 'getOrderList');
    }

    protected function calcCostTime($startTime, $endTime, &$realStartTime)
    {
        $ymd = date("Y-m-d");
        $startHour = intval(substr($startTime, 0, 2));
        $startMin = intval(substr($startTime, 2, 2));
        $startSec = intval(substr($startTime, 4, 2));
        $endHour = intval(substr($endTime, 0, 2));
        $endMin = intval(substr($endTime, 2, 2));
        $endSec = intval(substr($endTime, 4, 2));
        $start = strtotime($ymd . " " . $startHour . ":" . $startMin . ":" . $startSec . "");
        $end = strtotime($ymd . " " . $endHour . ":" . $endMin . ":" . $endSec . "");
        $realStartTime = $start;
        $ret = $end - $start;
        if ($ret < 0) {
            $ret += 86400;
        }
        return $ret;
    }

    /**
     * @desc 保存配置
     */
    public function  saveConfig($config = null)
    {
        $this->checkValidMachineAndApp();
        $enableUserNearStart = trim($_REQUEST['enableusernearstart']);
        $enableUserFarStop = trim($_REQUEST['enableuserfarstop']);
        $startAndStopRemind = trim($_REQUEST['startandstopremind']);
        $startAndStopRemind = $startAndStopRemind ? 1 : 0;
        $enableUserNearStart = $enableUserNearStart ? 1 : 0;
        $enableUserFarStop = $enableUserFarStop ? 1 : 0;
        if (!$config) {
            $config = array(
                "start_stop_remind" => $startAndStopRemind,
                "enable_user_far_stop" => $enableUserFarStop,
                "enable_user_near_start" => $enableUserNearStart
            );
        } else {
            $config['start_stop_remind'] = $startAndStopRemind;
            $config['enable_user_far_stop'] = $enableUserFarStop;
            $config['enable_user_near_start'] = $enableUserNearStart;
        }
        $data = ServiceFactory::getService("App")->setMachineConfig($this->getTPMachineid(), $this->getTPAppid(), $config);
        if ($data) {
            Result::showOk("ok");
        } else {
            Result::showError("system error");
        }
    }

    /**
     * 更新机器配置信息
     */
    private function updateMachineData()
    {
        $controlData = $this->getControlData();
        if ($controlData) {
            $controlData = json_encode($controlData);
            ServiceFactory::getService("Machine")->updateMachineData($controlData, $this->getTPMachineid());
        }
    }

    private function pushStopOrStartMessage()
    {
        $tpMachineid = $this->getTPMachineid();
        $state = trim($_REQUEST['state']);
        if (!$tpMachineid) {
            return;
        }
        $daStateTemp = $this->getService()->getState($tpMachineid);
        if (!$daStateTemp) {
            return;
        }
        $dbState = $daStateTemp['state'];
        if ($state != $dbState) {
            $name = $this->getName();
            if ($state) {
                ServiceFactory::getService("PushMsg")->pushMessage($name, $tpMachineid, $name . '已开启...');
            } else {
                ServiceFactory::getService("PushMsg")->pushMessage($name, $tpMachineid, $name . '已关闭...');
            }
        }
    }

    /**
     * 更新机器
     */
    protected function updateMachine()
    {
        $this->checkValidMachineAndAppOtherMachine();
        $this->updateMachineData();
        $this->pushStopOrStartMessage();
    }

    protected function getMachineStat()
    {
        $this->checkValidMachineAndApp();
        $this->returnJSON($this->getService()->getUseStat($this->getTPMachineid()));
    }
}
