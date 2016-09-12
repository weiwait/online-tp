<?php
use base\ServiceFactory;
use base\DaoFactory;
use utils\Common;
use utils\Result;
use utils\TestRequest;

class RecordController extends FrontController
{
    /**
     * 驼峰分割
     * @param $str
     * @return string
     */
    private function toUnderScore($str)
    {
        $array = array();
        $justForOne = true;
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str[$i] == strtolower($str[$i])) {
                $array[] = $str[$i];
            } else {
                if ($i > 0 && $justForOne) {
                    $justForOne = false;
                    $array[] = '_';
                    $array[] = strtolower($str[$i]);
                    continue;
                }
                if ($justForOne) {
                    $array[] = strtolower($str[$i]);
                } else {
                    $array[] = $str[$i];
                }
            }
        }

        $result = implode('', $array);
        return $result;
    }

    //=================以上可通用处理=================//

    private $host = "127.0.0.1";
    private $port = "8081";
    private $DEMO = true;

    /**
     * 初始化
     */
    public function init()
    {
        $this->host = explode(':', $_SERVER['HTTP_HOST'])[0];
        parent::init();
    }

    /**
     * 首页逻辑
     */
    public function indexAction()
    {
    }

    public function leftAction()
    {
        $subData['用户注册'] = '/api/schoolRegist';
        $subData['获取用户详细信息'] = '/api/schoolGetUser';
        $subData['标签绑定'] = '/api/schoolBindLabel';
        $subData['获取用户选修课程'] = '/api/schoolGetUserCourse';
        $subData['获取课程详细'] = '/api/schoolGetCourse';
        $subData['获取用户课程表'] = '/api/schoolGetTimetables';
        $subData['获取课程评价和分数'] = '/api/schoolGetValue';
        $subData['课程评价'] = '/api/schoolSetValue';
        $subData['设置课程提醒'] = '/api/schoolSetNotify';
        $subData['获取考勤记录'] = '/api/schoolGetAttendance';
        $subData['获取统计信息'] = '/api/schoolGetStatic';
        $data['学校考勤接口'] = $subData;
        $this->getView()->data = $data;
    }

    public function welcomeAction()
    {
        $this->getView()->message = "这是API";
    }

    /**
     * url识别
     * @param $data
     */
    private function getUrl()
    {
        $url = trim($_REQUEST['actionName']);
        $resultUrl = explode('_', $this->toUnderScore(($url)));
        $targetUrl = '';
        foreach ($resultUrl as $subUrl) {
            $targetUrl .= '/' . $subUrl;
        }
        $data['url'] = $_SERVER['REQUEST_URI'];
        $data['targetUrl'] = $targetUrl;
        return $data;
    }

    /**
     * API接口结尾处理
     * @param $data
     */
    private function apiEndBuild($param, $name)
    {
        foreach ($param as $key => $value) {
            $param[$key] = trim($_REQUEST[$key]);
        }
        $param['demo'] = $this->DEMO;
        $data['param'] = $param;
        $data = array_merge($this->getUrl(), $data);
        $data['name'] = $name;
        $requestString = '';
        $result = '';
        if ("POST" == $_SERVER['REQUEST_METHOD']) {
            $method = "POST";
            $requestString = TestRequest::buildRequest($this->host, $data['targetUrl'], $method, $param);
            $result = TestRequest::sendRequest($this->host, $this->port, $requestString);
        }
        $this->getView()->requestString = $requestString;
        $this->getView()->result = $result;
        $this->getView()->data = $data;
    }

    /**
     * APP界面设计接口
     */
    public function appuiAction()
    {
        $this->toUnderScore(trim($_REQUEST['actionName']));
    }

    /**
     * 用户注册
     */
    public function schoolRegistAction()
    {
        $data = ApiController::getSchoolRegistDemoData();
        $this->apiEndBuild($data, "用户注册");
    }

    /**
     * 获取用户详细信息
     */
    public function schoolGetUserAction()
    {
        $data = ApiController::getSchoolGetUserDemoData();
        $this->apiEndBuild($data, "获取用户详细信息");
    }

    /**
     * 标签绑定
     */
    public function schoolBindLabelAction()
    {
        $data = ApiController::getBindLabelDemoData();
        $this->apiEndBuild($data, "标签绑定");
    }

    /**
     * 获取用户选修课程
     */
    public function schoolGetUserCourseAction()
    {
        $data = ApiController::getSchoolGetUserCourseDemoData();
        $this->apiEndBuild($data, "获取用户选修课程");
    }

    /**
     * 获取课程详细
     */
    public function schoolGetCourseAction()
    {
        $data = ApiController::getSchoolGetCourseDemoData();
        $this->apiEndBuild($data, "获取课程详细");
    }

    /**
     * 获取用户课程表
     */
    public function schoolGetTimetablesAction()
    {
        $data = ApiController::getSchoolGetTimetablesDemoData();
        $this->apiEndBuild($data, "获取用户课程表");
    }

    /**
     * 获取课程评价和分数
     */
    public function schoolGetValueAction()
    {
        $data = ApiController::getSchoolGetValueDemoData();
        $this->apiEndBuild($data, "获取课程评价和分数");
    }

    /**
     * 获取课程评价和分数
     */
    public function schoolSetValueAction()
    {
        $data = ApiController::getSchoolSetValueDemoData();
        $this->apiEndBuild($data, "获取课程评价和分数");
    }

    /**
     * 设置课程提醒
     */
    public function schoolSetNotifyAction()
    {
        $data = ApiController::getSchoolSetNotifyDemoData();
        $this->apiEndBuild($data, "设置课程提醒");
    }

    /**
     * 获取考勤记录
     */
    public function schoolGetAttendanceAction()
    {
        $data = ApiController::getSchoolGetAttendanceData();
        $this->apiEndBuild($data, "获取考勤记录");
    }

    /**
     * 获取统计信息
     */
    public function schoolGetStaticAction()
    {
        $data = ApiController::getSchoolGetStaticData();
        $this->apiEndBuild($data, "获取统计信息");
    }

    public static function getSchoolRegistDemoData()
    {
        $data['name'] = '小明';
        $data['phone'] = '18718743323';
        $data['school'] = '思明学校';
        $data['class'] = '三(2)班';
        $data['company'] = '星弈科技';
        $data['job'] = '设计师';
        $data['label'] = '10123451234587654321';
        return $data;
    }

    public static function getSchoolGetUserDemoData()
    {
        $data['user'] = '123';
        return $data;
    }

    public static function getBindLabelDemoData()
    {
        $data['user'] = '123';
        $data['label'] = '10457234657845436739';
        return $data;
    }

    public static function getSchoolGetUserCourseDemoData()
    {
        $data['user'] = '123';
        return $data;
    }

    public static function getSchoolGetCourseDemoData()
    {
        $data['course'] = '434';
        return $data;
    }

    public static function getSchoolGetTimetablesDemoData()
    {
        $data['user'] = '123';
        return $data;
    }

    public static function getSchoolGetValueDemoData()
    {
        $data['user'] = '123';
        $data['course'] = '443';
        return $data;
    }

    public static function getSchoolSetValueDemoData()
    {
        $data['value'] = '8';
        $data['course'] = '443';
        $data['user'] = '123';
        return $data;
    }

    public static function getSchoolSetNotifyDemoData()
    {
        $data['user'] = '123';
        $data['course'] = '443';
        $data['notify'] = '1';
        return $data;
    }

    public static function getSchoolGetAttendanceData()
    {
        $data['user'] = '123';
        $data['begin'] = '2016-03-03';
        $data['end'] = '2016-03-03';
        $data['course'] = '443';
        $data['courseDetail'] = '1';
        return $data;
    }

    public static function getSchoolGetStaticData()
    {
        $data['user'] = '123';
        $data['begin'] = '2016-03-03';
        $data['end'] = '2016-03-03';
        $data['course'] = '443';
        return $data;
    }

    public static function getSchoolGetAttendanceDemoData()
    {
        $attendance['id'] = '123';
        $attendance['user'] = '123';
        $attendance['course'] = '456';
        $attendance['begin'] = '10:00';
        $attendance['end'] = '10:55';
        $attendances[0] = $attendance;

        $attendanceTwo['id'] = '123';
        $attendanceTwo['user'] = '123';
        $attendanceTwo['course'] = '457';
        $attendanceTwo['begin'] = '12:00';
        $attendanceTwo['end'] = '12:55';
        $attendances[1] = $attendanceTwo;

        $attendanceThree['id'] = '123';
        $attendanceThree['user'] = '123';
        $attendanceThree['course'] = '458';
        $attendanceThree['begin'] = '15:00';
        $attendanceThree['end'] = '15:55';
        $attendances[2] = $attendanceThree;

        return $attendances;
    }

    public static function getSchoolGetStaticDemoData()
    {
        $staticData['type'] = '0';
        $staticData['frequency'] = 20;
        $staticDatas[0] = $staticData;

        $staticDataTwo['type'] = '1';
        $staticDataTwo['frequency'] = 5;
        $staticDatas[1] = $staticDataTwo;

        $staticDataThree['type'] = '2';
        $staticDataThree['frequency'] = 4;
        $staticDatas[2] = $staticDataThree;

        return $staticDatas;
    }

    public static function getSchoolTimetablesDemoData()
    {
        $timetable['id'] = '123';
        $timetable['course'] = '789';
        $timetable['room'] = 'A515';
        $timetable['week'] = '5';
        $timetable['begin'] = '09:00';
        $timetable['end'] = '11:00';
        $timetables[0] = $timetable;

        $timetableTwo['id'] = '133';
        $timetableTwo['course'] = '769';
        $timetableTwo['room'] = 'A516';
        $timetableTwo['week'] = '4';
        $timetableTwo['begin'] = '11:00';
        $timetableTwo['end'] = '12:00';
        $timetables[1] = $timetableTwo;

        $timetableThree['id'] = '123';
        $timetableThree['course'] = '789';
        $timetableThree['room'] = 'A517';
        $timetableThree['week'] = '5';
        $timetableThree['begin'] = '17:00';
        $timetableThree['end'] = '18:00';
        $timetables[2] = $timetableThree;
        return $timetables;
    }

}