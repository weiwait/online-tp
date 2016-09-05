<?php
session_start();
define("APP_PATH",  dirname(dirname(__FILE__)));

// 引入composer自动加载
require dirname(__DIR__) . '/vendor/autoload.php';
$autoload = new Composer\Autoload\ClassLoader();
$autoload->register(true);
$autoload->addClassMap(['MCommonController' => APP_PATH . '/application/controllers/MCommonController.php']);
// composer autoload end

$globalTpAppid = 0;
$globalTpMachineid = 0;

$__content = json_encode($_REQUEST);
file_put_contents("tmp/all.log", date("Y-m-d H:i:s")." ".$__content."\n", FILE_APPEND);

$requestUrl = $_SERVER['REQUEST_URI'];
$requestUrlArray = explode("?", $requestUrl);
$requestUrl = $requestUrlArray[0];

$__tmpArray = explode("/", $requestUrl);
array_shift($__tmpArray);
$_GET['controlerName'] = array_shift($__tmpArray);
$_GET['controlerName'] = trim($_GET['controlerName']);
$_GET['actionName'] = "";

if(empty($_GET['controlerName']))
{
    $_GET['controlerName'] = "Index"; 
    $_GET['actionName'] = "index";
}
else
{
    $_GET['actionName'] = array_shift($__tmpArray);
    $_GET['actionName'] = trim($_GET['actionName']);
    
    if(empty($_GET['actionName']))
    {
        $_GET['actionName'] = "index"; 
    }
    else
    {
        while(!empty($__tmpArray))
        {
            $key = array_shift($__tmpArray); 
            $key = urldecode($key);
            if(!empty($__tmpArray))
            {
                $value = array_shift($__tmpArray); 
                $value = urldecode($value);
                //FIXME 譏ｯ蜷ｦ髴�隕「rldecode
                $_GET[$key] = $value;
            }
            else
            {
                $_GET[$key] = ""; 
            }
        }
    }
}

switch($_SERVER['REQUEST_METHOD'])
{
    case "POST":
    case "PUT":
    case "DELETE":
        $requestData = file_get_contents('php://input');
        $requestDataArray = explode("&", $requestData);
        foreach($requestDataArray as $item)
        {
            $arr = explode("=", $item);
            $key = urldecode($arr[0]); 
            $value = urldecode($arr[1]); 
            $_POST[$key] = $value;
        }
    break;
    default:
    break;
}

foreach($_GET as $key=>$value)
{
    $_REQUEST[$key] = $value;
}

foreach($_POST as $key=>$value)
{
    $_REQUEST[$key] = $value;
}

foreach($_COOKIE as $key=>$value)
{
    $_REQUEST[$key] = $value;
}

foreach($_REQUEST as $key => $value)
{
    if("content" != $key)
    {
        if(false !== strpos($value, "=") || false !== strpos($value, "\"") || false !== strpos($value, "'"))
        {
            $ret = array(
                "status"=>0,
                "data"=>"Illegal characters",
            );
            echo json_encode($ret);
            die;
        }
    }
}

foreach($_REQUEST as $key=>$value)
{
    $len = strlen($value);
    if("appid" == $key && "admin" != $_REQUEST['controlerName'])
    {
        if(36 != $len && 16 != $len && "manual" != $value)
        {
            $ret = array(
                "status"=>0,
                "data"=>"appid len must be 36 or 16",
            );
            echo json_encode($ret);
            die;
        }
    }
    else if("machineid" == $key && "admin" != $_REQUEST['controlerName'])
    {
        if(20 != $len)
        {
            $arr = explode(",", $value);
            foreach($arr as $value1)
            {
                $len1 = strlen($value1);
                if(20 != $len1)
                {
                    $ret = array(
                        "status"=>0,
                        "data"=>"machineid len must be 20",
                    );
                    echo json_encode($ret);
                    die;
                }
            }
        }
    }
    else if("temp" == $key)
    {
        $value = trim($value);
        $value = strtolower($value);
        if(1 !== substr_count($value, "c") && 1 !== substr_count($value, "f"))
        {
            $ret = array(
                "status"=>0,
                "data"=>"temp must be number + C/c/F/f, pls check your input",
            );
            echo json_encode($ret);
            die;
        }
        $arr = explode("c", $value);
        $arr1 = explode("f", $value);
        if((!is_numeric($arr[0]) || !empty($arr[1]) ) && (!is_numeric($arr1[0]) || !empty($arr1[1])))
        {
            $ret = array(
                "status"=>0,
                "data"=>"temp must be number + C/c/F/f, pls check your input!",
            );
            echo json_encode($ret);
            die;
        }
    }
    else if("level" == $key)
    {
        $value = trim($value);
        $value = strtolower($value);
        if(1 !== substr_count($value, "l"))
        {
            $ret = array(
                "status"=>0,
                "data"=>"level must be number + L/l, pls check your input",
            );
            echo json_encode($ret);
            die;
        }
        $arr = explode("l", $value);
        if(!is_numeric($arr[0]) || !empty($arr[1]))
        {
            $ret = array(
                "status"=>0,
                "data"=>"level must be number + L/l, pls check your input!",
            );
            echo json_encode($ret);
            die;
        }
    }
}

$app  = new Yaf_Application(APP_PATH . "/conf/application.ini");
$app->bootstrap()->run();


