<?php
//require_once './db.php';
require __DIR__ . "/getSchedule.php";
require __DIR__ . "/service/CertificateService.php";
//require './service/UserService.php';
require __DIR__ . '/service/FileService.php';

define('TOKEN', 'tangliangdong');//定义我们的token

$wechatObj = new wechatCallbackapiTest();
//调用函数
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
} else {
    $wechatObj->responseMsg();
}

class wechatCallbackapiTest{

    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        $postStr = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
//        $DB = new Db();
//        $conn = $DB->getConnection();
//        $servername = $DB->servername;
//        $username = $DB->username;
//        $password = $DB->password;
//        $dbname = $DB->dbname;
//        $conn = new mysqli($servername, $username, $password, $dbname);
//        mysql_query('SET NAME UTF8');
        $userService = new UserService();
        $fileService = new FileService();
        $certificateService = new CertificateService();

        if (!empty($postStr)) {
//            $this->logger('R ',$postStr);
            libxml_disable_entity_loader(true);//防止文件泄漏 安全防御
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $msgType = $postObj->MsgType;
            $media_id = $postObj->MediaId;
            $msgId = $postObj->MsgId;
            $keyword = trim($postObj->Content);
            $time = time();

            // echo print_r($json);
            // echo $json[0]->{'pm2_5'};
            if ($msgType == 'text') {
                $arr = explode(" ", $keyword);
                switch ($arr[0]) {
                    case 'pm':
                        $data = file_get_contents("http://www.pm25.in/api/querys/pm2_5.json?city=" . $arr[1] . "&token=5j1znBVAsnSf5xQyNQyq");
                        $json = @json_decode($data);
                        if (isset($json)){
                            $word = '这个小时内的API请求次数用完了，休息一下吧！';
                        }
                        $pm25 = $json[0]->pm2_5;
                        $word = $arr[1] . 'pm2.5：' . $pm25;
                        break;
                    case '天气';
                        if(count($arr)===2){
                            $data = file_get_contents("http://v.juhe.cn/weather/index?cityname=" . $arr[1] . "&dtype=json&format=1&key=f71ec98d95b28e1a4d6befb7be70bdf1");
                            $json = json_decode($data);
                            $weather = $json->result->today->weather;
                            $temperature = $json->result->today->temperature;
                            $wind = $json->result->today->wind;
                            $word = $arr[1] . '天气：' . $weather . ' 温度：' . $temperature . '风速：' . $wind;
                        }else{
                            $word = '请输入：天气 城市';
                        }
                        break;
                    case '查看照片':
                        $word = $fileService->check_photo($fromUsername);
//                        $word = $this->check_photo($fromUsername, $conn);
                        break;
                    case '绑定':
                        if (count($arr) === 3) {
                            $studentId = $arr[1];
                            $passwd = $arr[2];
                            $result = $userService->getInfoByUsername($studentId,$passwd);
//                            $sql = "select * from user where username='" . $fromUsername . "'";
//                            $result = $conn->query($sql);
                            $num_cnt = $result->num_rows;
                            if ($num_cnt > 0) {
                                $word = '此微信已绑定学生账号，若要修改，则输入：修改账号 学号 密码';
                            } else {
                                if ($data = $userService->save($fromUsername,$studentId,$passwd)>0) {
                                    $word = '绑定成功';
                                } else {
                                    $word = '绑定失败';
                                }
                            }
                        } else {
                            $word = '请输入： 绑定 学号 密码';
                        }
                        break;
                    case '修改':
                        if (count($arr) === 3) {
                            $studentId = $arr[1];
                            $passwd = $arr[2];
                            if (!preg_match("/^[0-9]{8}$/",$studentId)) {
                                $word = "账号只允许8位数字！";
                                break;
                            }
                            if(!preg_match("/^[0-9[a-zA-Z]*$/",$passwd)){
                                $word = "密码只允许字母和数字";
                                break;
                            }
                            $result = $userService->getInfoByUsername($fromUsername);
//                            $sql = "select * from user where username='" . $fromUsername . "'";
//                            $result = $conn->query($sql);
                            $num_cnt = $result->num_rows;
                            if ($num_cnt > 0) {
//                                $sql = "update user set studentId='" . $studentId . "',password='" . $passwd . "' where username = '" . $fromUsername . "'";
                                if ($userService->update($fromUsername,$studentId,$passwd)>0) {
                                    $word = '修改成功';
                                } else {
                                    $word = '输入的账号和密码和原来相同';
                                }
                            } else {
                                $word = '此微信未绑定学生账号，若要修改，则输入：修改 学号 密码';
                            }
                        } else {
                            $word = '请输入： 绑定 学号 密码';
                        }
                        break;
                    case '查看学号':
//                        $sql = "select studentId,password from user where username='" . $fromUsername . "' limit 1";
//                        $result = $conn->query($sql);
//                        $row = $result->fetch_row();
                        $result = $userService->getInfoByUsername($fromUsername);
                        $row = $result->fetch_row();
                        if(count($row>0)){
                            $word = '学号【' . $row[0] . '】 密码【' . $row[1] . "】\r\n若要修改信息，则输入：修改账号 学号 密码";
                        }else{
                            $word = '未绑定学号';
                        }
                        break;
                    case '课表':
                        $unit = new Unit;
                        $result = $userService->getInfoByUsername($fromUsername);
                        $num_cnt = $result->num_rows;
                        if ($num_cnt>0){
                            $row = $result->fetch_row();
                            $studentId = $row[0];
                            $passwd = $row[1];
                            $flag = $unit->checkRight($studentId,$passwd);
                            if($flag===0){
                                $word = '账号和密码错误，请修改账号或密码';
                            }else if($flag===-1){
                                $word = '用户名不存在或未按照要求参加教学活动！';
                            }else{
                                $word = '<a href="http://zzz.tangliangdong.me/weixin/scheduleView.php?username='.$fromUsername.'">查看一周课表</a>';
                            }
                        }else{
                            $word = '还未绑定学生账号，输入：绑定 学号 密码，进行绑定';
                        }
                        break;
                    case '保存准考证':
                        if (count($arr) === 2) {
                            $certificate = $arr[1];
                            $certificateService = new CertificateService();
                            if($certificateService->save($fromUsername,$certificate)){
                                $word = '保存成功';
                            }else{
                                $word = '保存失败';
                            }
                        }else{
                            $word = '请输入： 保存准考证 准考证号码';
                        }
                        break;
                    case '准考证':
                        $word = $this->check_certificate($fromUsername);
                        break;
                    case '成绩':
                        $word = $this->check_grade($fromUsername);
                        break;
                    case '新歌榜':
                        $word = $this->get_new_music_list();
                        break;
                    case '图文':
                        $record = array(
                            'title' => '你好世界',
                            'decription' => '杭州真美丽',
                            'picUrl' => 'http://img.youai123.com/1507602781-1099.jpg',
                            'url' => 'https://mp.weixin.qq.com/s?__biz=MzI2MTI5MDI5Ng==&tempkey=OTMyX09FVTIxTjQzQVR1aEZNTk1US184aUxnMzdsdTE3LVZvaGI2THE3cVpIR0k5WWRCY2IzYlVZTUlPM3E1a2JrdDhZanVEQ0VvaXk0N1hxc05KcmtEUDF3X2RDanFtc09ZVjkzYnJfT1hTRGpOc2ItM3N2SHRGc0tneXBKblZ2YXEtaFJLMzI0bkc5WW1VOEJDTnk3S3E2TWg0eHQwejRPVjBCdVpUOVF%2Bfg%3D%3D&chksm=6a5de15f5d2a6849d4d88885bb0fc55f2d49a6d27be13962cb2c97a963e33ebb1d0c2e2dc398#rd',
                        );
                        $resultStr = $this->handle_news($postObj,$record);
                        echo $resultStr;
                        return;
                    case 'token':
                        $access_token = $this->get_access_token();
                        $word = $access_token->access_token;
                        break;
                    default: // 输入错误的时候返回
                        $word = "查询pm2.5请输入： pm 杭州 \n查询天气请输入： 天气 杭州 \n查看已上传照片请输入： 查看照片 \n\n要查询课表 请先绑定账号，\n输入：绑定 学号 密码 【提示：密码是 Hdu+身份证后六位】\n再输入 课表\n\n查询成绩输入：成绩";
                }
                $itemTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    </xml>";
                $result = sprintf($itemTpl, $fromUsername, $toUsername, $time, $word);
                echo $result;
            }
            if ($msgType == 'event') {
                $event = $postObj->Event;
                switch ($event) {
                    case 'CLICK':
                        $eventKey = $postObj->EventKey;
                        switch ($eventKey) {
                            case 'query_picture':
                                $word = $fileService->check_photo($fromUsername);
                                break;
                            case 'schedule_btn':
                                $unit = new Unit;
                                $result = $userService->getInfoByUsername($fromUsername);
                                $num_cnt = $result->num_rows;
                                if ($num_cnt>0){
                                    $row = $result->fetch_row();
                                    $studentId = $row[0];
                                    $passwd = $row[1];
                                    $flag = $unit->checkRight($studentId,$passwd);
                                    if($flag===0){
                                        $word = '账号和密码错误，请修改账号或密码';
                                    }else if($flag===-1){
                                        $word = '用户名不存在或未按照要求参加教学活动！';
                                    }else{
                                        $word = '<a href="http://zzz.tangliangdong.me/weixin/scheduleView.php?username='.$fromUsername.'">查看一周课表</a>';
                                    }
                                }else{
                                    $word = '还未绑定学生账号，输入：绑定 学号 密码，进行绑定';
                                }
                                break;
                            case 'check_certificate':
                                $word = $this->check_certificate($fromUsername);
                                break;
                            case 'check_grade':
                                $word = $this->check_grade($fromUsername);
                                break;
                        }
                        break;
                    case 'subscribe': // 点击关注
                        $word = "谢谢关注【小唐棒棒糖】\n\n1.查询pm2.5请输入： pm 杭州 \n2.查询天气请输入： 天气 杭州 \n3.查看已上传照片请输入： 查看照片 \n\n要查询课表 请先绑定账号，\n输入：绑定 学号 密码【提示：密码是 Hdu+身份证后六位】\n再输入 课表\n 输入：查看学号 【可查看绑定的学号和密码】\n\n查询成绩输入：成绩";
                        break;
                }
                $itemTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";

                // $word = "<a href='https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=".$access_token."'</a>";
                $result = sprintf($itemTpl, $fromUsername, $toUsername, $time, $word);
                echo $result;

                $this->menu($access_token->access_token);
            }
            if ($msgType == 'image') {
                // $itemTpl = "<xml>
                // <ToUserName><![CDATA[%s]]></ToUserName>
                // <FromUserName><![CDATA[%s]]></FromUserName>
                // <CreateTime>%s</CreateTime>
                // <MsgType><![CDATA[image]]></MsgType>
                // <MediaId><![CDATA[%s]]></MediaId>
                // </xml>";
                // $picUrl = 'https://mp.weixin.qq.com/cgi-bin/filepage?type=2&begin=0&count=12&group_id=1&t=media/img_list&token=581922146&lang=zh_CN';
                // $material = file_get_contents('https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$access_token->access_token);
                // $url    = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$access_token->access_token;
                // $data   = '{"type":"image", "offset":0, "count":10}';
                // $res    = $wechatObj->curlPost($url, $data);
                // echo $res;
                // echo print_r(json_decode($material));
                // $result = sprintf($itemTpl, $fromUsername, $toUsername, $time,$media_id);
                $post = array(
                    "type"=> "image",
                    "offset"=> 0,
                    "count"=> 20
                );
                $access_token = $this->get_access_token();
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$access_token->access_token);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json; charset=utf-8')
                );
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $data = curl_exec($ch);
                curl_close($ch);
                print_r($data);

                $picUrl = $postObj->PicUrl;

                // 保存图片
                // $image_arr = explode('/',$picUrl);
                // $image_original_name = $image_arr[count($image_arr)-1];
                $image_file_name = $fromUsername . $time . '.jpg';
                $image_file = file_get_contents($picUrl);
                file_put_contents("./upload/image/$image_file_name", $image_file);

                // 保存图片记录
                $file_path = '/upload/image/' . $image_file_name;
//                $sql = "insert into file(username,file_path,file_name,add_time) values('" . $fromUsername . "','" . $file_path . "','" . $image_file_name . "','" . $time . "')";
                $fileService->save($fromUsername,$file_path,$image_file_name,$time);

                $itemTpl = "<xml>
          <ToUserName><![CDATA[%s]]></ToUserName>
          <FromUserName><![CDATA[%s]]></FromUserName>
          <CreateTime>%s</CreateTime>
          <MsgType><![CDATA[text]]></MsgType>
          <Content><![CDATA[%s]]></Content>
          </xml>";
                $word = '<a href="http://zzz.tangliangdong.me/weixin/image.php?event=look_my_image&fromUsername=' . $fromUsername . '">输入 查看照片</a>';
                $result = sprintf($itemTpl, $fromUsername, $toUsername, $time, $word);
                echo $result;
            } else {
                echo "Input something...";
            }
        } else {
            echo "";
            exit;
        }
    }

    private function check_grade($fromUsername){
        $userService = new UserService();
        $unit = new Unit;
        $result = $userService->getInfoByUsername($fromUsername);
        $num_cnt = $result->num_rows;
        if ($num_cnt>0){
            $row = $result->fetch_row();
            $studentId = $row[0];
            $passwd = $row[1];
            $flag = $unit->checkRight($studentId,$passwd);
            if($flag===0){
                $word = '账号和密码错误，请修改账号或密码';
            }else if($flag===-1){
                $word = '用户名不存在或未按照要求参加教学活动！';
            }else{
                $word = '<a href="http://zzz.tangliangdong.me/weixin/gradeView.php?username='.$fromUsername.'">查看成绩</a>';
            }
        }else{
            $word = '还未绑定学生账号，输入：绑定 学号 密码，进行绑定';
        }
        return $word;
    }

    private function check_certificate($fromUsername){
        $certificateService = new CertificateService();
        $result = $certificateService->getInfoByUsername($fromUsername);
        if (isset($result)){
            $row = $result->fetch_row();
            $word = '准考证号是： '.$row[0];
        }else{
            $word = '还没保存准考证号，请输入：保存准考证 准考证号码';
        }
        return $word;
    }

    private function checkSignature()
    {
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    function get_access_token(){
        $access_token_json = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx205766a318ce9842&secret=4852c307655724f2c2752e616aa20b58');
        $access_token = json_decode($access_token_json);
        return $access_token;
    }

    function menu($access_token)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=" . $access_token;
        $jsonmenu = '{
        "button": [
            {
                "name": "博主",
                "sub_button": [
                    {
                      "type": "view",
                      "name": "主页",
                      "url": "http://www.tangliangdong.me/",
                    },
                    {
                      "type": "view",
                      "name": "博客",
                      "url": "http://zhizhi.tangliangdong.me/",
                    }
                ]
            },
            {
                "name": "功能",
                "sub_button": [
                    {
                        "type": "click",
                        "name": "查询课表",
                        "key": "schedule_btn",
                        "sub_button": [ ]
                    },
                    {
                        "type": "click",
                        "name": "查看准考证",
                        "key": "check_certificate",
                        "sub_button": [ ]
                    },
                    {
                        "type": "click",
                        "name": "查看成绩",
                        "key": "check_grade",
                        "sub_button": [ ]
                    },
                    {
                        "type": "click",
                        "name": "查询照片",
                        "key": "query_picture",
                        "sub_button": [ ]
                    },
                    {
                        "type": "pic_photo_or_album", {
        "button": [
            {
                "name": "博主",
                "sub_button": [
                    {
                      "type": "view",
                      "name": "主页",
                      "url": "http://www.tangliangdong.me/",
                    },
                    {
                      "type": "view",
                      "name": "博客",
                      "url": "http://zhizhi.tangliangdong.me/",
                    }
                ]
            },
            {
                "name": "功能",
                "sub_button": [
                    {
                        "type": "click",
                        "name": "查询课表",
                        "key": "schedule_btn",
                        "sub_button": [ ]
                    },
                    {
                        "type": "click",
                        "name": "查看准考证",
                        "key": "check_certificate",
                        "sub_button": [ ]
                    },
                    {
                        "type": "click",
                        "name": "查看成绩",
                        "key": "check_grade",
                        "sub_button": [ ]
                    },
                    {
                        "type": "click",
                        "name": "查询照片",
                        "key": "query_picture",
                        "sub_button": [ ]
                    },
                    {
                        "type": "pic_photo_or_album",
                        "name": "拍照或者相册发图",
                        "key": "rselfmenu_1_1",
                        "sub_button": [ ]
                    },

                ]
            }
        ]
      }
                        "name": "拍照或者相册发图",
                        "key": "rselfmenu_1_1",
                        "sub_button": [ ]
                    },

                ]
            }
        ]
      }';
        $result = https_request($url, $jsonmenu);
//        var_dump($result);
    }

    function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    function get_new_music_list(){
        $url = 'https://music.163.com/';
        $unit = new Unit();
//        $result = $unit->curl_request($url,'','',1);
//        $cookie = $result['cookie'];

        $url = 'https://music.163.com/discover/toplist?id=3779629';

        $result = $unit->curl_request($url,'','','');
        print_r($result);

        $pattern = '@<textarea style="display:none;">.*?</textarea>@is';
        preg_match_all($pattern, $result, $matches);
//        print_r($matches);
        $jsonData = substr($matches[0][0],33,strlen($matches[0][0])-12);
        print_r($jsonData);
        $data = json_decode($jsonData);
        print_r($data);
    }


    public function handle_news($object,$newCnotent){
        $newsTplHead = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>1</ArticleCount>
            <Articles>";
        $newsTplBody = "<item>
            <Title><![CDATA[%s]]></Title> 
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
            </item>";
        $newsTplFoot = "</Articles>
            </xml>";
        $header = sprintf($newsTplHead,$object->FromUserName,$object->ToUserName,time());
        $title = $newCnotent['title'];
        $decription = $newCnotent['decription'];
        $picUrl = $newCnotent['picUrl'];
        $url = $newCnotent['url'];

        $body = sprintf($newsTplBody,$title,$decription,$picUrl,$url);

        return $header.$body.$newsTplFoot;
    }

//    public function logger($log_content){
//        if($_SERVER['REMOTE_ADDR']!= '127.0.0.1'){
//            $max_size = 10000;
//            $log_filename = 'log.xml';
//            if(file_exists($log_filename) and (abs(filesize($log_filename))>$max_size)){
//                unlink($log_filename);
//            }
//            file_put_contents($log_filename,date('H:i:s').' '.$log_content."\r\n",FILE_APPEND);
//
//        }
//    }

}

?>
