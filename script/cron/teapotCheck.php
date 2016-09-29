<?php
/**
 * Created by PhpStorm.
 * User: AfirSraftGarrier
 * Date: 2016-05-07
 * Time: 15:27
 */

use base\ServiceFactory;

include "../Loader.php";

ServiceFactory::getService('TeapotCron')->executeOrder();
