<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-02
 * Time: 14:43
 */

use base\DaoFactory;

require_once "MCommonController.php";

class TrackController extends MCommonController
{
    public function init()
    {
        parent::init();
        parent::disableView();
    }

    public function getControlData()
    {
        return null;
    }

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
        }else {
            $password = md5($password);
        }
        $company = $this->getValue('company');
        $job = $this->getValue('job');
        $data = ['name' => $name, 'password' => $password, 'phone' => $phone, 'company' => $company, 'job' => $job];
        $result = $this->doreg($data);
        if ($result > 0) {
            $msg = ['status' => 1, 'message' => 'register successfully'];
            echo json_encode($msg);
        }else {
            $msg = ['status' => 0, 'message' => 'register defeated'];
            echo json_encode($msg);
        }
    }

    private function getValue($key)
    {
        $key = empty($_REQUEST[$key]) ? '' : $_REQUEST[$key];
        return trim($key);
    }

    private function doreg($data)
    {
        $pdo = ServiceFactory::getService('MysqlPdo')->getPdo('track');
        $sql = "insert into user(`name`, `password`, `phone`, `company`, `job`) values (?,?,?,?,?)";
        $pdoStatement = $pdo->prepare($sql);
        $pdoStatement->bindValue(1, $data['name'], PDO::PARAM_STR);
        $pdoStatement->bindValue(2, $data['password'], PDO::PARAM_STR);
        $pdoStatement->bindValue(3, $data['phone'], PDO::PARAM_STR);
        $pdoStatement->bindValue(4, $data['company'], PDO::PARAM_STR);
        $pdoStatement->bindValue(5, $data['job'], PDO::PARAM_STR);
        $pdoStatement->execute();
        return $pdo->lastInsertId();
    }
}