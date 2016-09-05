<?php

use base\DaoFactory;
use base\ServiceFactory;

class PositionController extends MCommonController
{
    public function init()
    {
        parent::init();
        parent::disableView();
    }

    /**
     * desc 实现父类抽象方法
     */
    public function getControlData()
    {
        return null;
    }

    public function requestPositionAction()
    {
        $appid = parent::getAppid();
        $tpAppid = parent::getTpAppid();
        $title = 'REQUSETP';
        $content = $title . ':' . parent::getParam('selfAppid');
        if($appid != '' & $content != '') {
            // $status = ServiceFactory::getService("PushMsg")->addMessage('', $tpAppid, $appid, $title, $content, '');
            $Umeng = new \Umeng("57c3fc82e0f55a60930001ab", "vibln1ndpibkxa0mpor4s2datlkbgtm4");
            $status = $Umeng->sendIOSCustomizedcast($appid, $title, $content, true, true);
            if($status) {
                $this->addPositionMsg($tpAppid, $appid, $content, $title, '请求定位');
                $ec['status'] = 1;
                echo json_encode($ec);
            }else {
                $ec['status'] = 0;
                echo json_encode($ec);
            }
        }else {
            $ec['status'] = 0;
            echo json_encode($ec);
        }
    }

    public function responsePositionAction()
    {
        $appid = parent::getAppid();
        $tpAppid = parent::getTPAppid();
        $title = 'RESPONSEP';
        $content = parent::getParam('position');
        $selfAppid = parent::getParam('selfAppid');
        if($appid != '' & $content != '') {
            $status = ServiceFactory::getService("PushMsg")->addMessage('', $tpAppid, $appid, $title, $content, '');
            $this->addPositionMsg($tpAppid, $appid, $selfAppid, $title, $content);
            if($status) {
                $ec['status'] = 1;
                echo json_encode($ec);
            }else {
                $ec['status'] = 0;
                echo json_encode($ec);
            }
        }else {
            $ec['status'] = 0;
            echo json_encode($ec);
        }
    }

    public function getPositionMsgAction($appid = '', $type = '', $timeone = '', $timetwo = '', $limit = '', $reorder = '') {
        $appid = @parent::getAppid();
        $type = @parent::getParam('type');
        $timeone = @parent::getParam('timeone');
        $timetwo = @parent::getParam('timetwo');
        $reorder  = @parent::getParam('reorder');
        $limit = @parent::getParam('limit');
        $limit = empty($limit) ? 3: $limit;
        $reorder = empty($reorder) ? 'desc': $reorder;
        $timetwo = empty($timetwo) ? time(): $timetwo;
        if(!empty($appid) & empty($timeone) & empty($type)) {
            echo 'hello';
            //appid查询
            $sql = "SELECT * FROM `position_msg` WHERE `appid` = '{$appid}' ORDER BY `createtime` {$reorder} LIMIT {$limit}";
        }elseif(!empty($type) & empty($appid) & empty($timeone)) {
            //类型查询
            $sql = "SELECT * FROM `position_msg` WHERE `title` = '{$type}' ORDER BY `createtime` {$reorder} LIMIT {$limit}";
        }elseif(!empty($timeone) & empty($type) & empty($appid)) {
            //时间区间查询
            $sql = "SELECT * FROM `position_msg` WHERE `createtime` >= '{$timeone}' AND `createtime` <= '{$timetwo}' ORDER BY `createtime` {$reorder} LIMIT {$limit}";
        }elseif(!empty($appid) & !empty($timeone) & empty($type)) {
            //appid和时间查询
            $sql = "SELECT * FROM `position_msg` WHERE `appid` = '{$appid}' AND `createtime` >= '{$timeone}' AND `createtime` <= '{$timetwo}' ORDER BY `createtime` {$reorder} LIMIT {$limit}";
        }elseif(!empty($appid) & empty($timeone) & !empty($type)) {
            //appid、类型查询
            $sql = "SELECT * FROM `position_msg` WHERE `appid` = '{$appid}' AND `title` = '{$type}' ORDER BY `createtime` {$reorder} LIMIT {$limit}";
        }elseif(!empty($appid) & !empty($timeone) & !empty($type)) {
            //appid、类型、时间查询
            $sql = "SELECT * FROM `position_msg` WHERE `appid` = '{$appid}' AND `title` = '{$type}' AND `createtime` >= '{$timeone}' AND `createtime` <= '{$timetwo}' ORDER BY `createtime` {$reorder} LIMIT {$limit}";
        }elseif(empty($appid) & !empty($timeone) & !empty($type)) {
            //类型和时间查询
            $sql = "SELECT * FROM `position_msg` WHERE `title` = '{$type}' AND `createtime` >= '{$timeone}' AND `createtime` <= '{$timetwo}' ORDER BY `createtime` {$reorder} LIMIT {$limit}";
        }else {
            $sql = "SELECT * FROM `position_msg` ORDER BY `createtime` {$reorder} LIMIT {$limit}";
        }
        $data = DaoFactory::getDao('main')->query($sql);
        echo json_encode($data);
    }

    private function addPositionMsg($tpAppid, $appid, $selfAppid, $title, $content) {
        $sql = "INSERT INTO `position_msg` (`tp_appid`, `appid`, `self_appid`, `title`, `content`, `createtime`) VALUES
        ({$tpAppid}, '{$appid}', '{$selfAppid}', '{$title}', '{$content}'," . time() .")";
        DaoFactory::getDao("Main")->query($sql);
    }

}
