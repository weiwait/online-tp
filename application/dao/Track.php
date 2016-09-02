<?php
namespace dao;
use base\Dao;

class Track extends Dao
{
    public function __construct() {
        // 指定mysql表名字
        $this->table_name  = 'track';
        $this->server_name = 'track';
        parent::__construct();
    }
}