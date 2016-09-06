<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-02
 * Time: 14:43
 */

use base\DaoFactory;
use base\ServiceFactory;
use vendor\pictureUpload\Upload;

class TrackController extends MCommonController
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
        if (empty($password)) {
            $msg = ['status' => 0, 'message' => 'the password can not bu empty'];
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
        $username = $this->getValue('name');
        $password = $this->getValue('password');
        $appId = $this->getValue('appId');
        $data['username'] = $username;
        $data['password'] = md5($password);
        $res = $this->loginVerify($data);
        $sole = $res['appId'] == $appId? true: false;
        if (!false == $res && $sole == true) {
            $_SESSION['user_id'] = $res['id'];
            $msg = ['status' => 1, 'id' => $res['id'], 'name' => $res['name']];
        } else {
            $msg = ['status' => 0];
        }
        echo json_encode($msg);
    }

    private function loginVerify($data)
    {
        $sql = 'SELECT * FROM `user` WHERE `name` = ? AND `password` = ? LIMIT 1';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $data['username'], PDO::PARAM_STR);
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
        $user = $this->getuser('id', $friend);
        $appId = $user['appId'];
        $content = "{$friend}:{$user['name']}:ref";
        $result = $this->friendStand($id, $friend, 'request');
        switch ($result) {
            case true:
                $this->umeng->send($appId, '', $content);
                $msg = ['status' => 1, 'message' => 'requested'];
                break;
            case 1:
                $msg = ['status' => 2, 'message' => 'is already a friend'];
                break;
            case 2:
                $msg = ['status' => 3, 'message' => 'has been refused'];
                break;
            default:
                $msg = ['status' => 0, 'message' => 'request be defeated'];
        }
        echo json_encode($msg);
    }

    private function friendStand($userid, $friendid, $action)
    {
        switch ($action) {
            case 'request':
                $status = $this->friendStatus($userid, $friendid);
                if ($status == false) {
                    $sql = 'INSERT INTO `friend` (first, second, status) VALUES (?, ?, ?)';
                    $pdoStatement = $this->pdo->prepare($sql);
                    $pdoStatement->bindValue(1, $userid, PDO::PARAM_INT);
                    $pdoStatement->bindValue(2, $friendid, PDO::PARAM_INT);
                    $pdoStatement->bindValue(3, 0, PDO::PARAM_INT);
                    $pdoStatement->execute();
                    $data = $this->pdo->lastInsertId();
                    return $data > 0 ? true : false;
                } else {
                    switch ($status['status']) {
                        case 0:
                            return true;
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
                $sql = 'INSERT INTO `friend` (first, second, status) VALUES (?,?,?)';
                $pdoStatement = $this->pdo->prepare($sql);
                $pdoStatement->bindValue(1, $friendid, PDO::PARAM_INT);
                $pdoStatement->bindValue(2, $userid, PDO::PARAM_INT);
                $pdoStatement->bindValue(3, 1, PDO::PARAM_INT);
                $pdoStatement->execute();
                $result2 = $this->pdo->lastInsertId();
                $result2 = $result2 > 0 ? true : false;
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
                $sql = 'UPDATE `friend` SET `status` = ? WHERE `first` = ? AND `second` = ?';
                $pdoStatement = $this->pdo->prepare($sql);
                $pdoStatement->bindValue(1, 2, PDO::PARAM_INT);
                $pdoStatement->bindValue(2, $userid, PDO::PARAM_INT);
                $pdoStatement->bindValue(3, $friendid, PDO::PARAM_INT);
                $pdoStatement->execute();
                $result = $pdoStatement->rowCount();
                $result = $result == 1 ? true : false;
                return $result;
                break;
            case 'delete':
                $sql = 'DELETE FROM `friend` WHERE `first` = ? AND `second` = ?';
                $pdoStatement = $this->pdo->prepare($sql);
                $pdoStatement->bindValue(1, $userid, PDO::PARAM_INT);
                $pdoStatement->bindValue(2, $friendid, PDO::PARAM_INT);
                $pdoStatement->execute();
                $result = $pdoStatement->rowCount();
                return $result == 1 ? true : false;
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
        $user = $this->getuser('id', $agreeid);
        $appId = $user['appId'];
        $content = "{$agreeid}:{$user['name']}:agf";
        $result = $this->friendStand($agreeid, $id, 'agree');
        if ($result) {
            $this->umeng->send($appId, '', $content);
            $msg = ['status' => 1, 'message' => 'agree become friends'];
        } else {
            $msg = ['status' => 0, 'message' => 'operation defeated'];
        }
        echo json_encode($msg);
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
        $result = $this->friendStand($friendid, $id, 'refused');
        if ($result) {
            $msg = ['status' => 1, 'message' => 'has refuse'];
        } else {
            $msg = ['status' => 0, 'message' => 'operation defeated'];
        }
        echo json_encode($msg);
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
        $result = $this->friendStand($id, $friendid, 'delete');
        if ($result) {
            $msg = ['status' => 1, 'message' => 'delete a friend successfuly'];
        } else {
            $msg = ['status' => 0, 'message' => 'operation defeated'];
        }
        echo json_encode($msg);
    }

    public function searchUser()
    {
        $phone = $this->getValue('phone');
        $result = $this->getuser('phone', $phone);
        if ($result == false) {
            $msg = ['status' => 0, 'message' => 'the user not found'];
        } else {
            $msg = ['status' => 1, 'id' => $result['id'], 'username' => $result['name']];
        }
        echo json_encode($msg);
    }

    private function getuser($where, $value)
    {
        $sql = "SELECT `id`, `name` FROM `user` WHERE {$where} = ? LIMIT 1";
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
        echo json_encode($users);
    }

    private function allrequest($id)
    {
        $sql = 'SELECT `first` FROM `friend` WHERE `second` = ? AND `status` = 0';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
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
        $path = "./portrait/images/";
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

}
