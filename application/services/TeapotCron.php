<?php

namespace services;

include_once "MCommonService.php";

class TeapotCron extends MCommonService
{
    public function executeOrder()
    {
        $time = date('His');
        $timeMini = $time - 130;
        $timeMax = $time + 130;
        $sql = "select `appid`, `heattime`, `week`, `temp`, `boil`, `purify`, `keepwarm`, `orderid`, `machineid` from `teapot_order` where `isdelete` = 0 and `heattime` != 0 and `action` = 'heat' and `heattime` > {$timeMini} and `heattime` < {$timeMax}";
        $data = $this->query('1', $sql);
        foreach ($data as $order) {
            $today = intval(date('w'));
            if ($order['week'] == '0000000' || $order['week'][$today] == '1') {
                $time = strtotime($order['heattime']) - time();
                if ($time < 20 && $time > -90) {
                    $machineid = $order['machineid'];
                    unset($order['machineid']);
                    unset($order['heattime']);
                    unset($order['week']);
                    $order['operation'] = '2';
                    startMachine($order, $machineid);
                    $this->updateStatus($order['orderid']);
                }
            }
        }
    }

    private function updateStatus($orderid)
    {
        $sql = "update `teapot_order` set `isdelete` = 1 where `orderid` = {$orderid}";
        $this->query('1', $sql);
    }
}

