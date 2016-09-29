<?php
namespace services;

use base\Service;
use dal\Memcached;
use base\DaoFactory;
use base\ServiceFactory;
use utils\Tag;

class Attendance extends Service
{
    public function __construct()
    {
    }

    public function reg($tpAppid, $username, $phonenumber, $email, $password)
    {
        $sql = "update app set username='" . $username . "',phonenumber='" . $phonenumber . "',email='" . $email . "',password='" . md5($password) . "' where id='" . $tpAppid . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);
        if ($data) {
            $sql = "select * from app where id='" . $tpAppid . "' limit 1";
            $data = DaoFactory::getDao("Main")->query($sql);
            return $data;
        } else {
            return false;
        }
    }

    public function getApp($tpAppid, $phonenumber)
    {
        $sql = "select * from app where id='" . $tpAppid . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    public function modifypassword($tpAppid, $phonenumber, $password)
    {
        $sql = "update app set password='" . md5($password) . "' where id='" . $tpAppid . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //新增考勤人
    /*public function addpunch($tpAppid, $username, $phonenumber)
    {
        $sql = "select * from app where phonenumber='" . $phonenumber . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);
		
        if (empty($data)) {
            $sql = "insert into app(appid, memo, parent_id, username, phonenumber, is_online, is_admin, is_accept) values ('" . $phonenumber . "', '', '" . $tpAppid . "', '" . $username . "', '" . $phonenumber . "', '0', '0', '0')";
            $data = DaoFactory::getDao("Main")->execute($sql);
			$insert_id = DaoFactory::getDao("Main")->insert_id();
            if ($insert_id) {
                //插入消息
				$title = '您添加成为新的考勤人';
				$content = '您添加成为新的考勤人，按确定键接受成为新的考勤人，按取消键取消接受。';
				$sql = "insert into attendance_msg (type, tp_appid, title, content, add_time, ref_id) values ('1', '" . $insert_id . "', '" . $title . "', '" . $content . "', '" . time() . "', '".$insert_id."')";
				DaoFactory::getDao("Shard")->branchDb($tpAppid);
				$data = DaoFactory::getDao("Shard")->query($sql);
				
				//添加绑定
				$sql = "insert into attendance_bind (parent_id, tp_appid) values ('" . $tpAppid . "', '" . $insert_id . "')";
				DaoFactory::getDao("Shard")->branchDb($tpAppid);
				$data = DaoFactory::getDao("Shard")->query($sql);
				
				return $data;
            } else {
                return false;
            }
        } else {
            //添加绑定
			$sql = "select count(*) as num from attendance_bind where parent_id='" . $tpAppid . "' and tp_appid='" . $data[0]['id'] . "' limit 1";
			DaoFactory::getDao("Shard")->branchDb($tpAppid);
        	$data_bind = DaoFactory::getDao("Shard")->query($sql);
			if(empty($data_bind[0]['num'])){
				$sql = "insert into attendance_bind (parent_id, tp_appid) values ('" . $tpAppid . "', '" . $data[0]['id'] . "')";
				DaoFactory::getDao("Shard")->branchDb($tpAppid);
				$ret = DaoFactory::getDao("Shard")->execute($sql);
				return $ret;
			}
        }
    }*/
    public function addpunch($parent_id, $phonenumber)
    {
        //查找考勤人
        $sql = "select * from app where phonenumber='" . $phonenumber . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);//print_r($data);exit;
        if (empty($data)) {
            return array(
                'status' => 0,
                'data' => '考勤人不存在'
            );
        }

        $child_id = $data[0]['id'];
        //判断下级是否存在
        $sql = "select * from app_child where parent_id='" . $parent_id . "' and child_id='" . $child_id . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);//print_r($data);exit;
        if ($data) {
            return array(
                'status' => 0,
                'data' => '下级用户已存在'
            );
        }

        //新增下级
        $sql = "insert into app_child(parent_id, child_id, is_accept) values ('" . $parent_id . "', '" . $child_id . "', '0')";
        $data = DaoFactory::getDao("Main")->execute($sql);
        if ($data) {
            return array(
                'status' => 1,
                'data' => '添加成功'
            );
        }

        return array(
            'status' => 0,
            'data' => '添加失败'
        );
    }

    public function acceptpunch($appid, $phonenumber, $is_accept)
    {
        $sql = "select count(*) as num from app where appid='" . $appid . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);

        if (empty($data[0]['num'])) {
            $sql = "update app set appid='" . $appid . "', is_accept='" . $is_accept . "' where phonenumber='" . $phonenumber . "' limit 1";
            $data = DaoFactory::getDao("Main")->query($sql);
            if ($data) {
                return $data;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //获取上一次考勤记录
    public function getPunchLast($tpAppid, $tpMachineid)
    {
        $sql = "select * from attendance_punch where tp_appid='" . $tpAppid . "' and tp_machineid='" . $tpMachineid . "' order by id desc limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //attendance_test
    public function getLastTest($tpAppid, $tpMachineid)
    {
        $sql = "select * from attendance_test where tp_appid='" . $tpAppid . "' and tp_machineid='" . $tpMachineid . "' order by id desc limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        return $data;
    }

    //attendance_test
    public function insertTest($tpAppid, $tpMachineid, $app_ip, $appLongitude, $appLatitude, $type, $distance, $time)
    {
        $sql = "insert into attendance_test (tp_appid, tp_machineid, createtime, create_ip, longitude, latitude, type, distance) values ('" . $tpAppid . "', '" . $tpMachineid . "', '" . $time . "', '" . $app_ip . "', '" . $appLongitude . "', '" . $appLatitude . "', '" . $type . "', '" . $distance . "')";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        return $data;
    }

    //attendance_test
    public function updateTest($tpAppid, $punch_id, $app_ip, $type, $distance, $time)
    {
        $sql = "update attendance_test set last_active_time='" . $time . "',last_active_ip='" . $app_ip . "',last_active_type='" . $type . "',last_active_distance='" . $distance . "' where id='" . $punch_id . "' limit 1";

        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        return $data;
    }

    public function getPunchMonth($tpAppid, $tpMachineid, $time)
    {
        $time = strtotime($time);
        $data = $this->getMatchingLabelId($tpAppid);
        $tpAppid = $data['tp_labelid'];
        $tpMachineid = $data['tp_machineid'];
        $sql = "select * from attendance_punch where tp_appid='" . $tpAppid . "' and tp_machineid='" . $tpMachineid . "' and FROM_UNIXTIME(createtime,'%m')=FROM_UNIXTIME($time,'%m') order by createtime asc";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //获取考勤日记录
    public function getPunchDay($tpAppid, $tpMachineid, $time)
    {
        $time = strtotime($time);
        $data = $this->getMatchingLabelId($tpAppid);
        $tpAppid = $data['tp_labelid'];
        $tpMachineid = $data['tp_machineid'];
        $sql = "select * from attendance_punch where tp_appid='" . $tpAppid . "' and tp_machineid='" . $tpMachineid . "' and FROM_UNIXTIME(createtime,'%Y%m%d')=FROM_UNIXTIME($time,'%Y%m%d') order by createtime asc";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);

        if ($data) {
            return $data;
        } else {
            return 'hello';
        }
    }

    private function getMatchingLabelId($tpAppId)
    {
        $sql = "select `tp_labelid`, `tp_machineid` from `bind_label` where `tp_appid` = {$tpAppId}";
        $data = DaoFactory::getDao('Main')->query($sql);
        return $data[0];
    }

    public function getCheckInMonth($tpAppid, $tpMachineid, $time)
    {
        $time = strtotime($time);
        $sql = "select * from attendance_punch where tp_appid='" . $tpAppid . "' and tp_machineid='" . $tpMachineid . "' and type='5'  and FROM_UNIXTIME(createtime,'%m')=FROM_UNIXTIME($time,'%m') order by createtime asc";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    public function getCheckInDay($tpAppid, $tpMachineid, $time)
    {
        $time = strtotime($time);
        $sql = "select * from attendance_punch where tp_appid='" . $tpAppid . "' and tp_machineid='" . $tpMachineid . "' and type='5' and FROM_UNIXTIME(createtime,'%d')=FROM_UNIXTIME($time,'%d') order by createtime asc";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //插入考勤数据
    public function insertPunch($tpAppid, $tpMachineid, $app_ip, $appLongitude, $appLatitude, $type, $distance, $time)
    {
        $sql = "insert into attendance_punch(tp_appid, tp_machineid, createtime, create_ip, longitude, latitude, type, distance) values ('" . $tpAppid . "', '" . $tpMachineid . "', '" . ($time ? $time : time()) . "', '" . $app_ip . "', '" . $appLongitude . "', '" . $appLatitude . "', '" . $type . "', '" . $distance . "')";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    public function addCheckIn($tpAppid, $tpMachineid, $app_ip, $appLongitude, $appLatitude, $distance)
    {
        $sql = "insert into attendance_punch (tp_appid, tp_machineid, createtime, create_ip, longitude, latitude, type, distance) values ('" . $tpAppid . "', '" . $tpMachineid . "', '" . time() . "', '" . $app_ip . "', '" . $appLongitude . "', '" . $appLatitude . "', '5', '" . $distance . "')";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    public function updateCheckIn($tpAppid, $punch_id, $app_ip, $distance)
    {
        $sql = "update attendance_punch set last_active_time='" . time() . "',last_active_ip='" . $app_ip . "',last_active_type='5',last_active_distance='" . $distance . "' where id='" . $punch_id . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    public function restartCheckIn($tpAppid, $punch_id)
    {
        $sql = "update attendance_punch set last_active_time='' where id='" . $punch_id . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    /**
     * @desc 获取最后一个打卡记录
     * @param $tpAppid
     * @param $tpMachineid
     * @return mixed
     * @throws \base\Yaf_Exception_StartupError
     */
    public function getLastCheckIn($tpAppid, $tpMachineid)
    {
        //$sql = "select * from attendance_punch where tp_appid='" . $tpAppid . "' and tp_machineid='" . $tpMachineid . "' and type='5' order by id desc limit 1";
        $sql = "select * from attendance_punch where tp_appid='" . $tpAppid . "' and tp_machineid='" . $tpMachineid . "' order by id desc limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        return DaoFactory::getDao("Shard")->query($sql);
    }

    public function insertApp($tpAppid, $tpMachineid, $app_ip, $appLongitude, $appLatitude, $type, $distance)
    {
        $sql = "insert into app_punch(tp_appid, tp_machineid, createtime, create_ip, longitude, latitude, type, distance) values ('" . $tpAppid . "', '" . $tpMachineid . "', '" . time() . "', '" . $app_ip . "', '" . $appLongitude . "', '" . $appLatitude . "', '" . $type . "', '" . $distance . "')";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //插入考勤数据到临时表
    public function insertPunchTmp($tpAppid, $tpMachineid, $app_ip, $appLongitude, $appLatitude, $type, $distance)
    {
        $sql = "insert into attendance_punch_tmp(tp_appid, tp_machineid, createtime, create_ip, longitude, latitude, type, distance) values ('" . $tpAppid . "', '" . $tpMachineid . "', '" . time() . "', '" . $app_ip . "', '" . $appLongitude . "', '" . $appLatitude . "', '" . $type . "', '" . $distance . "')";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //插入考勤数据到临时表
    public function insertPunchAttendance($tpAppid, $tpMachineid, $app_ip, $appLongitude, $appLatitude, $type, $distance)
    {
        $sql = "insert into attendance_punch_attendance(tp_appid, tp_machineid, createtime, create_ip, longitude, latitude, type, distance) values ('" . $tpAppid . "', '" . $tpMachineid . "', '" . time() . "', '" . $app_ip . "', '" . $appLongitude . "', '" . $appLatitude . "', '" . $type . "', '" . $distance . "')";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    public function updatePunch($tpAppid, $punch_id, $app_ip, $type, $distance, $time)
    {
        $sql = "update attendance_punch set last_active_time='" . ($time ? $time : time()) . "',last_active_ip='" . $app_ip . "',last_active_type='" . $type . "',last_active_distance='" . $distance . "' where id= {$punch_id}";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    public function updatePunchYesterday($tpAppid, $punch_id, $app_ip, $type, $distance)
    {
        $ytime = time() - (24 * 3600);
        $ystr = date('Y-m-d', $ytime) . ' 23:59:59';
        $time = strtotime($ystr);
        $sql = "update attendance_punch set last_active_time='" . $time . "',last_active_ip='" . $app_ip . "',last_active_type='" . $type . "',last_active_distance='" . $distance . "' where id='" . $punch_id . "' limit 1";

        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    public function updateApp($tpAppid, $punch_id, $app_ip, $type, $distance)
    {
        $sql = "update app_punch set last_active_time='" . time() . "',last_active_ip='" . $app_ip . "',last_active_type='" . $type . "',last_active_distance='" . $distance . "' where id='" . $punch_id . "' limit 1";

        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //设置工作时间
    public function setworktime($tpAppid, $week, $time)
    {
        $time_arr = explode(',', $time);
        $time_s = serialize($time_arr);
        $sql = "update attendance_set set week=$week,time='" . $time_s . "' where id=7";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //获取工作时间
    public function getworktime($tpAppid)
    {
        $sql = "select * from attendance_set where id=7";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //设置个人工作时间
    public function setworktimeapp($tpAppid, $phonenumber, $week, $time)
    {
        $time_arr = explode(',', $time);
        $time_s = serialize($time_arr);
        $sql = "update app set work_week=$week,work_time='" . $time_s . "' where phonenumber=$phonenumber";
        $data = DaoFactory::getDao("Main")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //获取工作时间
    public function getworktimeapp($tpAppid)
    {
        $sql = "select * from app where id=$tpAppid";
        $data = DaoFactory::getDao("Main")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //设置名称
    public function setname($tpAppid, $tpMachineid, $name)
    {
        $sql = "select is_admin from app where id='" . $tpAppid . "'";
        $data = DaoFactory::getDao("Main")->query($sql);

        if ($data[0][is_admin] == 1) {
            $sql = "update machine_detail set machine_name='" . $name . "' where tp_machineid='" . $tpMachineid . "' limit 1";
            DaoFactory::getDao("Shard")->branchDb($tpAppid);
            $data = DaoFactory::getDao("Shard")->query($sql);

            if ($data) {
                return $data;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //获取名称
    public function getname($tpAppid, $tpMachineid)
    {
        $sql = "select machine_name from machine_detail where tp_machineid='" . $tpMachineid . "'";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);

        if ($data) {
            return $data[0];
        } else {
            return false;
        }
    }

    //获取下级考勤人列表
    /*public function getPunchList($tpAppid)
    {
        $sql = "select id,appid,parent_id,username,phonenumber,email,is_online,is_admin,is_accept,headurl from app order by id desc";
        $data = DaoFactory::getDao("Main")->query($sql);

        if ($data) {
            $data_tree = $this->tree($data, $tpAppid);

            $sql = "select id,appid,parent_id,username,phonenumber,email,is_online,is_admin,is_accept,headurl from app where id='" . $tpAppid . "'";
            $data = DaoFactory::getDao("Main")->query($sql);

            if ($data) {
                array_unshift($data_tree, $data[0]);

            }

            return $data_tree;
        } else {
            return false;
        }
    }*/
    public function getPunchList($tpAppid)
    {
        //获取下级ID
        $sql = "select * from app_child where parent_id=$tpAppid";
        $data = DaoFactory::getDao("Main")->query($sql);
        if (empty($data)) {
            return array(
                'status' => 0,
                'data' => '暂无下级'
            );
        }

        $fields = "id,appid,parent_id,username,phonenumber,email,is_online,is_admin,is_accept,headurl";

        //获取下级详情
        $ids = implode(',', $data[0]);
        $sql = "select $fields from app where id in ($ids)";
        $data = DaoFactory::getDao("Main")->query($sql);
        if (empty($data)) {
            return array(
                'status' => 0,
                'data' => '暂无下级'
            );
        }

        $data_tree = $data[0];

        //自己
        $sql = "select $fields from app where id='" . $tpAppid . "'";
        $data = DaoFactory::getDao("Main")->query($sql);

        if ($data) {
            array_unshift($data_tree, $data[0]);
        }

        return array(
            'status' => 1,
            'data' => $data
        );
    }

    public function tree($table, $p_id = '0')
    {
        $tree = array();
        foreach ($table as $row) {
            if ($row['parent_id'] == $p_id) {
                $tmp = $this->tree($table, $row['id']);
                if ($tmp) {
                    $row['children'] = $tmp;
                } else {
                    $row['leaf'] = true;
                }
                $tree[] = $row;
            }
        }
        Return $tree;
    }

    public function un_nodes($nodes, &$list)
    {
        foreach ($nodes as $v) {
            if (!empty($v['list'])) {
                nodes($v['list'], $list);
            } else {
                $list[] = $v;
            }
        }

        return $list;
    }

    public function getBindMachineidList($tpAppid)
    {
        //$ret = array();
        $sql = "select tp_machineid from bind where tp_appid='" . $tpAppid . "'";
        $data = DaoFactory::getDao("Main")->query($sql);

        return $data;
    }

    //获取绑定的考勤机列表
    public function getBindAttendanceList($tpAppid)
    {
        $ret = array();
        $sql = "select tp_machineid from bind where tp_appid='" . $tpAppid . "'";
        $data = DaoFactory::getDao("Main")->query($sql);

        foreach ($data as $key => $item) {

        }

        return $data;
    }

    public function getMachineid($TpMachineid)
    {
        $sql = "select machineid from machine where id='" . $TpMachineid . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);
        if (empty($data)) {
            return false;
        } else {
            return $data[0]['machineid'];
        }
    }

    //根据IP获取tp_machineid
    public function getTpMachineidByIp($ip)
    {
        $sql = "select tp_machineid from machine_detail where last_active_ip='" . $ip . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);
        if (empty($data)) {
            return false;
        } else {
            return $data[0]['tp_machineid'];
        }
    }

    public function upload($file, $tpAppid)
    {
        $sql = "update app set headurl='" . $file . "' where id='" . $tpAppid . "' limit 1";
        $ret = DaoFactory::getDao("Main")->query($sql);
        if ($ret) {
            return $ret;
        } else {
            return false;
        }
    }

    public function getheadurl($tpAppid)
    {
        $sql = "select headurl from app where id='" . $tpAppid . "' limit 1";
        $ret = DaoFactory::getDao("Main")->query($sql);
        if ($ret) {
            return $ret[0]['headurl'];
        } else {
            return false;
        }
    }

    //获取消息
    public function getmsg($tpAppid)
    {
        $sql = "select id as msg_id,type,title,content,add_time,is_read from attendance_msg where tp_appid='" . $tpAppid . "'";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

    //获取消息内容
    public function getmsginfo($tpAppid, $id)
    {
        $sql = "select id as msg_id,type,title,content,add_time,is_read from attendance_msg where tp_appid='" . $tpAppid . "' and id='" . $id . "'";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if ($data) {
            $sql = "update attendance_msg set is_read='1' where tp_appid='" . $tpAppid . "' and id='" . $id . "' limit 1";
            DaoFactory::getDao("Shard")->branchDb($tpAppid);
            $ret = DaoFactory::getDao("Shard")->execute($sql);
            if ($ret) {
                return $data[0];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //标记消息已读
    public function msgread($tpAppid, $id)
    {
        //判断文章是否存在
        $sql = "select count(*) as num from attendance_msg where tp_appid='" . $tpAppid . "' and id='" . $id . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if (empty($data[0]['num'])) {
            return false;
        }

        $sql = "update attendance_msg set is_read='1' where tp_appid='" . $tpAppid . "' and id='" . $id . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $ret = DaoFactory::getDao("Shard")->execute($sql);
        if ($ret) {
            return $ret;
        } else {
            return false;
        }
    }

    //APP绑定标签
    public function labelbind($tpAppid, $labelid)
    {
        $labelid = '1001010162' . $labelid;
        $sql = "select count(*) as num from attendance_label where tp_appid='" . $tpAppid . "' and labelid='" . $labelid . "'";
        DaoFactory::getDao("Shard")->branchDb($tpAppid);
        $data = DaoFactory::getDao("Shard")->query($sql);

        if (empty($data[0]['num'])) {
            $sql = "insert into attendance_label(labelid, tp_appid) values ('" . $labelid . "', '" . $tpAppid . "')";
            DaoFactory::getDao("Shard")->branchDb($tpAppid);
            $ret = DaoFactory::getDao("Shard")->execute($sql);
            if ($ret) {
                return $ret;
            } else {
                return false;
            }
        }
    }

    /**
     * 获取标签绑定的APPID。
     * @param $labelid
     * @return array
     * @throws \base\Yaf_Exception_StartupError
     */
    public function getAppIdFromLabelId($labelid)
    {
        $sql = "select tp_appid from attendance_label where labelid='" . $labelid . "' limit 1";
        $data = DaoFactory::getDao("Shard")->query($sql);
        return $data[0]['tp_appid'];
    }

    /**
     * 检查没有发送离开推送的标签,发送并做好标记。
     */
    public function checkLabelLeave()
    {
        $sql = "select * from attendance_punch_label where leave_note<>1 order by id desc limit 100";
        $data = DaoFactory::query('1', $sql);
        $now = time();
        foreach ($data as $item) {
            $time_dirr = $now - $item['leave_time'];
            if ($time_dirr > 360) {
                ServiceFactory::getService("Attendance")->pushLabelNotification($item['tp_labelid'], true);
                $this->leaveLabel($item['id']);
            }
        }
    }

    /**
     * 标签离开处理
     * @param $id
     * @return mixed
     * @throws \base\Yaf_Exception_StartupError
     */
    public function leaveLabel($id)
    {
        $sql = "update attendance_punch_label set leave_note=1 where id='" . $id . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($id);
        $res = DaoFactory::getDao("Shard")->query($sql);
        return $res;
    }

    public function pushLabelNotification($labelId, $isLeave = false)
    {
        if (\AppController::$TESTPUSH && $labelId == '1112') {
            \AppController::yaojunLog('C1E9B944-EB75-4C89-8FF2-B6F814A76C3E', '||||||||||A silent...', 4);
            ServiceFactory::getService("PushMsg")->addSilentMessage('456', 'C1E9B944-EB75-4C89-8FF2-B6F814A76C3E');
            return;
        }
        if (\AppController::$TESTPUSH && $labelId == '1105') {
            \AppController::wangLog('1460347011477710', '||||||||||A silent...', 4);
            ServiceFactory::getService("PushMsg")->addSilentMessage('342', '1460347011477710');
            return;
        }
        if (\AppController::$TESTPUSH && $labelId == '1107') {
            \AppController::liuLog('88538244-638A-49ED-BDB3-D5128330F40E', '||||||||||A silent...', 4);
            ServiceFactory::getService("PushMsg")->addSilentMessage('385', '88538244-638A-49ED-BDB3-D5128330F40E');
            return;
        }
        $tpAppid = ServiceFactory::getService("Attendance")->getAppIdFromLabelId($labelId);
        if ($tpAppid) {
            $appid = ServiceFactory::getService("App")->getAppid($tpAppid);
            if ($isLeave) {
                ServiceFactory::getService("PushMsg")->addSilentMessage($tpAppid, $appid);
            } else {
                ServiceFactory::getService("PushMsg")->addSilentMessage($tpAppid, $appid);
            }
        }
    }

    //插入标签数据
    public function insertLabel($tpLabelid, $tpMachineid, $ip, $longitude, $latitude)
    {
        $sql = "insert into attendance_punch_label(tp_labelid, tp_machineid, create_time, create_ip, longitude, latitude, leave_time, leave_ip, leave_longitude, leave_latitude) values ('" . $tpLabelid . "', '" . $tpMachineid . "', '" . time() . "', '" . $ip . "', '" . $longitude . "', '" . $latitude . "', '" . time() . "', '" . $ip . "', '" . $longitude . "', '" . $latitude . "')";
        DaoFactory::getDao("Shard")->branchDb($tpLabelid);
        $res = DaoFactory::getDao("Shard")->query($sql);
        return $res;
    }

    //更新标签数据
    public function updateLabel($tpLabelid, $tpMachineid, $ip, $id, $longitude, $latitude)
    {
        $sql = "update attendance_punch_label set leave_time = unix_timestamp(), leave_ip= '{$ip}', leave_longitude = '{$longitude}', `leave_latitude` = '{$latitude}' where id = {$id} limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpLabelid);
        $res = DaoFactory::getDao("Shard")->query($sql);
        return $res;
    }

    //获取最新标签记录
    public function getLastLabel($tpLabelid, $tpMachineid)
    {
        $sql = "select * from attendance_punch_label where tp_labelid='" . $tpLabelid . "' and tp_machineid='" . $tpMachineid . "' order by id desc limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpLabelid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if (empty($data)) {
            return array();
        } else {
            return $data[0];
        }
    }

    //获取标签使用记录数
    public function getActionLogNumForAdmin($tpMachineid)
    {
        $sql = "select count(1) as num from attendance_punch_label where tp_labelid='" . $tpMachineid . "'";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if (empty($data)) {
            return 0;
        } else {
            return $data[0]['num'];
        }
    }

    //获取标签使用记录
    public function getActionLogListForAdmin($tpMachineid, $offset, $limit)
    {
        $ret = array();
        $sql = "select * from attendance_punch_label where tp_labelid='" . $tpMachineid . "' order by id desc limit " . $offset . ", " . $limit . "";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if (empty($data)) {
            return array();
        } else {
            foreach ($data as $item) {
                $ret[] = array(
                    "labelid" => $this->getMachineid($item['tp_labelid']),
                    "machineid" => $this->getMachineid($item['tp_machineid']),
                    "create_time" => date("Y-m-d H:i:s", $item['create_time']),
                    "create_ip" => $item['create_ip'],
                    "longitude" => $item['longitude'],
                    "latitude" => $item['latitude'],

                    "leave_time" => date("Y-m-d H:i:s", $item['leave_time']),
                    "leave_ip" => $item['leave_ip'],
                    "leave_longitude" => $item['leave_longitude'],
                    "leave_latitude" => $item['leave_latitude'],
                );
            }
        }
        return $ret;
    }

    public function timediff($begin_time, $end_time)
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

    public function getColleague($parentId)
    {
        $sql = "SELECT `username`, `phonenumber`, `email`, `headurl` FROM `app` WHERE `parent_id` = {$parentId}";
        return DaoFactory::getDao('Main')->query($sql);
    }
}