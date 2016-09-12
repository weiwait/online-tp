<?php

use base\ServiceFactory;
use base\DaoFactory;

include "../Loader.php";

ServiceFactory::getService("Attendance")->checkLabelLeave();
