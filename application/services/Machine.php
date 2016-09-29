<?php
namespace services;

use base\Service;
use base\DaoFactory;
use base\ServiceFactory;
use utils\Tag;

include_once "MCommonService.php";

class Machine extends MCommonService
{
    public function getTpMachineid($machineid)
    {
        $sql = "select id from machine where machineid='" . $machineid . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);
        if (empty($data)) {
            return false;
        } else {
            return $data[0]['id'];
        }
    }

    public function isExist($machineid)
    {
        $sql = "select id from machine where machineid='" . $machineid . "' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);
        if (empty($data)) {
            return false;
        } else {
            return true;
        }
    }

    public function bindLog($tpMachineid, $tpAppid, $action)
    {
        $sql = "insert into bind_log(tp_appid, tp_machineid, createtime, ip, action) values('" . $tpAppid . "', '" . $tpMachineid . "', '" . time() . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $action . "')";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        return DaoFactory::getDao("Shard")->query($sql);
    }

    public function active($tpMachineid, $ip)
    {
        if (empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $sql = "update machine_detail set last_active_ip='" . $ip . "', last_active_time='" . time() . "' where tp_machineid='" . $tpMachineid . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        return DaoFactory::getDao("Shard")->query($sql);
    }

    public function isActive($tpMachineid)
    {
        $sql = "select last_active_time from machine_detail where tp_machineid = '" . $tpMachineid . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if (empty($data)) {
            return false;
        } else {
            $lastActiveTime = intval($data[0]['last_active_time']);
			
			//标签活跃时间为大于6分钟
			$type = $data[0]['type'];
			if($type == '10'){
				if (time() - $lastActiveTime < 360) {
					return true;
				} else {
					return false;
				}
			}else{
				if (time() - $lastActiveTime < 60) {
					//少于1分钟，就认为是活的
					return true;
				} else {
					return false;
				}
			}
        }
    }

    public function reg($machineid)
    {
        $sql = "insert into machine (machineid) values('" . $machineid . "')";
        if (DaoFactory::getDao("Main")->query($sql)) {
            $tpMachineid = DaoFactory::getDao("Main")->insert_id();

            $type = $this->getMachineType($machineid);

            //
            $sql = "insert into machine_detail(tp_machineid, type, createtime, last_active_time, create_ip, last_active_ip, isdelete) values ('" . $tpMachineid . "', '" . $type . "', '" . time() . "', '" . time() . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $_SERVER['REMOTE_ADDR'] . "', 0)";
            DaoFactory::getDao("Shard")->branchDb($tpMachineid);
            if (!DaoFactory::getDao("Shard")->query($sql)) {
                return false;
            }

            //
            $sql = "insert into teapot_state (tp_machineid, machineid) values('" . $tpMachineid . "', '" . $machineid . "')";
            return DaoFactory::getDao("Shard")->query($sql);
        }
        return false;
    }

    public function getAllCount($machineid, $status = '')
    {
        //status,在线状态，1在线，2不在线，默认全部.2015/11/30增加在线过滤
        //少于1分钟，就认为是活的
        $where = "1=1";
        if (!empty($status)) {
            include APP_PATH . "/conf/shard.php";
            $shard = $db_shard_config['shard'];
            foreach ($shard as $key => $value) {
                $arr = explode("-", $key);
                $min = intval($arr[0]);
                $shardId = $min + 1;
                if ($status == 1) {
                    $sql = "select tp_machineid from machine_detail where last_active_time > " . (time() - 60);
                } else {
                    $sql = "select tp_machineid from machine_detail where last_active_time <= " . (time() - 60);
                }
                DaoFactory::getDao("Shard")->branchDb($shardId);
                $data = DaoFactory::getDao("Shard")->query($sql);
                foreach ($data as $value) {
                    $status_id .= ',' . $value['tp_machineid'];
                }
                if ($data) {
                    $where = "id in (" . trim($status_id, ',') . ")";
                } else {
                    $where = "1!=1";
                }
            }
        }
        if (empty($machineid)) {
            $sql = "select count(1) as num from machine where " . $where;
        } else if (is_array($machineid)) {
            $sql = "select count(1) as num from machine where id in ('" . implode("','", $machineid) . "')";
        } else {
            $sql = "select count(1) as num from machine where machineid='" . $machineid . "'";
        }
        $data = DaoFactory::getDao("Main")->query($sql);
        return $data[0]['num'];
    }

    public function getList($machineid, $offset, $limit, $status = '')
    {
        //status,在线状态，1在线，2不在线，默认全部.2015/11/30增加在线过滤
        //少于1分钟，就认为是活的
        $where = "1=1";
        if (!empty($status)) {
            include APP_PATH . "/conf/shard.php";
            $shard = $db_shard_config['shard'];
            foreach ($shard as $key => $value) {
                $arr = explode("-", $key);
                $min = intval($arr[0]);
                $shardId = $min + 1;
                if ($status == 1) {
                    $sql = "select tp_machineid from machine_detail where last_active_time > " . (time() - 60);
                } else {
                    $sql = "select tp_machineid from machine_detail where last_active_time <= " . (time() - 60);
                }
                DaoFactory::getDao("Shard")->branchDb($shardId);
                $data = DaoFactory::getDao("Shard")->query($sql);
                foreach ($data as $value) {
                    $status_id .= ',' . $value['tp_machineid'];
                }
                if ($data) {
                    $where = "id in (" . trim($status_id, ',') . ")";
                } else {
                    $where = "1!=1";
                }
            }
        }

        $ret = array();
        if (empty($machineid)) {
            $sql = "select * from machine where " . $where . " order by id desc limit " . $offset . ", " . $limit . "";
        } else if (is_array($machineid)) {
            $sql = "select * from machine where id in ('" . implode("','", $machineid) . "') order by id desc limit " . $offset . ", " . $limit . "";
        } else {
            $sql = "select * from machine where machineid='" . $machineid . "' order by id desc limit " . $offset . ", " . $limit . "";
        }

        $data = DaoFactory::getDao("Main")->query($sql);
        foreach ($data as $item) {
            $tpMachineid = $item['id'];
            $machineid = $item['machineid'];
            DaoFactory::getDao("Shard")->branchDb($tpMachineid);
            $sql = "select * from machine_detail where tp_machineid='" . $tpMachineid . "' limit 1";
            $tmpData = DaoFactory::getDao("Shard")->query($sql);

            $sql = "select count(1) as num from bind where tp_machineid ='" . $tpMachineid . "'";
            $tmpData2 = DaoFactory::getDao("Main")->query($sql);
            $appNum = $tmpData2[0]['num'];

            if (!empty($tmpData)) {
                $tmpData[0]['machineid'] = $machineid;
                $tmpData[0]['appNum'] = $appNum;
                $ret[] = $tmpData[0];
            }
        }
        return $ret;
    }

    public function getAppidList($tpMachineid)
    {
        $ret = array();
        $sql = "select tp_appid from bind where tp_machineid='" . $tpMachineid . "'";
        $data = DaoFactory::getDao("Main")->query($sql);
        foreach ($data as $item) {
            $ret[] = $item['tp_appid'];
        }
        return $ret;
    }

    public function haveAppOnline($tpMachineid)
    {
        $tpAppidArray = $this->getAppidList($tpMachineid);
        if (empty($tpAppidArray)) {
            return 0;
        }
        foreach ($tpAppidArray as $tpAppid) {
            if (ServiceFactory::getService("App")->isActive($tpAppid)) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * @desc 更新地理位置
     */
    public function updateLocation()
    {
        include APP_PATH . "/conf/shard.php";
        //遍历全部分库
        $shard = $db_shard_config['shard'];

        $updateIpMapNum = 0;
        foreach ($shard as $key => $value) {
            $arr = explode("-", $key);
            $min = intval($arr[0]);
            $shardId = $min + 1;

            //phone_type, 0=unknown, 1=android, 2=ios
            $sql = "select last_active_ip, longitude, latitude, phone_type,last_active_time from app_detail where longitude != '' and latitude != ''";
            DaoFactory::getDao("Shard")->branchDb($shardId);
            $data = DaoFactory::getDao("Shard")->query($sql);

            $ipMapArray = array();
            foreach ($data as $item) {
                $ip = $item['last_active_ip'];
                $t = $item['last_active_time'];
                $longitude = $item['longitude'];
                $latitude = $item['latitude'];
                $phoneType = $item['phone_type'];

                if (isset($ipMapArray[$ip . "_" . $phoneType])) {
                    if ($t > $ipMapArray[$ip . "_" . $phoneType]['last_active_time']) {
                        $ipMapArray[$ip . "_" . $phoneType] = array(
                            "last_active_ip" => $ip,
                            "last_active_time" => $t,
                            "longitude" => $longitude,
                            "latitude" => $latitude,
                            "phone_type" => $phoneType,
                        );
                    }
                } else {
                    $ipMapArray[$ip . "_" . $phoneType] = array(
                        "last_active_ip" => $ip,
                        "last_active_time" => $t,
                        "longitude" => $longitude,
                        "latitude" => $latitude,
                        "phone_type" => $phoneType,
                    );
                }
            }


            foreach ($ipMapArray as $item) {
                $ip = $item['last_active_ip'];
                $longitude = $item['longitude'];
                $latitude = $item['latitude'];

                if (false !== strpos($longitude, "4.9E")) {
                    continue;
                }
                if (false !== strpos($latitude, "4.9E")) {
                    continue;
                }

                $longitude = formatNum($longitude);
                $latitude = formatNum($latitude);

                $phoneType = $item['phone_type'];
                if (!empty($longitude) && !empty($latitude)) {
                    //fei 2015-06-06 增加ip地址的贡献者
                    $sql = "delete from ip_map where ip='" . $ip . "' and phone_type='" . $phoneType . "'";
                    echo $sql . "\n";
                    DaoFactory::getDao("Main")->query($sql);

                    $sql = "insert into ip_map set ip='" . $ip . "', longitude='" . $longitude . "', latitude='" . $latitude . "', phone_type='" . $phoneType . "'";
                    echo $sql . "\n";
                    DaoFactory::getDao("Main")->query($sql);
                    ++$updateIpMapNum;
                }
            }
        }
        echo "update ip map num = " . $updateIpMapNum . "<br/>";

        $updateMachineNum = 0;
        foreach ($shard as $key => $value) {
            $arr = explode("-", $key);
            $min = intval($arr[0]);
            $shardId = $min + 1;

            /*
            if(isset($_GET['force']))
            {
                $sql = "select tp_machineid, last_active_ip from machine_detail where last_active_ip != ''"; //强制更新
            }
            else
            {
                $sql = "select tp_machineid, last_active_ip from machine_detail where last_active_ip != '' and last_update_ll_time < ".time()." - 86400"; //1天更新1次
            }
            */

            $sql = "select tp_machineid, last_active_ip from machine_detail where last_active_ip != ''"; //强制更新

            DaoFactory::getDao("Shard")->branchDb($shardId);
            $data = DaoFactory::getDao("Shard")->query($sql);
            foreach ($data as $item) {
                $tpMachineid = $item['tp_machineid'];
                $ip = $item['last_active_ip'];
                $flag = false;

                //ios
                $sql = "select * from ip_map where ip='" . $ip . "' and phone_type='2' limit 1";
                $detail = DaoFactory::getDao("Main")->query($sql);
                if (!empty($detail)) {
                    $sql = "update machine_detail set longitude='" . formatNum($detail[0]['longitude']) . "', latitude='" . formatNum($detail[0]['latitude']) . "', last_update_ll_time='" . time() . "' where tp_machineid='" . $tpMachineid . "' limit 1";
                    echo $sql . "\n";
                    DaoFactory::getDao("Shard")->branchDb($tpMachineid);
                    DaoFactory::getDao("Shard")->query($sql);
                    $flag = true;
                }

                //android
                //FIXME 频繁切换数据库
                $sql = "select * from ip_map where ip='" . $ip . "' and phone_type='1' limit 1";
                $detail = DaoFactory::getDao("Main")->query($sql);
                if (!empty($detail)) {
                    $sql = "update machine_detail set longitude_android='" . formatNum($detail[0]['longitude']) . "', latitude_android='" . formatNum($detail[0]['latitude']) . "', last_update_ll_time='" . time() . "' where tp_machineid='" . $tpMachineid . "' limit 1";
                    echo $sql . "\n";
                    DaoFactory::getDao("Shard")->branchDb($tpMachineid);
                    DaoFactory::getDao("Shard")->query($sql);
                    $flag = true;
                }

                if ($flag) {
                    $updateMachineNum++;
                }
            }
        }
        echo "update machine detail num = " . $updateMachineNum . "<br/>";
    }

    public function getDetail($tpMachineid)
    {
        $sql = "select * from machine_detail where tp_machineid='" . $tpMachineid . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if (empty($data)) {
            return array();
        } else {
            return $data[0];
        }
    }

    public function getAllMapData($status)
    {
        include_once APP_PATH . "/conf/shard.php";
        $shard = $db_shard_config['shard'];

        $ret = array();

        //$updateIpMapNum = 0;
        foreach ($shard as $key => $value) {
            $arr = explode("-", $key);
            $min = intval($arr[0]);
            $shardId = $min + 1;
            if (empty($status)) {
                $sql = "select longitude, latitude from machine_detail where longitude != '' and latitude != ''";
            } elseif ($status == 1) {
                $sql = "select longitude, latitude from machine_detail where longitude != '' and latitude != '' and last_active_time > " . (time() - 60);
            } elseif ($status == 2) {
                $sql = "select longitude, latitude from machine_detail where longitude != '' and latitude != '' and last_active_time <= " . (time() - 60);
            }

            DaoFactory::getDao("Shard")->branchDb($shardId);
            $data = DaoFactory::getDao("Shard")->query($sql);
            foreach ($data as $item) {
                $item['longitude'] = floatval($item['longitude']) + 0.013;
                $item['latitude'] = floatval($item['latitude']) + 0.003;
                $ret[] = array(floatval(number_format($item['longitude'], 3, '.', '')), floatval(number_format($item['latitude'], 3, '.', '')), 1);
            }
        }
        return $ret;
    }

    public function syncMapData()
    {
        include_once APP_PATH . "/conf/shard.php";
        $shard = $db_shard_config['shard'];

        foreach ($shard as $key => $value) {
            $arr = explode("-", $key);
            $min = intval($arr[0]);
            $shardId = $min + 1;

            $sql = "select tp_machineid, last_active_ip from machine_detail";
            DaoFactory::getDao("Shard")->branchDb($shardId);
            $data = DaoFactory::getDao("Shard")->query($sql);
            foreach ($data as $item) {
                $lastActiveIp = $item['last_active_ip'];
                if (empty($lastActiveIp)) {
                    continue;
                }
                $tpMachineid = $item['tp_machineid'];
                $sql = "select * from ip_map where ip='" . $last_active_ip . "' limit 1";
                $data1 = DaoFactory::getDao("Main")->query($sql);
                if ($data1) {
                    $sql = "update machine_detail set longitude='" . $data1[0]['longitude'] . "', latitude='" . $data1[0]['latitude'] . "' where tp_machineid='" . $tpMachineid . "' ";
                    DaoFactory::getDao("Shard")->branchDb($tpMachineid);
                    DaoFactory::getDao("Shard")->query($sql);
                }
            }
        }

    }

    //没有经纬度的Machine统计
    public function getMachineNoJWCount()
    {
        include APP_PATH . "/conf/shard.php";

        $total = 0;
        $shard = $db_shard_config['shard'];

        foreach ($shard as $key => $value) {
            $arr = explode("-", $key);
            $min = intval($arr[0]);
            $shardId = $min + 1;
            $sql = "select count(1) as num from machine_detail where longitude = '' and latitude = ''";
            DaoFactory::getDao("Shard")->branchDb($shardId);
            $data = DaoFactory::getDao("Shard")->query($sql);;
            $num = $data[0]['num'];
            $total += $num;
        }
        return $total;
    }


    //统计Machine在线数，默认统计全部,nojw=true时，统计无经纬度的在线数
    public function getMachineOnlineCount($nojw = false)
    {
        include APP_PATH . "/conf/shard.php";

        $total = 0;
        $shard = $db_shard_config['shard'];
        //少于1分钟，就认为是活的
        $time = time() - 60;

        $machineArray = array();

        foreach ($shard as $key => $value) {
            $arr = explode("-", $key);
            $min = intval($arr[0]);
            $shardId = $min + 1;
            if ($nojw) {
                $sql = "select count(1) as num from machine_detail where longitude = '' and latitude = '' and last_active_time >" . $time;
            } else {
                $sql = "select count(1) as num from machine_detail where last_active_time >" . $time;
            }
            DaoFactory::getDao("Shard")->branchDb($shardId);
            $data = DaoFactory::getDao("Shard")->query($sql);;
            $num = $data[0]['num'];
            $total += $num;
        }
        return $total;
    }

    public function updateRouter($tpMachineid, $routerId)
    {
        $sql = "update machine_detail set router_id='" . $routerId . "' where tp_machineid='" . $tpMachineid . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        return DaoFactory::getDao("Shard")->query($sql);;
    }

    public function clearRouter($tpMachineid, $routerId)
    {
        $sql = "update machine_detail set router_id='0' where tp_machineid='" . $tpMachineid . "' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        return DaoFactory::getDao("Shard")->query($sql);;
    }

    //查询已注册数量
    public function countreg($machineid)
    {
        $sql = "select count from regcount where id='$machineid' limit 1";
        $data = DaoFactory::getDao("Main")->query($sql);

        if (empty($data)) {
            $sql = "insert into regcount (id,count) values ('$machineid',1)";
            DaoFactory::getDao("Main")->query($sql);
            return 1;
        } else {
            $sql = "update regcount set count=`count`+1 where id='$machineid' limit 1";
            DaoFactory::getDao("Main")->query($sql);
            return $data[0]['count'] + 1;
        }

    }

    /**
     * 获取在线额Machine
     */
    public function getActiveTpMachineid()
    {
        include APP_PATH . "/conf/shard.php";
        $shard = $db_shard_config['shard'];
        $machineids = array();
        foreach ($shard as $key => $value) {
            $arr = explode("-", $key);
            $min = intval($arr[0]);
            $shardId = $min + 1;
            $sql = "select tp_machineid from machine_detail where last_active_time > " . (time() - 60);
            $data = $this->query($shardId, $sql);
            $machineids = array_merge($machineids, $data);
        }
        return $machineids;
    }

    /**
     * 获取machineid
     */
    public function getMachineid($tpMachineid)
    {
        $sql = "select machineid from machine where id='" . $tpMachineid . "'";
        $data = DaoFactory::getDao("Main")->query($sql);
        if (!$data) {
            return null;
        } else {
            return $data[0]['machineid'];
        }
    }

    /**
     * 更新机器data
     */
    public function updateMachineData($data, $tpMachineid)
    {
        $sql = "update machine_detail set data='" . $data . "' where tp_machineid='" . $tpMachineid . "'";
        $data = $this->query($tpMachineid, $sql);
        if (!$data) {
            return null;
        } else {
            return $data[0];
        }
    }

    // 多考勤机更新同一记录，获取主机的tpMachineId
    public function getMachineGroupMaster($member)
    {
        $sql = "SELECT `master` FROM `machine_group` WHERE `master` = {$member} OR `auxilliary` = {$member} LIMIT 1";
        $data = DaoFactory::getDao('Main')->query($sql);
        if ($data) {
            return $data[0]['master'];
        }
        return null;
    }

}

