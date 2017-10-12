<?php

/**
 * Created by PhpStorm.
 * User: tangliangdong
 * Date: 2017/9/28
 * Time: 13:06
 */
require __DIR__ .  '/../db.php';

class UserService{
    private $db;
    private $conn;

    function __construct(){
        if (!isset($this->db) && !is_a($this->db, 'Db')){
            $this->db = new Db();
        }
        $this->conn = $this->db->getConnection();

//        mysql_query('SET NAME UTF8');
    }

    public function save($fromUsername,$studentId,$passwd){
        $sql = "INSERT INTO user (username,studentId,password) VALUES ('" . $fromUsername . "','" . $studentId . "', '" . $passwd . "')";
        $this->conn->query($sql);
        return $this->conn->affected_rows;
    }

    public function update($fromUsername,$studentId,$passwd){
        $sql = "update user set studentId='" . $studentId . "',password='" . $passwd . "' where username = '" . $fromUsername . "'";
        $this->conn->query($sql);
        return $this->conn->affected_rows;
    }

    public function getInfoByUsername($username){
        $sql = 'select studentId,password,id from user where username="'.$username.'"';
//        $result =  $this->conn->query($sql);
//        return $result->fetch_row();
        return $this->conn->query($sql);
    }
}