<?php

/**
 * Created by PhpStorm.
 * User: tangliangdong
 * Date: 2017/9/28
 * Time: 16:59
 */

class FileService{
    private $db;
    private $conn;
    private $userService;

    function __construct(){
        if (!isset($this->db) && !is_a($this->db, 'Db')){
            $this->db = new Db();
        }
        $this->conn = $this->db->getConnection();
//        mysql_query('SET NAME UTF8');
    }

    function save($fromUsername,$image_file_name,$file_path,$time){
        $sql = "insert into file(username,file_path,file_name,add_time) values('" . $fromUsername . "','" . $file_path . "','" . $image_file_name . "','" . $time . "')";
        $result = $this->conn->query($sql);
        return $this->conn->affected_rows;
    }


    function check_photo($fromUsername)
    {
        $sql = "select * from file where username='" . $fromUsername . "'";
        $result = $this->conn->query($sql);
        $word = "已上传的图片：\n";
        if ($result->num_rows > 0) {
            // 输出数据
            while ($row = $result->fetch_assoc()) {
                $date = date("Y-m-d H:i", $row['add_time']);
                $word = $word . '<a href="http://zzz.tangliangdong.me/weixin/' . $row['file_path'] . '">' . $date . '</a>' . "\n";
            }
            $word = $word . "\n" . '<a href="http://zzz.tangliangdong.me/weixin/image.php?event=look_my_image&fromUsername=' . $fromUsername . '">浏览所有照片</a>';
        } else {
            $word = '未发送过照片';
        }
        return $word;
    }
}