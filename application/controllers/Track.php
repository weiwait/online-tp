<?php


use base\DaoFactory;
use base\ServiceFactory;
use vendor\pictureUpload\Upload;

class TrackController extends MCommonController
{
    /** @var $pdo \PDO */
    private $pdo;
    /** @var $mainPdo \PDO */
    private $mainPdo;
    /** @var $umeng services\UmengSend */
    private $umeng;

    public function init()
    {
        header('Content-Type: text/json');
        parent::init();
        parent::disableView();
        $this->pdo = ServiceFactory::getService('MysqlPdo')->getPdo('track');
        $this->mainPdo = ServiceFactory::getService('MysqlPdo')->getPdo('main');
        $this->umeng = ServiceFactory::getService('UmengSend');
    }

    /**
     * @return null 父类抽象方法
     */
    public function getControlData()
    {
        return null;
    }

    /**
     *注册一个用户，需要名称、密码、手机号码、公司名称、工作
     */
    public function registerAction()
    {
        $phone = $this->getValue('phone');
        if (preg_match('/^((13)|(15)|(17)|(18)){1}\d{9}$/', $phone) != 1) {
            $msg = ['status' => 0, 'message' => 'the phone is incorrectness'];
            echo json_encode($msg);
            return;
        }
        $sql = "select phone from user where phone = '{$phone}' limit 1";
        $result = DaoFactory::getDao('track')->query($sql);
        if (!count($result) == 0) {
            $msg = ['status' => 0, 'message' => 'the phone is registered'];
            echo json_encode($msg);
            return;
        }
        $name = $this->getValue('name');
        if (empty($name)) {
            $msg = ['status' => 0, 'message' => 'the name can not be empty'];
            echo json_encode($msg);
            return;
        }
        $password = $this->getValue('password');
        if (preg_match('/^[\w_]{9,25}$/', $password) != 1) {
            $msg = ['status' => 0, 'message' => 'the password is incorrectness'];
            echo json_encode($msg);
            return;
        } else {
            $password = md5($password);
        }
        $company = $this->getValue('company');
        $job = $this->getValue('job');
        $appId = $this->getValue('appId');
        $portrait = $this->upload();
        $portrait = $portrait == false? '': $portrait;
        $data = ['name' => $name, 'password' => $password, 'phone' => $phone, 'company' => $company, 'job' => $job, 'appId' => $appId, 'portrait' => $portrait];
        $result = $this->doReg($data);
        if ($result > 0) {
            $msg = ['status' => 1, 'message' => 'register successfully'];
        } else {
            $msg = ['status' => 0, 'message' => 'register defeated'];
        }
        echo json_encode($msg);
    }

    /**
     * @param $key [表单的name]
     * @return string [name 的值]
     */
    private function getValue($key)
    {
        $key = empty($_REQUEST[$key]) ? '' : $_REQUEST[$key];
        return trim($key);
    }

    /**
     * @param $data [待注册用户的信息]
     * @return string [插入成功后的主键id]
     */
    private function doReg($data)
    {
        $sql = 'INSERT INTO `user`(`name`, `password`, `phone`, `company`, `job`, `appId`, `portrait`) VALUES (?,?,?,?,?,?,?)';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $data['name'], PDO::PARAM_STR);
        $pdoStatement->bindValue(2, $data['password'], PDO::PARAM_STR);
        $pdoStatement->bindValue(3, $data['phone'], PDO::PARAM_STR);
        $pdoStatement->bindValue(4, $data['company'], PDO::PARAM_STR);
        $pdoStatement->bindValue(5, $data['job'], PDO::PARAM_STR);
        $pdoStatement->bindValue(6, $data['appId'], PDO::PARAM_STR);
        $pdoStatement->bindValue(7, $data['portrait'], PDO::PARAM_STR);
        $pdoStatement->execute();
        return $this->pdo->lastInsertId();
    }

    public function loginAction()
    {
        $phone = $this->getValue('phone');
        $password = $this->getValue('password');
        $appId = $this->getValue('appId');
        $data['phone'] = $phone;
        $data['password'] = md5($password);
        $res = $this->loginVerify($data);
        if (!false == $res) {
            $_SESSION['user_id'] = $res['id'];
            $_SESSION['user_name'] = $res['name'];
            $_SESSION['user_appId'] = $res['appId'];
            if (!empty($appId)) {
                $this->updateOne('user', 'appId', 'id', $res['id'], $appId, 'string');
            }
            $msg = ['status' => 1, 'id' => $res['id'], 'name' => $res['name'], 'phone' => $res['phone'], 'portrait' => $res['portrait']];
        } else {
            $msg = ['status' => 0];
        }
        echo json_encode($msg);
    }

    private function loginVerify($data)
    {
        $sql = 'SELECT * FROM `user` WHERE `phone` = ? AND `password` = ? LIMIT 1';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $data['phone'], PDO::PARAM_STR);
        $pdoStatement->bindValue(2, $data['password'], PDO::PARAM_STR);
        $pdoStatement->execute();
        $result = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    private function updateOne($table, $setField, $whereField, $whereValue, $value, $valueType)
    {
        $type = $valueType == 'string'? PDO::PARAM_STR: PDO::PARAM_INT;
        $sql = "UPDATE {$table} SET {$setField} = ? WHERE {$whereField} = {$whereValue}";
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $value, $type);
        $pdoStatement->execute();
        return $pdoStatement->rowCount();
    }

    public function requestAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'message' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $friend = $this->getValue('id');
        if ($friend == $id) {
            $msg = ['status' => 0, 'message' => 'request be defeated'];
            echo json_encode($msg);
            return;
        }
        $user = $this->getuser('id', $friend);
        $appId = $user['appId'];
        $content = "{$friend}:{$user['name']}";
        $content = ['ask' => $content];
        $notification = $user['name'] . '请求加为好友';
        $result = $this->friendStand($id, $friend, 'request');
        ob_start();
        switch ($result) {
            case 11:
                $status = $this->umeng->send($appId, 'track', $notification, true, true, $content);
                $msg = ['status' => 1, 'message' => 'requested', 'umeng' => $status];
                break;
            case 1:
                $msg = ['status' => 2, 'message' => 'is already a friend'];
                break;
            case 2:
                $msg = ['status' => 3, 'message' => 'has been refused'];
                break;
            case 0:
                $msg = ['status' => 0, 'message' => 'request be defeated'];
                break;
            default:
                $msg = ['status' => 0, 'message' => 'request be defeated'];
        }
        ob_get_clean();
        echo json_encode($msg);
        ob_end_flush();
    }

    private function friendStand($userid, $friendid, $action)
    {
        switch ($action) {
            case 'request':
                $status = $this->friendStatus($userid, $friendid);
                if ($status == false) {
                    $sql = 'INSERT INTO `friend` (`first`, `second`, status) VALUES (?, ?, ?)';
                    $pdoStatement = $this->pdo->prepare($sql);
                    $pdoStatement->bindValue(1, $userid, PDO::PARAM_INT);
                    $pdoStatement->bindValue(2, $friendid, PDO::PARAM_INT);
                    $pdoStatement->bindValue(3, 0, PDO::PARAM_INT);
                    $pdoStatement->execute();
                    $data = $this->pdo->lastInsertId();
                    return $data > 0 ? 11 : 0;
                } else {
                    switch ($status['status']) {
                        case 0:
                            return 0;
                            break;
                        case 1:
                            return 1;
                            break;
                        case 2:
                            return 2;
                            break;
                    }
                }
                break;
            case 'agree':
                $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
                $this->pdo->beginTransaction();
                $sql = 'UPDATE `friend` SET `status` = ? WHERE `first` = ? AND `second` = ?';
                $pdoStatement = $this->pdo->prepare($sql);
                $pdoStatement->bindValue(1, 1, PDO::PARAM_INT);
                $pdoStatement->bindValue(2, $userid, PDO::PARAM_INT);
                $pdoStatement->bindValue(3, $friendid, PDO::PARAM_INT);
                $pdoStatement->execute();
                $result1 = $pdoStatement->rowCount();
                $result1 = $result1 == 1 ? true : false;
                $status = $this->friendStatus($friendid, $userid);
                if ($status == false) {
                    $sql = 'INSERT INTO `friend` (first, second, status) VALUES (?,?,?)';
                    $pdoStatement = $this->pdo->prepare($sql);
                    $pdoStatement->bindValue(1, $friendid, PDO::PARAM_INT);
                    $pdoStatement->bindValue(2, $userid, PDO::PARAM_INT);
                    $pdoStatement->bindValue(3, 1, PDO::PARAM_INT);
                    $pdoStatement->execute();
                    $result2 = $this->pdo->lastInsertId();
                    $result2 = $result2 > 0 ? true : false;
                } else {
                    $sql = 'UPDATE `friend` SET `status` = ? WHERE `first` = ? AND `second` = ?';
                    $pdoStatement = $this->pdo->prepare($sql);
                    $pdoStatement->bindValue(1, 1, PDO::PARAM_INT);
                    $pdoStatement->bindValue(2, $friendid, PDO::PARAM_INT);
                    $pdoStatement->bindValue(3, $userid, PDO::PARAM_INT);
                    $pdoStatement->execute();
                    $result2 = $pdoStatement->rowCount();
                    $result2 = $result2 == 1 ? true : false;
                }
                if ($result1 && $result2) {
                    $this->pdo->commit();
                    $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
                    return true;
                }
                $this->pdo->rollBack();
                $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
                return false;
                break;
            case 'refused':
//                $sql = 'UPDATE `friend` SET `status` = ? WHERE `first` = ? AND `second` = ?';
                $sql = 'DELETE FROM `friend` WHERE `first` = ? AND `second` = ?';
                $pdoStatement = $this->pdo->prepare($sql);
                $pdoStatement->bindValue(1, $userid, PDO::PARAM_INT);
                $pdoStatement->bindValue(2, $friendid, PDO::PARAM_INT);
                $pdoStatement->execute();
                $result = $pdoStatement->rowCount();
                $result = $result == 1 ? true : false;
                return $result;
                break;
            case 'delete':
                $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
                $this->pdo->beginTransaction();
                $sql = 'DELETE FROM `friend` WHERE `first` = ? AND `second` = ?';
                $pdoStatement = $this->pdo->prepare($sql);
                $pdoStatement->bindValue(1, $userid, PDO::PARAM_INT);
                $pdoStatement->bindValue(2, $friendid, PDO::PARAM_INT);
                $pdoStatement->execute();
                $result = $pdoStatement->rowCount();
                $result = $result == 1 ? true : false;
                $sql = 'DELETE FROM `friend` WHERE `first` = ? AND `second` = ?';
                $pdoStatement = $this->pdo->prepare($sql);
                $pdoStatement->bindValue(1, $friendid, PDO::PARAM_INT);
                $pdoStatement->bindValue(2, $userid, PDO::PARAM_INT);
                $pdoStatement->execute();
                $result2 = $pdoStatement->rowCount();
                $result2 = $result2 == 1 ? true : false;
                if ($result && $result2) {
                    $this->pdo->commit();
                    $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
                    return true;
                }
                $this->pdo->rollBack();
                $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
                return false;
                break;
        }
    }

    private function friendStatus($userid, $friendid)
    {
        $sql = 'SELECT * FROM `friend` WHERE `first` = ? AND `second` = ? LIMIT 1';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $userid, PDO::PARAM_INT);
        $pdoStatement->bindValue(2, $friendid, PDO::PARAM_INT);
        $pdoStatement->execute();
        $data = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function approveAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'messge' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $agreeid = $this->getValue('id');
        $frienStatus = $this->friendStatus($agreeid, $id);
        if ($frienStatus == false) {
            $msg = ['status' => 0, 'message' => 'operation defeated'];
            echo json_encode($msg);
            return;
        }
        if ($frienStatus['status'] == 1) {
            $msg = ['status' => 0, 'message' => 'operation defeated'];
            echo json_encode($msg);
            return;
        }
        $user = $this->getuser('id', $agreeid);
        $appId = $user['appId'];
        $content = "{$id}:{$_SESSION['user_name']}";
        $content = ['approve' => $content];
        $notification = $_SESSION['user_name']. '已同意好友请求';
        $result = $this->friendStand($agreeid, $id, 'agree');
        ob_start();
        if ($result) {
            $status = $this->umeng->send($appId, 'track', $notification, true, true, $content);
            $msg = ['status' => 1, 'message' => 'agree become friends', 'umeng' => $status];
        } else {
            $msg = ['status' => 0, 'message' => 'operation defeated'];
        }
        ob_get_clean();
        echo json_encode($msg);
        ob_end_flush();
    }

    public function refusedAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'messge' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $friendid = $this->getValue('id');
        $user = $this->getuser('id', $friendid);
        $result = $this->friendStand($friendid, $id, 'refused');
        if ($result) {
            $status = $this->umeng->send($user['appId'], 'track', $_SESSION['user_name'] . '拒绝了您的好友请求', true, true);
            $msg = ['status' => 1, 'message' => 'has refuse', 'umeng' => $status];
        } else {
            $msg = ['status' => 0, 'message' => 'operation defeated'];
        }
        ob_get_clean();
        echo json_encode($msg);
        ob_end_flush();
    }

    public function friendDeleteAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'messge' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $friendid = $this->getValue('id');
        $user = $this->getuser('id', $friendid);
        $appId = $user['appId'];
        $content = ['delete' => $friendid];
        $result = $this->friendStand($id, $friendid, 'delete');
        if ($result) {
            $status = $this->umeng->send($appId, 'track', $user['name'] . '与您解除好友关系', true, true, $content);
            $msg = ['status' => 1, 'message' => 'delete a friend successfully', 'umeng' => $status];
        } else {
            $msg = ['status' => 0, 'message' => 'operation defeated'];
        }
        ob_get_clean();
        echo json_encode($msg);
        ob_end_flush();
    }

    public function searchuserAction()
    {
        $phone = $this->getValue('phone');
        $result = $this->getuser('phone', $phone);
        if ($result == false) {
            $msg = ['status' => 0, 'message' => 'the user not found'];
        } else {
            $msg = ['status' => 1, 'id' => $result['id'], 'name' => $result['name']];
        }
        echo json_encode($msg);
    }

    private function getuser($where, $value)
    {
        $sql = "SELECT `id`, `name`, `appId` FROM `user` WHERE {$where} = ? LIMIT 1";
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $value, PDO::PARAM_STR);
        $pdoStatement->execute();
        return $pdoStatement->fetch(PDO::FETCH_ASSOC);
    }


    public function friendListAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'messge' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $users = $this->allfriend($id);
        echo json_encode($users);
    }

    private function allfriend($id)
    {
        $sql = 'SELECT `second` FROM `friend` WHERE `first` = ? AND `status` = 1';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
        $pdoStatement->execute();
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        $user = [];
        foreach ($data as $value) {
            $user[] = $this->getuser('id', $value['second']);
        }
        return $user;
    }

    public function getRequestAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'messge' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $users = $this->allrequest($id);
        $positions = $this->allGetMePosition($id);
        $message = array_merge($users, $positions);
        echo json_encode($message);
    }

    private function allGetMePosition($id)
    {
        $sql = 'SELECT * FROM `position_msg` WHERE `user_id` = ? AND `title` = ? AND `isdelete` = ? AND `other_id` > ?';
        $pdoStatement = $this->mainPdo->prepare($sql);
        $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
        $pdoStatement->bindValue(2, 'RESPONSEP', PDO::PARAM_STR);
        $pdoStatement->bindValue(3, 0, PDO::PARAM_INT);
        $pdoStatement->bindValue(4, 0, PDO::PARAM_INT);
        $pdoStatement->execute();
        $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        $data = [];
        foreach ($result as $value) {
            $name = $this->getuser('id', $value['other_id'])['name'];
            $data[] = ['name' =>$name, 'time' => $value['createtime']];
        }
        return $data;
    }

    public function deleteHistoryAction()
    {
        $id = $this->getValue('id');
        $sql = 'UPDATE `position_msg` SET `isdelete` = ? WHERE `id` = ?';
        $pdoStatement = $this->mainPdo->prepare($sql);
        $pdoStatement->bindValue(1, 1, PDO::PARAM_INT);
        $pdoStatement->bindValue(2, $id, PDO::PARAM_INT);
        $pdoStatement->execute();
        $result = $pdoStatement->rowCount();
        if ($result == 1) {
            $msg = ['status' => 1];
        } else {
            $msg = ['status' => 0];
        }
        echo json_encode($msg);
    }

    private function allrequest($id)
    {
        $sql = 'SELECT `first` FROM `friend` WHERE `second` = ? AND `status` = ?';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
        $pdoStatement->bindValue(2, 0, PDO::PARAM_INT);
        $pdoStatement->execute();
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        $user = [];
        foreach ($data as $value) {
            $user[] = $this->getuser('id', $value['first']);
        }
        return $user;
    }

    private function upload()
    {
        $up = new Upload();
        $path = "./portrait";
        $up -> set("path", $path);
        $up -> set("maxsize", 2000000);
        $up -> set("allowtype", array("gif", "png", "jpg","jpeg"));
        $up -> set("israndname", true);
        if ($up->upload('portrait')) {
            return ($up->getFileName());
        }else {
            return false;
        }
    }

    public function quitAction()
    {
        if ($_SESSION['user_id'] != null) {
            unset($_SESSION['user_id']);
            $msg = ['status' => 1];
        } else {
            $msg = ['status' => 0];
        }
        echo json_encode($msg);
    }

    public function modificationAction()
    {
        $phone = $this->getValue('phone');
        $password = $this->getValue('password');
        if (preg_match('/^[\w_]{9,25}$/', $password) != 1) {
            $msg = ['status' => 0, 'message' => 'the password is incorrectness'];
            echo json_encode($msg);
            return;
        } else {
            $password = md5($password);
        }
        $result = $this->updateOne('user', 'password', 'phone', $phone, $password, 'string');
        if ($result > 0) {
            $msg = ['status' => 1, 'message' => 'modification success'];
        } else {
            $msg = ['status' => 0, 'message' => 'modification defeated'];
        }
        echo json_encode($msg);
    }

    public function lastPositionAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'messge' => 'login please'];
            echo json_encode($msg);
            return;
        }
        $friendId = $this->getValue('id');
        $sql = 'SELECT `content` FROM `position_msg` WHERE `user_id` = ? AND `other_id` = ? ORDER BY `createtime` DESC LIMIT 1';
        $pdoStatement = $this->mainPdo->prepare($sql);
        $pdoStatement->bindValue(1, $friendId, PDO::PARAM_INT);
        $pdoStatement->bindValue(2, 0, PDO::PARAM_INT);
        $pdoStatement->execute();
        $result = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $this->addGetMeHistory($id, $friendId, $result['content']);
            echo json_encode($result);
        } else {
            echo json_encode(['message' => 'no position info']);
        }
    }

    private function addGetMeHistory($id, $friendId, $position)
    {
        $user = $this->getuser('id', $friendId);
        $title = 'RESPONSEP';
        $content = "{$title}:{$position}:{$user['id']}:{$user['name']}";
        $sql = 'INSERT INTO `position_msg` (`user_id`, `other_id`, `title`, `content`, `createtime`) VALUES (?,?,?,?,?)';
        $pdoStatement = $this->mainPdo->prepare($sql);
        $pdoStatement->bindValue(1, $friendId, PDO::PARAM_INT);
        $pdoStatement->bindValue(2, $id, PDO::PARAM_INT);
        $pdoStatement->bindValue(3, $title, PDO::PARAM_STR);
        $pdoStatement->bindValue(4, $content, PDO::PARAM_STR);
        $pdoStatement->bindValue(5, time(), PDO::PARAM_STR);
        $pdoStatement->execute();
    }

    public function renameAction()
    {
        $id = $_SESSION['user_id'];
        if ($id == null) {
            $msg = ['status' => 0, 'messge' => 'login please'];
            echo json_encode($msg);
            return;
        }

        $newName = $this->getValue('name');
        $result = $this->updateOne('user', 'name', 'id', $id, $newName, 'string');
        if ($result) {
            $msg = ['status' => 1];
        } else {
            $msg = ['status' => 0];
        }
        echo json_encode($msg);
    }
}
