<?php

use base\ServiceFactory;

class WeiwaitController extends MCommonController
{
    private $content = '';

    public function init()
    {
        \Yaf_Dispatcher::getInstance()->disableView();
    }
    public function testAction() {
        $url = 'http://read.qidian.com/BookReader/0OAc8-pOJYI1,HhP1NZYaRKwex0RJOkJclQ2.aspx';
        $url1 = 'http://www.xxbiquge.com/1_1408/1556280.html';
        $url2 = 'http://baidu.book.3g.cn/xuan/index.php?m=Book&a=content&wsto=alading&bookid=248341&menuid=253';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $str = curl_exec($ch);
        curl_close($ch);
        header('Content-Type: text/html; charset=utf-8');
        preg_match("#>[.\n]*?(第.*?章.*?)<#", $str, $title);
        echo '<h1 style="text-align: center">' . $title[1] . '</h1>';
        $str = preg_replace("#<.*?>#", '', $str);
        $str = $this->trimStr($str);
        $str = $this->addP($str);
        echo $str;
    }

    private function trimStr($str)
    {
        if (preg_match("#.*?[\s\n]#", $str)) {
            preg_match("#.*?[\s\n]#", $str, $data);
            if (strlen($data[0]) < 300) {
                $str = preg_replace("#.*?[\s\n]#", '', $str, 1);
                return $this->trimStr($str);
            } else {
                $this->content .= $data[0];
                $str = preg_replace("#.*?[\s\n]#", '', $str, 1);
                if (preg_match("#.*?[\s\n]#", $str)) {
                    return $this->trimStr($str);
                }
                return $this->content;
            }
        }
        return $this->content;
    }

    private function addP($str, $content = '')
    {
        if (preg_match("#((&nbsp;){4}.*?)&nbsp#", $str)) {
            preg_match("#((&nbsp;){4}.*?)&nbsp#", $str, $data);
            $content .= '<p>' . $data[1] . '</p>';
            $str = preg_replace("#$data[1]#", '', $str);
            return $this->addP($str, $content);
        } else {
            return $content;
        }
    }

    /**
     * 获取控制电器的参数值
     * @return mixed
     */
    function getControlData()
    {
        return null;
    }
}

