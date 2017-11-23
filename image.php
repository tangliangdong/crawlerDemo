<?php
require_once './db.php';
$DoEvent = $_GET['event'];
$DB = new Db();
$servername = $DB->servername;
$username = $DB->username;
$password = $DB->password;
$dbname = $DB->dbname;
$conn = new mysqli($servername, $username, $password, $dbname);
//mysql_query('SET NAME UTF8');

switch($DoEvent){
  case 'look_my_image':
    $fromUsername = $_GET['fromUsername'];
    $sql = "select * from file where username='".$fromUsername."'";
    $result = $conn->query($sql);
    $word = "<h3>已上传的图片：</h3>";
    if ($result->num_rows > 0) {
      // 输出数据
      while($row = $result->fetch_assoc()) {
        $date = date("Y-m-d H:i",$row['add_time']);
        $word = $word .'<img src="http://zzz.tangliangdong.me/weixin/'.$row['file_path'].'"/><br/>';
      }
    }else{
      $word = '未发送过照片';
    }
    break;
}

?>

<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
  <style>
    img{
      width: 400px;
    }
  </style>
</head>
<body>
  <?php echo $word ?>
</body>
</html>