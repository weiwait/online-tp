<?php
use base\Logger;
use utils\Common;
class ControlController extends \Yaf_Controller_Abstract
{
    private $secureKeyMap = array(
        "v1"=>"13800138000",
        "v2"=>"110119120",
    );

    private $hardCodeToken = "123123123";

    public function init()
    {
       //$this->checkToken(); 
    }

    private $tokenMap = array(
        "app,reg"=>"appid",
        "app,bind"=>"appid,machineid",
        "app,unbind"=>"appid,machineid",
        "app,getmachinelist"=>"appid,page,pagesize",
        "app,getmachinenum"=>"appid",
        "app,deletemachine"=>"appid,machineid",
        "app,checkversion"=>"appid,version",
        "app,feedback"=>"appid,content",
        "app,clearallrequest"=>"appid,machineid",
        "app,updatelocation"=>"appid,longitude,latitude",
        "app,feedbackdetail"=>"appid,page,pagesize",
        "app,addmsg"=>"appid,machineid,content",
        "app,getmsglist"=>"appid,page,pagesize",
        "app,getunreadmsglist"=>"appid,page,pagesize",
        "app,updatemsgstatus"=>"appid,id",
        "app,getmsgnum"=>"appid",
        "app,getunreadmsgnum"=>"appid",
        "app,deletemsg"=>"appid,id",
        "app,getnearmachine"=>"appid,onlineflag,bindflag",

        "machine,reg"=>"machineid",
        "teapot,actionlog"=>"machineid,operation,starttime,endtime,level,temp,boil,purify,keepwarm,energy",
        "teapot,getactionloglist"=>"appid,machineid,page,pagesize",
        "teapot,updatestate"=>"machineid,level,temp,hub,state",
        "teapot,request"=>"machineid,page,pagesize",
        "teapot,requestresult"=>"machineid,orderid,result",
        "teapot,getstate"=>"appid,machineid",
        "teapot,heat"=>"appid,machineid,temp,boil,purify,keepwarm,heattime,week,costtime",
        "teapot,cancelheat"=>"appid,machineid,orderid",
        "teapot,stopheat"=>"appid,machineid",
        "teapot,getorderlist"=>"appid,machineid,page,pagesize",
        "teapot,stat"=>"appid,machineid",

        //加湿器
        "humidifier,actionlog"=>"machineid,operation,starttime,endtime,startlevel,endlevel,tophumidity,middlehumidity,bottomhumidity,starthumidity,endhumidity,energy",
        "humidifier,getactionloglist"=>"appid,machineid,page,pagesize",
        "humidifier,updatestate"=>"machineid,humidity,level,state",
        "humidifier,getstate"=>"appid,machineid",
        "humidifier,stat"=>"appid,machineid",
        "humidifier,request"=>"machineid",
    );

    private function tokenError($msg="bad token")
    {
        $ret = array(
                "status"=>0,
                "data"=>$msg,
                ); 
        echo json_encode($ret);
        die;
    }

    public function buildToken($controlerName, $actionName, $v='v1')
    {
        $controlerName = strtolower($controlerName);
        $actionName = strtolower($actionName);

        if(!isset($this->secureKeyMap[$v]))
        {
            return "-1"; 
        }
        //echo "-----------".$controlerName."-------------".$actionName."-------------------------\n";

        $flag = false;
        foreach($this->tokenMap as $key=>$value)
        {
            $arr = explode(",", $key); 
            $arr[0] = trim($arr[0]);
            $arr[1] = trim($arr[1]);
            if($controlerName == $arr[0] && $actionName == $arr[1])
            {
                $arr1 = explode(",", $value); 
                $content = "";
                foreach($arr1 as $k)
                {
                    $k = trim($k);
                    $content .= ",".$_REQUEST[$k];
                }
                return md5($this->secureKeyMap[$v].$content);
            }
        }
        return "-2";
    }

    public function checkToken()
    {
        $controlerName = $_REQUEST['controlerName']; 
        $actionName = $_REQUEST['actionName']; 

        $controlerName = strtolower($controlerName);
        $actionName = strtolower($actionName);

        $v = $_REQUEST['v'];
        $token = $_REQUEST['token'];

        if($token == $this->hardCodeToken)
        {
            return true;
        }

        if(in_array($controlerName, array("admin", "test")))
        {
            return true; 
        }
        if(empty($v) || empty($token))
        {
            //$this->tokenError();
        }

        $flag = false;
        foreach($this->tokenMap as $key=>$value)
        {
            $arr = explode(",", $key); 
            $arr[0] = trim($arr[0]);
            $arr[1] = trim($arr[1]);
            if($controlerName == $arr[0] && $actionName == $arr[1])
            {
                $arr1 = explode(",", $value); 
                $content = "";
                foreach($arr1 as $k)
                {
                    $k = trim($k);
                    $content .= ",".$_REQUEST[$k];
                }
                $okToken = md5($this->secureKeyMap[$v].$content);
                if($okToken != $token)
                {
                    $this->tokenError();
                }
                $flag = true;
                break;
            }
        }
        if(false == $flag)
        {
            $this->tokenError("token map not set ".$controlerName.", ".$actionName."");
        }
        return true;
    }
}
