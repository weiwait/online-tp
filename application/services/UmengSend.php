<?php

namespace services;

use base\Service;
use base\ServiceFactory;

class UmengSend extends Service
{
    public function send($appid, $title, $content, $sound = true, $shock = true)
    {
        echo "into send\n";
        $phoneType = ServiceFactory::getService("App")->getPhoneType($appid);

        $appid = strtolower($appid);
        $appid = str_replace("-", "", $appid);

        switch ($phoneType) {
            case 1:
                $Umeng = new \Umeng("54d97a8bfd98c52b06000086", "qp4vy8yiygqjd5q6xu6fb0yjjpuchdqw");
                //android
                $ret = $Umeng->sendAndroidCustomizedcast($appid, $title, $content, $sound, $shock);
                break;
            case 2:
                $Umeng = new \Umeng("57c3fc82e0f55a60930001ab", "vibln1ndpibkxa0mpor4s2datlkbgtm4");
                //ios
                $ret = $Umeng->sendIOSCustomizedcast($appid, $title, $content, $sound, $shock);
                break;
            default:
                echo "bad phoneType[" . $phoneType . "]\n";
                $ret = false;
                break;
        }
        return $ret;
    }

}
