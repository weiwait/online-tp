<?php
use utils\TestRequest;

include_once "MCommonController.php";

class ApiController extends MCommonController
{
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

    private $host = "127.0.0.1";
    private $port = "8081";
    protected $DEMO = true;

    public function init()
    {
        $this->host = explode(':', $_SERVER['HTTP_HOST'])[0];
        parent::init();
    }

    public function indexAction()
    {
    }

    public function leftAction()
    {
        $subData['用户注册'] = '/api/schoolRegist';
        $subData['获取用户详细信息'] = '/api/schoolGetUser';
        $subData['获取用户选修课程'] = '/api/schoolGetUserCourse';
        $subData['获取课程详细'] = '/api/schoolGetCourse';
        $subData['获取用户课程表'] = '/api/schoolGetTimetables';
        $subData['获取课程评价和分数'] = '/api/schoolGetValue';
        $subData['课程评价'] = '/api/schoolSetValue';
        $subData['设置课程提醒'] = '/api/schoolSetNotify';
        $subData['获取考勤记录'] = '/api/schoolGetAttendance';
        $subData['获取统计信息'] = '/api/schoolGetStatic';
        $subData['获取所有班级信息'] = '/api/schoolGetAllClass';
        $subData['打卡'] = '/api/schoolPunch';
        //        $subData['获取教室信息'] = '/api/schoolGetClass';
        //        $subData['获取教师信息'] = '/api/schoolGetClass';
        //        $subData['获取课程信息'] = '/api/schoolGetClass';1
        $data['学校考勤接口'] = $subData;
        $subDataTwo['注册'] = '/api/trackRegist';
        $subDataTwo['登录'] = '/api/trackLogin';
        $subDataTwo['添加好友'] = '/api/trackRequest';
        $subDataTwo['同意好友'] = '/api/trackApprove';
        $subDataTwo['删除好友'] = '/api/trackDelete';
        $subDataTwo['获取请求好友列表'] = '/api/trackGetRequest';
        $subDataTwo['获取好友列表'] = '/api/trackFriendList';
        $data['行踪记录接口'] = $subDataTwo;
        $this->getView()->data = $data;
    }

    public function welcomeAction()
    {
        $this->getView()->message = "这是API，有什么不懂请询问管理员。（测试请将参数demo置为1。）";
    }

    private function getRequestParam($data)
    {
        foreach ($data as $key => $value) {
            $param[$key] = trim($value['value']);
        }
        return $param;
    }

    private function getResponseData($data)
    {
        foreach ($data as $key => $value) {
            $param[$key] = trim($value['hint']);
        }
        return $param;
    }

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

    private function apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo)
    {
        $param['demo'] = $this->getParamData('', $this->DEMO);
        foreach ($param as $key => $value) {
            $param[$key]['value'] = trim($_REQUEST[$key]);
        }
        $data['param'] = $param;
        $data['requestNote'] = $requestNote;
        $data['responseNote'] = $responseNote;
        $data['responseDemo'] = $responseDemo;
        $data = array_merge($this->getUrl(), $data);
        $data['name'] = $name;
        if ("POST" == $_SERVER['REQUEST_METHOD'] && !$param['demo']['value']) {
            $method = "POST";
            $requestString = TestRequest::buildRequest($this->host, $data['targetUrl'], $method, $this->getRequestParam($param));
            $result = TestRequest::sendRequest($this->host, $this->port, $requestString);
        } else {
            $result = $responseDemo;
        }
        $this->getView()->requestString = $requestString;
        $this->getView()->result = $result;
        $this->getView()->data = $data;
    }

    public function appuiAction()
    {
        $this->toUnderScore(trim($_REQUEST['actionName']));
    }

    public function dbAction()
    {
        header("content-type:image/jpg;");
        $content = file_get_contents('images/db.jpg');
        echo $content;
    }

    public function schoolRegistAction()
    {
        $param = ApiController::getSchoolRegistDemoData();
        $name = "用户注册";
        $requestNote = "1 电话phone字段必须合法电话格式，不能为空。\r\n2 班级class字段为班级的id，不能为空。\r\n3 标签label为绑定的label的值，注意限制label的格式为20为数字,前面为10，不能为空。";
        $responseNote = "1 主要用到表为用户表user。";
        $responseDemo = $this->getShowString("注册成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolGetUserAction()
    {
        $param = ApiController::getSchoolGetUserDemoData();
        $name = "获取用户详细信息";
        $requestNote = "1 注意user是用户id。";
        $responseNote = "1 主要用到表为用户表user。";
        $responseDemo = $this->getShowString($this->getResponseData(ApiController::getSchoolRegistDemoData()));
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolGetUserCourseAction()
    {
        $param = ApiController::getSchoolGetUserCourseDemoData();
        $name = "获取用户选修课程";
        $requestNote = "1 注意user是用户id。";
        $responseNote = "1 主要用到表为用户表usercourse和course。\r\n2 通过user找到选修课程usercourse，关联course找到用户选修课的详细信息。";
        $responseDemo = $this->getShowString(ApiController::getSchoolGetUserCourseData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolGetCourseAction()
    {
        $param = ApiController::getSchoolGetCourseDemoData();
        $name = "获取课程详细";
        $requestNote = "1 注意是课程id。";
        $responseNote = "1 主要用到课程表lesson，根据课程id查找课程详细lesson。";
        $responseDemo = $this->getShowString(ApiController::getSchoolCourseDemoData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolGetTimetablesAction()
    {
        $param = ApiController::getSchoolGetTimetablesDemoData();
        $name = "获取用户课程信息";
        $requestNote = "1 注意user是用户id。";
        $responseNote = "1 查找用户修的所有课程每周详细，用到选修课usercourse和必修课compulsory，然后查找对应的lesson返回。";
        $responseDemo = $this->getShowString(ApiController::getSchoolLessonDemoData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolGetValueAction()
    {
        $param = ApiController::getSchoolGetValueDemoData();
        $name = "获取课程评价和分数";
        $requestNote = "";
        $responseNote = "1 根据用户id和课程id查询获取usercourse表内容";
        $responseDemo = $this->getShowString(ApiController::getUserCourseDData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolSetValueAction()
    {
        $param = ApiController::getSchoolSetValueDemoData();
        $name = "课程评价";
        $requestNote = "1 注意评分value限制为1~5分。";
        $responseNote = "1 主要是设置课程表usercourse。";
        $responseDemo = $this->getShowString("评价成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolSetNotifyAction()
    {
        $param = ApiController::getSchoolSetNotifyDemoData();
        $name = "设置课程提醒";
        $requestNote = "1 注意notify为整数0~3。";
        $responseNote = "1 主要是设置课程表usercourse。";
        $responseDemo = $this->getShowString("设置成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolGetAttendanceAction()
    {
        $param = ApiController::getSchoolGetAttendanceData();
        $name = "获取考勤记录";
        $requestNote = "1 注意一下日期格式如2016-08-09。";
        $responseNote = "1 主要获取一段时间内每节课的考勤记录。\r\n2 如果课程course不传表示查询全部课程。";
        $responseDemo = $this->getShowString(ApiController::getSchoolGetAttendanceDemoData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolGetStaticAction()
    {
        $param = ApiController::getSchoolGetStaticData();
        $name = "获取统计信息";
        $requestNote = "1 注意一下日期格式如2016-08-09。";
        $responseNote = "1 type：-1为缺席 0为迟到 1为正常 2为早退。\r\n2 frequency为对应的次数。\r\n3 要求服务端通过打卡记录表punch来和正常的课程时间来比较统计计算出来。\r\n4 如果课程course不传表示统计全部课程。";
        $responseDemo = $this->getShowString(ApiController::getSchoolGetStaticDemoData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolGetAllClassAction()
    {
        $param = '';
        $name = "获取所有班级信息";
        $requestNote = "";
        $responseNote = "1 主要用到班级表class，获取这个表的所有内容。";
        $responseDemo = $this->getShowString(ApiController::getSchoolGetAllClassDemoData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function schoolPunchAction()
    {
        $param = ApiController::getSchoolPunchData();
        $name = "打卡";
        $requestNote = "1 这个方法待定。";
        $responseNote = "1 这个接口比较复杂，待定。\r\n2 主要用到打卡表punch，通过打卡机判断是哪节课，如果判定是合法（即经过打卡机判定），则修改begin或end时间（如果已经有begin则更新end，反之写入begin)。";
        $responseDemo = $this->getShowString("打卡成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function trackRegistAction()
    {
        $param = ApiController::getTrackRegistData();
        $name = "注册";
        $requestNote = "1 注意phone为用户唯一标识，注意格式，同时不能为空。\r\n2 用户、密码当然不能为空。";
        $responseNote = "1 主要用到user表。";
        $responseDemo = $this->getShowString("注册成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function trackLoginAction()
    {
        $param = ApiController::getTrackLoginData();
        $name = "登录";
        $requestNote = "1 用户名为用户名或手机都可登陆。";
        $responseNote = "1 主要用到user表。";
        $responseDemo = $this->getShowString("登录成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function trackRequestAction()
    {
        $param = ApiController::getTrackRequestData();
        $name = "添加好友";
        $requestNote = "1 注意添加的好友为名字或电话。";
        $responseNote = "1 主要用到user表和friend表。friend表的status字段意思请看数据库。";
        $responseDemo = $this->getShowString("添加请求发送成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function trackDeleteAction()
    {
        $param = ApiController::getTrackDeleteData();
        $name = "删除好友";
        $requestNote = "1 注意删除的好友为名字或电话。";
        $responseNote = "1 主要是删除friend记录。\r\n";
        $responseDemo = $this->getShowString("删除好友成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function trackApproveAction()
    {
        $param = ApiController::getTrackApproveData();
        $name = "同意好友";
        $requestNote = "";
        $responseNote = "";
        $responseDemo = $this->getShowString("同意好友成功");
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function trackFriendListAction()
    {
        $param = ApiController::getTrackFriendListData();
        $name = "获取好友列表";
        $requestNote = "";
        $responseNote = "";
        $responseDemo = $this->getShowString($this->getTrackGetFriendListDemoData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    public function trackGetRequestAction()
    {
        $param = ApiController::getTrackGetRequestData();
        $name = "获取请求好友列表";
        $requestNote = "";
        $responseNote = "";
        $responseDemo = $this->getShowString($this->getTrackGetFriendListDemoData());
        $this->apiEndBuild($param, $name, $requestNote, $responseNote, $responseDemo);
    }

    private function getSchoolRegistDemoData()
    {
        $data['name'] = $this->getParamData('名字', '小明');
        $data['phone'] = $this->getParamData('电话', '18718743323');
        $data['school'] = $this->getParamData('学校', '思明学校');
        $data['class'] = $this->getParamData('班级', '1');
        $data['company'] = $this->getParamData('公司', '星弈科技');
        $data['job'] = $this->getParamData('工作', '设计师');
        $data['label'] = $this->getParamData('标签', '10123451234587654321');
        return $data;
    }

    private function getSchoolGetUserDemoData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        return $data;
    }

    private function getSchoolGetUserCourseDemoData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        return $data;
    }

    private function getSchoolGetCourseDemoData()
    {
        $data['course'] = $this->getParamData('课程', '123');
        return $data;
    }

    private function getSchoolGetTimetablesDemoData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        return $data;
    }

    private function getSchoolGetValueDemoData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        $data['course'] = $this->getParamData('课程', '123');
        return $data;
    }

    private function getSchoolSetValueDemoData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        $data['course'] = $this->getParamData('课程', '123');
        $data['value'] = $this->getParamData('评分', '3');
        return $data;
    }

    private function getSchoolSetNotifyDemoData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        $data['course'] = $this->getParamData('课程', '123');
        $data['notify'] = $this->getParamData('提醒', '3');
        return $data;
    }

    private function getSchoolGetAttendanceData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        $data['course'] = $this->getParamData('课程', '123');
        $data['begin'] = $this->getParamData('开始', '2016-03-03');
        $data['end'] = $this->getParamData('结束', '2016-03-04');
        return $data;
    }

    private function getSchoolGetStaticData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        $data['course'] = $this->getParamData('课程', '123');
        $data['begin'] = $this->getParamData('开始', '2016-03-03');
        $data['end'] = $this->getParamData('结束', '2016-03-04');
        return $data;
    }

    private function getSchoolPunchData()
    {
        $data['user'] = $this->getParamData('用户', '123');
        return $data;
    }

    private function getTrackRegistData()
    {
        $data['name'] = $this->getParamData('用户', 'ACC');
        $data['phone'] = $this->getParamData('电话', '18717616161');
        $data['password'] = $this->getParamData('密码', '323232');
        $data['company'] = $this->getParamData('公司', '兴红学技');
        $data['job'] = $this->getParamData('工作', '设计师');
        return $data;
    }

    private function getTrackLoginData()
    {
        $data['username'] = $this->getParamData('用户名', 'ACC');
        $data['password'] = $this->getParamData('密码', '34fwfre');
        return $data;
    }

    private function getTrackRequestData()
    {
        $data['user'] = $this->getParamData('请求者', 'ACC');
        $data['friend'] = $this->getParamData('被请求者', 'ABC');
        return $data;
    }

    private function getTrackDeleteData()
    {
        $data['user'] = $this->getParamData('请求者', 'ACC');
        $data['friend'] = $this->getParamData('被请求者', 'ABC');
        return $data;
    }

    private function getTrackApproveData()
    {
        $data['user'] = $this->getParamData('请求者', 'ACC');
        $data['friend'] = $this->getParamData('被请求者', 'ABC');
        return $data;
    }

    private function getTrackFriendListData()
    {
        $data['username'] = $this->getParamData('用户名', 'ACC');
        return $data;
    }

    private function getTrackGetRequestData()
    {
        $data['username'] = $this->getParamData('用户名', 'ACC');
        return $data;
    }

    private function getClassDData()
    {
        $data['id'] = 1;
        $data['name'] = "初三（2）班";
        return $data;
    }

    private function getTeacherDData()
    {
        $data['id'] = 1;
        $data['name'] = "王老师";
        return $data;
    }

    private function getCourseDData()
    {
        $data['id'] = 1;
        $data['name'] = "体育课";
        $data['teacher'] = 1;
        return $data;
    }

    private function getRoomDData()
    {
        $data['id'] = 1;
        $data['name'] = "A栋515";
        $data['attendance'] = "1";
        return $data;
    }

    private function getUserCourseDData()
    {
        $data['id'] = 1;
        $data['user'] = 1;
        $data['course'] = 1;
        $data['notify'] = 1;
        $data['value'] = 1;
        $data['score'] = 1;
        return $data;
    }

    private function getTrackUserDData()
    {
        $data['id'] = 1;
        $data['name'] = "ACC";
        $data['phone'] = "18718754345";
        return $data;
    }

    private function getSchoolGetUserCourseData()
    {
        $data['user'] = 1;

        $course['id'] = 123;
        $course['name'] = '歌剧赏析';
        $course['teacher'] = $this->getTeacherDData();
        $courses[0] = $course;

        $courseTwo['id'] = 124;
        $courseTwo['name'] = '跆拳道';
        $courseTwo['teacher'] = $this->getTeacherDData();
        $courses[1] = $courseTwo;

        $courseThree['id'] = 125;
        $courseThree['name'] = '足球';
        $courseThree['teacher'] = $this->getTeacherDData();
        $courses[2] = $courseThree;

        $data['courses'] = $courses;
        return $data;
    }

    private function getSchoolLessonDemoData()
    {
        $lesson['id'] = 123;
        $lesson['course'] = $this->getCourseDData();
        $lesson['room'] = $this->getRoomDData();
        $lesson['week'] = 5;
        $lesson['index'] = 1;
        $lessons[0] = $lesson;

        $lessonTwo['id'] = 133;
        $lessonTwo['course'] = $this->getCourseDData();
        $lessonTwo['room'] = $this->getRoomDData();
        $lessonTwo['week'] = 4;
        $lessonTwo['index'] = 4;
        $lessons[1] = $lessonTwo;

        $lessonThree['id'] = 123;
        $lessonThree['course'] = $this->getCourseDData();
        $lessonThree['room'] = $this->getRoomDData();
        $lessonThree['week'] = 5;
        $lessonThree['index'] = 6;
        $lessons[2] = $lessonThree;
        return $lessons;
    }

    private function getSchoolCourseDemoData()
    {
        $lesson['id'] = 123;
        $lesson['course'] = 1;
        $lesson['room'] = $this->getRoomDData();
        $lesson['week'] = 5;
        $lesson['index'] = 1;
        $lessons[0] = $lesson;

        $lessonTwo['id'] = 133;
        $lessonTwo['course'] = 1;
        $lessonTwo['room'] = $this->getRoomDData();
        $lessonTwo['week'] = 4;
        $lessonTwo['index'] = 4;
        $lessons[1] = $lessonTwo;

        $lessonThree['id'] = 123;
        $lessonThree['course'] = 1;
        $lessonThree['room'] = $this->getRoomDData();
        $lessonThree['week'] = 5;
        $lessonThree['index'] = 6;
        $lessons[2] = $lessonThree;
        return $lessons;
    }

    public static function getSchoolGetAttendanceDemoData()
    {
        $attendance['id'] = 123;
        $attendance['user'] = 123;
        $attendance['course'] = 456;
        $attendance['begin'] = '10:00';
        $attendance['end'] = '10:55';
        $attendances[0] = $attendance;

        $attendanceTwo['id'] = 123;
        $attendanceTwo['user'] = 123;
        $attendanceTwo['course'] = 457;
        $attendanceTwo['begin'] = '12:00';
        $attendanceTwo['end'] = '12:55';
        $attendances[1] = $attendanceTwo;

        $attendanceThree['id'] = 123;
        $attendanceThree['user'] = 123;
        $attendanceThree['course'] = 458;
        $attendanceThree['begin'] = '15:00';
        $attendanceThree['end'] = '15:55';
        $attendances[2] = $attendanceThree;

        return $attendances;
    }

    public static function getSchoolGetStaticDemoData()
    {
        $staticData['type'] = 0;
        $staticData['frequency'] = 20;
        $staticDatas[0] = $staticData;

        $staticDataTwo['type'] = 1;
        $staticDataTwo['frequency'] = 5;
        $staticDatas[1] = $staticDataTwo;

        $staticDataThree['type'] = 2;
        $staticDataThree['frequency'] = 4;
        $staticDatas[2] = $staticDataThree;

        return $staticDatas;
    }

    private function getSchoolGetAllClassDemoData()
    {
        $classes[0] = $this->getClassDData();
        $classes[1] = $this->getClassDData();
        $classes[2] = $this->getClassDData();
        return $classes;
    }

    private function getTrackGetFriendListDemoData()
    {
        $friends[0] = $this->getTrackUserDData();
        $friends[1] = $this->getTrackUserDData();
        $friends[2] = $this->getTrackUserDData();
        return $friends;
    }

    //    /**
    //     * @desc 用户注册
    //     */
    //    public function registAction()
    //    {
    //        if ($this->DEMO) {
    //            $this->checkIsDemo();
    //        }
    //        $phone = $this->getValidParam('phone', '电话', $this->TYPE_PHONE, true);
    //        $name = $this->getValidParam('name');
    //        $school = $this->getValidParam('school');
    //        $class = $this->getValidParam('class');
    //        $company = $this->getValidParam('company');
    //        $job = $this->getValidParam('job');
    //        $this->show("注册成功");
    //    }
    //
    //    /**
    //     * @desc 获取用户详细信息
    //     */
    //    public function getUserAction()
    //    {
    //        $this->checkIsDemo();
    //        $id = $this->getValidParam('user', 'user', null, true);
    //        $user = ApiController::getSchoolRegistDemoData();
    //        $user['id'] = $id;
    //        $this->show($user);
    //    }
    //
    //    /**
    //     * @desc 绑定标签
    //     */
    //    public function bindLabelAction()
    //    {
    //        $this->checkIsDemo();
    //        $user = $this->getValidParam('user', 'user', $this->TYPE_NUMBER, true);
    //        $lable = $this->getValidParam('label', 'label', $this->TYPE_NUMBER, true, "/^10[0-9]{18}$/", "应为20为数字,前面为10");
    //        $this->show("标签绑定成功");
    //    }
    //
    //    /**
    //     * @desc 获取用户选修课程
    //     */
    //    public function getUserCourseAction()
    //    {
    //        $this->checkIsDemo();
    //        $user = $this->getValidParam('user', 'user', $this->TYPE_NUMBER, true);
    //
    //        $data['user'] = $user;
    //
    //        $course['id'] = '123';
    //        $course['name'] = '歌剧赏析';
    //        $course['teacher'] = '345';
    //        $courses[0] = $course;
    //
    //        $courseTwo['id'] = '124';
    //        $courseTwo['name'] = '跆拳道';
    //        $courseTwo['teacher'] = '346';
    //        $courses[1] = $courseTwo;
    //
    //        $courseThree['id'] = '125';
    //        $courseThree['name'] = '足球';
    //        $courseThree['teacher'] = '347';
    //        $courses[2] = $courseThree;
    //
    //        $data['courses'] = $courses;
    //
    //        $this->show($data);
    //    }
    //
    //    /**
    //     * @desc 获取课程详细
    //     */
    //    public function getCourseAction()
    //    {
    //        $this->checkIsDemo();
    //        $course = $this->getValidParam('course', 'course', $this->TYPE_NUMBER, true);
    //
    //        $data['course'] = $course;
    //
    //        $data['timetables'] = ApiController::getSchoolTimetablesDemoData();
    //
    //        $this->show($data);
    //    }
    //
    //    /**
    //     * @desc 获取用户课程表
    //     */
    //    public function getTimetablesAction()
    //    {
    //        $this->checkIsDemo();
    //
    //        $user = $this->getValidParam('user', 'user', $this->TYPE_NUMBER, true);
    //
    //        $data['user'] = $user;
    //
    //        $data['timetables'] = ApiController::getSchoolTimetablesDemoData();
    //
    //        $this->show($data);
    //    }
    //
    //    /**
    //     * @desc 获取评价和分数
    //     */
    //    public function getValueAction()
    //    {
    //        $this->checkIsDemo();
    //        $user = $this->getValidParam('user', 'user', $this->TYPE_NUMBER, true);
    //        $course = $this->getValidParam('course', 'course', $this->TYPE_NUMBER, true);
    //        $value['id'] = '123';
    //        $value['user'] = $user;
    //        $value['course'] = $course;
    //        $value['value'] = '8';
    //        $value['score'] = '60';
    //        $this->show($value);
    //    }
    //
    //    /**
    //     * @desc 课程评价
    //     */
    //    public function setValueAction()
    //    {
    //        $this->checkIsDemo();
    //        $value = $this->getValidParam('value', 'value', null, true, "/^([1-5])$/", "应为1~5数字");
    //        $user = $this->getValidParam('user', 'user', $this->TYPE_NUMBER, true);
    //        $course = $this->getValidParam('course', 'course', $this->TYPE_NUMBER, true);
    //        $this->show("评价成功");
    //    }
    //
    //    /**
    //     * @desc 设置提醒
    //     */
    //    public function setNotifyAction()
    //    {
    //        $this->checkIsDemo();
    //        $notify = $this->getValidParam('notify', 'notify', null, true, "/^[0-9]$/", "应为0~9数字");
    //        $user = $this->getValidParam('user', 'user', $this->TYPE_NUMBER, true);
    //        $course = $this->getValidParam('course', 'course', $this->TYPE_NUMBER, true);
    //        $this->show("提醒成功");
    //    }
    //
    //    /**
    //     * @desc 获取考勤记录
    //     */
    //    public function getAttendanceAction()
    //    {
    //        $this->checkIsDemo();
    //        $begin = $this->getValidParam('begin', 'begin', null, true, "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "应为格式如2016-07-20");
    //        $end = $this->getValidParam('end', 'end', null, true, "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "应为格式如2016-07-20");
    //        $user = $this->getValidParam('user', 'user', $this->TYPE_NUMBER, true);
    //        $course = $this->getValidParam('course');
    //        $courseDetail = $this->getValidParam('courseDetail');
    //
    //        $data = ApiController::getSchoolGetAttendanceDemoData();
    //
    //        $this->show($data);
    //    }
    //
    //    /**
    //     * @desc 获取统计信息
    //     */
    //    public function getStaticAction()
    //    {
    //        $this->checkIsDemo();
    //        $begin = $this->getValidParam('begin', 'begin', null, true, "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "应为格式如2016-07-20");
    //        $end = $this->getValidParam('end', 'end', null, true, "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", "应为格式如2016-07-20");
    //        $user = $this->getValidParam('user', 'user', $this->TYPE_NUMBER, true);
    //        $course = $this->getValidParam('course');
    //
    //        $data = ApiController::getSchoolGetStaticDemoData();
    //
    //        $this->show($data);
    //    }

    function getControlData()
    {
        // TODO: Implement getControlData() method.
    }
}