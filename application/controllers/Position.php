<?php

use base\DaoFactory;
use base\ServiceFactory;

require_once "MCommonController.php";
require_once APP_PATH . "/library/umeng/Umeng.php";

class PositionController extends MCommonController
{
    /** @var $pdo \PDO */
    private $pdo;
    /** @var $umeng services\UmengSend */
    private $umeng;
    public function init()
    {
        parent::init();
        parent::disableView();
        $this->pdo = ServiceFactory::getService('MysqlPdo')->getPdo('track');
        $this->umeng = ServiceFactory::getService('UmengSend');
    }

    /**
     * desc 实现父类抽象方法
     */
    public function getControlData()
    {
        return NALL;
    }

    private function getuser($where, $value)
    {
        $sql = "SELECT `id`, `name`, `appId` FROM `user` WHERE {$where} = ? LIMIT 1";
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $value, PDO::PARAM_STR);
        $pdoStatement->execute();
        return $pdoStatement->fetch(PDO::FETCH_ASSOC);
    }

    public function requestPositionAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'message' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $friendId = parent::getParam('id');
        $friend = $this->getuser('id', $friendId);
        $appId = $friend['appId'];
//        $appid = parent::getAppid();
//        $tpAppid = parent::getTpAppid();
        $title = 'REQUSETP';
        $content = $title . ':' . $id . ':' . $_SESSION['user_name'];
        $content = ['request' => $content];
        ob_start();
        if($appId != '' && $content != '') {
            $status = $this->umeng->send($appId, "", "", false, false, $content);
            ob_get_clean();
            if($status) {
                $this->addPositionMsg('', $appId, $content, $title, '请求定位');
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
        ob_end_flush();
    }

    public function responsePositionAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'message' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $friendId = parent::getParam('id');
        $friend = $this->getuser('id', $friendId);
        $appId = $friend['appId'];
//        $appid = parent::getAppid();
//        $tpAppid = parent::getTPAppid();
        $title = 'RESPONSEP';
        $content = $title . ':' . parent::getParam('position') . ':' . $id . ':' . $_SESSION['user_name'];
        $content = ['request' => $content];
        $selfAppid = $_SESSION['user_appId'];
        ob_start();
        if($appId != '' & $content != '') {
            $status = $this->umeng->send($appId, "", "", false, false, $content);
            $this->addPositionMsg('', $appId, $selfAppid, $title, $content);
            ob_get_clean();
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
        ob_end_flush();
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
