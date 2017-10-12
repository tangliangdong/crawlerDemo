<?php

/**
 * Class Db
 * 数据库连接类
 */
class Db{

    public $servername = "127.0.0.1";
    public $username = "root";
    public $password = "root";
    public $dbname = "weixin";

    public function getConnection(){
        $servername = $this->servername;
        $username = $this->username;
        $password = $this->password;
        $dbname = $this->dbname;
        $conn = new mysqli($servername, $username, $password, $dbname);
        return $conn;
    }
}