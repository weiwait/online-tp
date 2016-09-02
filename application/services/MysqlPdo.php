<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-02
 * Time: 17:44
 */
namespace services;

class MysqlPdo
{
    public function getPdo($dbname)
    {
        $dbname = 'test_tp_' . $dbname;
        $conn = "mysql:host=rdsiw7z9hqi3rzubgw1vio.mysql.rds.aliyuncs.com;dbname={$dbname};charset=utf8";
        $username = 'test1';
        $password = '123test';
        return new \PDO($conn, $username, $password);
    }
}
