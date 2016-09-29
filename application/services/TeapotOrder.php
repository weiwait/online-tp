<?php
namespace services;
use base\Service;
use dal\Memcached;
use base\DaoFactory;
use base\ServiceFactory;
use utils\Tag;

class TeapotOrder extends Service
{
	public function __construct(){
	}

    /**
     * @desc 获取预约的类型
     */
    private function getOrderType($week)
    {
        $type = 0;
        $len = strlen($week); 
        for($i=0; $i<$len; $i++)
        {
            $c = substr($week, $i, 1); 
            if("0" != $c)
            {
                $type = 1; 
                break;
            }
        }
        return $type;
    }

    /**
     * @desc 获取预约id
     */
    public function createOrderid($tpMachineid)
    {
        $i = 0;
        while($i<20)
        {
            $orderid = date("YmdHis").rand(10, 99);
            $sql = "select orderid from teapot_order where tp_machineid='".$tpMachineid."' and orderid='".$orderid."' limit 1";
            DaoFactory::getDao("Shard")->branchDb($tpMachineid);
            $data = DaoFactory::getDao("Shard")->query($sql);
            if(empty($data))
            {
                return $orderid; 
            }
            ++$i;
        }
        return 0;
    }

    /**
     * @desc 添加预约
     */
    public function add($tpMachineid, $orderid, $machineid, $tpAppid, $appid, $temp, $boil, $purify, $keepwarm, $heattime, $costtime, $week, $action, $startRemind, $endRemind, $noWaterRemind, $noWaterRemindThreshold)
    {
        $type = $this->getOrderType($week);
        $sql = "insert into teapot_order(tp_appid, appid, createtime, tp_machineid, orderid, type, machineid, temp, boil, purify, keepwarm, heattime, costtime, week, action, start_remind, end_remind, no_water_remind, no_water_remind_threshold) values ('".$tpAppid."','".$appid."','".time()."', '".$tpMachineid."', '".$orderid."', '".$type."', '".$machineid."', '".$temp."', '".$boil."', '".$purify."', '".$keepwarm."', '".$heattime."', '".$costtime."', '".$week."', '".$action."', '".$startRemind."', '".$endRemind."', '".$noWaterRemind."', '".$noWaterRemindThreshold."')"; 
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        return DaoFactory::getDao("Shard")->query($sql);
    }

    /**
     * @desc 判断预约是否存在
     */
    public function isExist($tpMachineid, $orderid)
    {
        $sql = "select orderid from teapot_order where tp_machineid='".$tpMachineid."' and orderid='".$orderid."' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if(empty($data))
        {
            return false; 
        }
        else
        {
            return true;
        }
    }

    /**
     * @desc 获取预约详情
     */
    public function getDetail($tpMachineid, $orderid)
    {
        $sql = "select * from teapot_order where tp_machineid='".$tpMachineid."' and orderid='".$orderid."' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if(empty($data))
        {
            return false; 
        }
        else
        {
            return $data[0];
        }
    }

    public function isDone($tpMachineid, $orderid)
    {
        $sql = "select resulttime from teapot_order where tp_machineid='".$tpMachineid."' and orderid='".$orderid."' limit 1";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if(empty($data))
        {
            return false; 
        }
        else
        {
            if(empty($data[0]['resulttime']))
            {
                return false; 
            }
            else
            {
                return true;
            }
        }
    }

    public function requestResult($tpMachineid, $orderid, $result)
    {
        $sql = "update teapot_order set result='".$result."', resulttime='".time()."' where tp_machineid='".$tpMachineid."' and orderid='".$orderid."' limit 1";        
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        return DaoFactory::getDao("Shard")->query($sql);
    }

    public function cancelOrder($tpMachineid, $tpAppid, $orderid)
    {
        //fei 2014-11-26 将resulttime设置为0
        $sql = "update teapot_order set createtime='".time()."', action='cancelheat', resulttime='0' where tp_machineid='".$tpMachineid."' and tp_appid='".$tpAppid."' and orderid='".$orderid."' limit 1"; 
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        return DaoFactory::getDao("Shard")->query($sql);
    }

    public function getOrderNum($tpMachineid, $tpAppid)
    {
        if(!empty($tpAppid))
        {
            $sql = "select count(1) as num from teapot_order where isdelete='0' and iscancel='0' and resulttime='0' and tp_machineid='".$tpMachineid."' and tp_appid='".$tpAppid."'";
        }
        else
        {
            $sql = "select count(1) as num from teapot_order where isdelete='0' and iscancel='0' and resulttime='0' and tp_machineid='".$tpMachineid."'";
        }
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if(empty($data))
        {
            return 0; 
        }
        else
        {
            return $data[0]['num'];
        }
    }

    public function getOrderList($tpMachineid, $tpAppid, $offset, $limit)
    {
        $ret = array();
        if(!empty($tpAppid))
        {
            $sql = "select * from teapot_order where isdelete='0' and iscancel='0' and resulttime='0' and tp_machineid='".$tpMachineid."' and tp_appid='".$tpAppid."' order by createtime asc limit ".$offset.",".$limit."";
        }
        else
        {
            $sql = "select * from teapot_order where isdelete='0' and iscancel='0' and resulttime='0' and tp_machineid='".$tpMachineid."' order by createtime asc limit ".$offset.",".$limit."";
        }

        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        foreach($data as $item)
        {
            $ret[] = array(
                "orderid"=>$item['orderid'], 
                "appid"=>$item['appid'], 
                "temp"=>$item['temp'], 
                "boil"=>$item['boil'], 
                "purify"=>$item['purify'], 
                "keepwarm"=>$item['keepwarm'], 
                "heattime"=>$item['heattime'], 
                "week"=>$item['week'], 
                "action"=>$item['action'], 
                "startremind"=>$item['start_remind'], 
                "endremind"=>$item['end_remind'], 
                "nowaterremind"=>$item['no_water_remind'], 
                "nowaterremindthreshold"=>$item['no_water_remind_threshold'], 
            ); 
        }
        return $ret;
    }

    public function clearAllRequest($tpMachineid)
    {
        $sql = "delete from teapot_order where tp_machineid='".$tpMachineid."'"; 
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        return DaoFactory::getDao("Shard")->query($sql);
    }


    public function getCurrHeatTpappidByDb($tpMachineid, &$targetOrderid)
    {
        $sql = "select last_orderid, last_tp_appid from machine_detail where tp_machineid='".$tpMachineid."' limit 1";    
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if(empty($data))
        {
            return false; 
        }
        $tpAppid = $data[0]['last_tp_appid'];
        $targetOrderid = $data[0]['last_orderid'];
        return $tpAppid;
    }


    //获取当前加热是由那个appid触发的
    public function getCurrHeatTpappid($tpMachineid, &$targetOrderid)
    {
        //获取那些还没有删除的，没有取消的，还在工作时间内的记录
        $sql = "select orderid, tp_appid, createtime, week, heattime, costtime from teapot_order where tp_machineid='".$tpMachineid."' and action='heat' and isdelete='0' and iscancel='0'"; 
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if(empty($data))
        {
            return "";
        }
        $now = time();
        foreach($data as $item)
        {
            $orderid = $item['orderid'];
            $orderWeek = $item['week'];
            $orderHeatTime = $item['heattime'];
            $orderCostTime = intval($item['costtime']);
            $tpAppid = $item['tp_appid'];
            $createtime = $item['createtime'];

            //判断是不是立即加热,如果是旧忽略
            if(empty($orderHeatTime))
            {
                continue;
            }

            if(empty($orderCostTime))
            {
                continue; 
            }


            //TODO............
            $minOffsetTime = 9999999;
            $minOffsetTpappid = "";
            $minOffsetOrderid = "";
            //预约加热
            for($i=0; $i<7; ++$i)
            {
                //$new = $week[$i];
                $pos = date('w', time());
                $old = $orderWeek[$pos];
                if('1' != $old)
                {
                    //没有预约
                    continue; 
                }

                $oldHour = intval(substr($orderHeatTime, 0, 2)); 
                $oldMin = intval(substr($orderHeatTime, 2, 2)); 
                $oldSec = intval(substr($orderHeatTime, 4, 2)); 

                $oldStartTime = mktime($oldHour, $oldMin, $oldSec, intval(date("m", $now)), intval(date("d", $now)), intval(date("Y", $now))); 
                //$oldEndTime = $oldStartTime + $orderCostTime;
                $offsetTime = time() - $oldStartTime;
                if($offsetTime < 0)
                {
                    //时间偏移不能为负
                    continue; 
                }

                if( $oldStartTime <= $now && $oldEndTime >= $now )
                {
                    //时间吻合
                    $targetOrderid = $orderid;
                    return $tpAppid;
                }
            }
        }
        return "";
    }

    //判断是否能预约
    public function canOrder($tpMachineid, $week, $heattime, $costtime)
    {
        //fei 2014-11-26 这里不能带上resulttime=0, 过滤那些取消预约的
        //$sql = "select week, heattime, costtime from teapot_order where tp_machineid='".$tpMachineid."' and isdelete='0' and iscancel='0' and resulttime='0'"; 
        $sql = "select week, heattime, costtime from teapot_order where tp_machineid='".$tpMachineid."' and isdelete='0' and iscancel='0' and result !='cancelorderok' and `action` = 'heat'";
        DaoFactory::getDao("Shard")->branchDb($tpMachineid);
        $data = DaoFactory::getDao("Shard")->query($sql);
        if(empty($data))
        {
            return true;
        }
        foreach($data as $item)
        {
            $orderWeek = $item['week'];
            $orderHeatTime = $item['heattime'];
            $orderCostTime = intval($item['costtime']);


            $newStartTime = strtotime($heattime);
            $newEndTime = $newStartTime + $costtime;
            $oldStartTime = strtotime($orderHeatTime);
            $oldEndTime = $oldStartTime + $orderCostTime;
            $today = intval(date('w'));

            if ('0000000' == $week && $newStartTime < time()) {
                $week[$today + 1] = '1';
            }

            if ('0000000' == $orderWeek && $oldStartTime < time()) {
                $orderWeek[$today + 1] = '1';
            }

            if ('0000000' == $week) {
                if ('0000000' == $orderWeek) {
                    if ($newEndTime < $oldStartTime || $newStartTime > $oldEndTime) {
                        //时间无重合
                        continue;
                    } else {
                        //时间重叠
                        return false;
                    }
                } else {
                    if ($orderWeek[$today] != '1') {
                        continue;
                    }
                    if ($newEndTime < $oldStartTime || $newStartTime > $oldEndTime) {
                        //时间无重合
                        continue;
                    } else {
                        //时间重叠
                        return false;
                    }
                }
            } else {
                if ('0000000' == $orderWeek) {
                    if ($week[$today] != '1') {
                        continue;
                    }
                    if ($newEndTime < $oldStartTime || $newStartTime > $oldEndTime) {
                        //时间无重合
                        continue;
                    } else {
                        //时间重叠
                        return false;
                    }
                } else {
                    for ($i = 0; $i < 7; $i++) {
                        if ('1' == $week[$i] && '1' == $orderWeek[$i]) {
                            if ($newEndTime < $oldStartTime || $newStartTime > $oldEndTime) {
                                //时间无重合
                                continue;
                            } else {
                                //时间重叠
                                return false;
                            }
                        } else {
                            continue;
                        }
                    }
                }
            }
        }
        return true;
    }

}

