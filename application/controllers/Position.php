<?php

use base\DaoFactory;

require_once "MCommonController.php";
require_once APP_PATH . "/application/services/PushMsg.php";

class PositionController extends MCommonController
{

    /**
     * desc 实现父类抽象方法
     */
    public function getControlData()
    {
        return NALL;
    }

    public function positionAction($appid = '', $content = '', $title = '')
    {
        parent::disableView();
        $appid = parent::getAppid();
        $tpAppid = parent::getTpAppid();
        $data = $this->filter();
        $title = $data['title'];
        $content = $data['content'];
        $this->addMessage($tpAppid, $appid, $title, $content);
    }

    private function addMessage($tpAppid, $appid, $title, $content)
    {
        $psm = new services\PushMsg;
        $sql = "INSERT INTO `push_msg` (`tp_appid`, `appid`, `title`, `content`, createtime) VALUES ($tpAppid, '$appid', '$title','$content'," . time() . ")";
        $push = DaoFactory::getDao("Main")->query($sql);
        if ($push) {
            $psm->push();
        }
    }

    private function filter()
    {
        foreach ($_GET as $key => $item) {
            $item = htmlspecialchars($item);
            $item = addslashes($item);
            $item = quotemeta($item);
            $item = nl2br($item);
            $item = strip_tags($item);
            $item = mysql_real_escape_string($item);
            $data[$key] = $item;
        }
        return $data;
    }
}
