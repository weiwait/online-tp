<?php

use base\DaoFactory;
use base\ServiceFactory;


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
        return null;
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
        $title = 'REQUSETP';
        $content = $title . ':' . $id . ':' . $_SESSION['user_name'];
        $content2 = ['request' => $content];
        if($appId != '' && $content != '') {
            ob_start();
            $status = $this->umeng->send($appId, "", "", false, false, $content2);
            ob_get_clean();
            if($status) {
                $ec['status'] = 1;
                echo json_encode($ec);
                $this->addPositionMsg($id, $friendId,  $title, '请求定位');
            }else {
                $ec= ['status' => 0, 'umeng' => $status];
                echo json_encode($ec);
            }
            ob_end_flush();
        }else {
            $ec['status'] = 0;
            echo json_encode($ec);
        }
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
        $position = parent::getParam('position');
        $title = 'RESPONSEP';
        if ($friendId == '0') {
            $this->addPositionMsg($id, $friendId, $title, $position);
            return;
        }
        $friend = $this->getuser('id', $friendId);
        $appId = $friend['appId'];
        $content = $title . ':' . $position . ':' . $id . ':' . $_SESSION['user_name'];
        $content2 = ['request' => $content];
        if($appId != '' & $content != '') {
            ob_start();
            $status = $this->umeng->send($appId, "", "", false, false, $content2);
            ob_get_clean();
            if($status) {
                $ec['status'] = 1;
                echo json_encode($ec);
                $this->addPositionMsg($id, $friendId, $title, $content);
            }else {
                $ec= ['status' => 0, 'umeng' => $status];
                echo json_encode($ec);
            }
            ob_end_flush();
        }else {
            $ec['status'] = 0;
            echo json_encode($ec);
        }
    }

    private function addPositionMsg($userId, $otherId, $title, $content) {
        $sql = "INSERT INTO `position_msg` (`user_id`, `other_id`, `title`, `content`, `createtime`) VALUES
        ('{$userId}', '{$otherId}', '{$title}', '{$content}'," . time() .")";
        DaoFactory::getDao("Main")->query($sql);
    }

}
