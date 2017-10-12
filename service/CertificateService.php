<?php

/**
 * Created by PhpStorm.
 * User: tangliangdong
 * Date: 2017/9/28
 * Time: 16:38
 */
require __DIR__ .  '/UserService.php';

class CertificateService{

    private $db;
    private $conn;
    private $userService;

    function __construct(){
        $this->db = new Db();
        if (!isset($this->db) && !is_a($this->db, 'Db')){
            $this->db = new Db();
        }
        $this->userService = new UserService();
        $this->conn = $this->db->getConnection();
//        mysql_query('SET NAME UTF8');
    }

    public function save($username,$certificate){
        $result = $this->userService->getInfoByUsername($username);
        $user = $result->fetch_row();
        $id = $user[2];
        $sql = "insert into certificate(user_id,certificate) values('".$id."','".$certificate."')";
        if($result = $this->conn->query($sql)){
            return true;
        }else{
            return false;
        }
    }
    public function getInfoByUsername($fromUsername){
        $sql = 'select certificate from certificate join user on user.id = certificate.user_id where user.username="'.$fromUsername.'"';
        $result = $this->conn->query($sql);
        return $result;
    }
}