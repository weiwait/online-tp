<?php

use base\ServiceFactory;
use vendor\pictureUpload\Upload;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-26
 * Time: 9:55
 */
class SpecificationController extends \Yaf_Controller_Abstract
{
    /** @var $pdo \PDO */
    private $pdo = null;

    public function init()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        $this->pdo = ServiceFactory::getService('MysqlPdo')->getPdo('main');
    }

    public function specificationAction()
    {
        $lang = $this->getRequest()->getParam('lang', 'cn');
        $specifications = $this->getSpecification();
        $content = $this->getSpecificationContent($lang);
        $data = [];
        foreach ($specifications as $name) {
            $data[$name['specification_name']] = [];
            foreach ($content as $image) {
                if ($image['specification_id'] == $name['id']) {
                    $data[$name['specification_name']][] = $image['image'];
                }
            }
        }
        header('Content-Type:text/json');
        $this->getResponse()->setBody(json_encode($data));
    }

    public function specificationJsonAction()
    {
        $data = $this->getSpecification();
        header('Content-Type:text/json');
        $this->getResponse()->setBody(json_encode($data));
    }

    public function specificationContentJsonAction()
    {
        $lang = $this->getRequest()->getParam('lang', 'cn');
        $id = $this->getRequest()->getParam('id');
        $sql = 'SELECT * FROM `specification_content` WHERE `specification_id` = ? AND `lang` = ? ORDER BY `sort`';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
        $pdoStatement->bindValue(2, $lang, PDO::PARAM_STR);
        $pdoStatement->execute();
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type:text/json');
        $this->getResponse()->setBody(json_encode($data));
    }

    private function getSpecification()
    {
        $sql = 'SELECT * FROM `specification` ORDER BY `sort`';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement ->execute();
        return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSpecificationContent($lang)
    {
        $sql = 'SELECT * FROM `specification_content` WHERE `lang` = ? ORDER BY `sort`';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $lang, PDO::PARAM_STR);
        $pdoStatement->execute();
        return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSpecificationAction()
    {
        if ($this->getRequest()->isGet()) {
            $this->initView();
            return $this->display('addSpecification');
        }
        $name = $_POST['name'];
        if (!trim($name)) {
            return $this->getResponse()->setBody('invalid name');
        }
        $sql = 'INSERT INTO `specification` (`specification_name`) VALUES (?)';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $name, PDO::PARAM_STR);
        $pdoStatement->execute();
        $result = $this->pdo->lastInsertId();
        if ($result) {
            $this->getResponse()->setBody('insert success');
        } else {
            $this->getResponse()->setBody('operation defeated');
        }
    }

    public function addSpecificationContentAction()
    {
        if ($this->getRequest()->isGet()) {
            $specifications = $this->getSpecification();
            $this->initView();
            return $this->display('addSpecificationContent', ['specifications' => $specifications]);
        }
        $lang = $_POST['lang'];
        $id = $_POST['id'];
        $image = $this->upload();
        if ($image == false) {
            return $this->getResponse()->setBody('operation defeated');
        }
        $sql = 'INSERT INTO `specification_content` (`specification_id`, `image`, `lang`) VALUES (?,?,?)';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
        $pdoStatement->bindValue(2, $image, PDO::PARAM_STR);
        $pdoStatement->bindValue(3, $lang, PDO::PARAM_STR);
        $pdoStatement->execute();
        $result = $this->pdo->lastInsertId();
        if ($result) {
            $this->getResponse()->setBody('insert success');
        } else {
            $this->getResponse()->setBody('operation defeated');
        }
    }

    private function upload()
    {
        $up = new Upload();
        $path = "./specification";
        $up -> set("path", $path);
        $up -> set("maxsize", 2000000);
        $up -> set("allowtype", array("gif", "png", "jpg","jpeg"));
        $up -> set("israndname", true);
        if ($up->upload('image')) {
            return ($up->getFileName());
        }else {
            return false;
        }
    }

    public function allSpecificationAction()
    {
        $data = $this->getSpecification();
        $this->initView();
        return $this->display('allSpecification', ['data' => $data]);
    }

    public function deleteOne($id, $table)
    {
        $sql = "DELETE FROM `$table` WHERE `id` = ?";
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
        $pdoStatement->execute();
        $result = $pdoStatement->rowCount();
        if ($result) {
            return $this->getResponse()->setBody('delete success');
        } else {
            return $this->getResponse()->setBody('delete defeated');
        }
    }

    public function compileSpecificationAction()
    {
        if ($this->getRequest()->isGet()) {
            $id = $this->getRequest()->getParam('id');
            $isDelete = $this->getRequest()->getParam('d');
            if ($isDelete) {
                return $this->deleteOne($id, 'specification');
            }
            $sql = "SELECT * FROM `specification` WHERE `id` = ?";
            $pdoStatement = $this->pdo->prepare($sql);
            $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
            $pdoStatement->execute();
            $data = $pdoStatement->fetch(PDO::FETCH_ASSOC);
            $this->initView();
            return $this->display('compileSpecification', ['data' => $data]);
        }
        $id = $_POST['id'];
        $sort = $_POST['sort'];
        $name = $_POST['name'];
        $sql = 'UPDATE `specification` SET `specification_name` = ?, `sort` = ? WHERE `id` = ?';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $name, PDO::PARAM_STR);
        $pdoStatement->bindValue(2, $sort, PDO::PARAM_INT);
        $pdoStatement->bindValue(3, $id, PDO::PARAM_INT);
        $pdoStatement->execute();
        $result = $pdoStatement->rowCount();
        if ($result) {
            return $this->getResponse()->setBody('update success');
        }
        $this->getResponse()->setBody('operation defeated');
    }

    public function allSpecificationContentAction()
    {
        $sql = 'SELECT * FROM `specification_content`';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->execute();
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        $this->initView();
        $this->display('allSpecificationContent', ['data' => $data]);
    }

    public function compileSpecificationContentAction()
    {
        if ($this->getRequest()->isGet()) {
            $id = $this->getRequest()->getParam('id');
            $isDelete = $this->getRequest()->getParam('d');
            if ($isDelete) {
                return $this->deleteOne($id, 'specification_content');
            }
            $sql = "SELECT * FROM `specification_content` WHERE `id` = ?";
            $pdoStatement = $this->pdo->prepare($sql);
            $pdoStatement->bindValue(1, $id, PDO::PARAM_INT);
            $pdoStatement->execute();
            $data = $pdoStatement->fetch(PDO::FETCH_ASSOC);
            $this->initView();
            return $this->display('compileSpecificationContent', ['data' => $data]);
        }
        $id = $_POST['id'];
        $sort = intval($_POST['sort']);
        $lang = $_POST['lang'];
        $image = $this->upload();
        if ($image == false) {
            return $this->getResponse()->setBody('operation defeated');
        }
        $sql = 'UPDATE `specification_content` SET `image` = ?, `sort` = ?, `lang` = ? WHERE `id` = ?';
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->bindValue(1, $image, PDO::PARAM_STR);
        $pdoStatement->bindValue(2, $sort, PDO::PARAM_INT);
        $pdoStatement->bindValue(3, $lang, PDO::PARAM_STR);
        $pdoStatement->bindValue(4, $id, PDO::PARAM_INT);
        $pdoStatement->execute();
        $result = $pdoStatement->rowCount();
        if ($result) {
            return $this->getResponse()->setBody('update success');
        }
        $this->getResponse()->setBody('operation defeated');
    }
}

