<?php

use base\DaoFactory;
use services\PushMsg;

require_once "MCommonController.php";
require_once APP_PATH . "/application/services/PushMsg.php";

class PositionController extends MCommonController
{
    private $pushmsg;

    public function init()
    {
        parent::init();
        parent::disableView();
        $this->pushmsg = new PushMsg;
    }

    /**
     * desc 实现父类抽象方法
     */
    public function getControlData()
    {
        return NALL;
    }

    public function requestPositionAction($appid = '', $selfAppid = '')
    {
        $appid = parent::getAppid();
        $tpAppid = parent::getTpAppid();
        $title = 'REQUSETP';
        $content = parent::getParam('selfAppid');
        $this->pushmsg->addMessage('', $tpAppid, $appid, $title, $content, '');
    }

    public function responsePosition($appid = '',$position = '')
    {
        $appid = parent::getAppid();
        $tpAppid = parent::getTPAppid();
        $title = 'RESPONSEP';
        $content = parent::getParam('position');
        $this->pushmsg->addMessge('', $tpAppid, $appid, $title, $content, '');
    }

}
