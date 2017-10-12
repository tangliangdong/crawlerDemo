<?php

/**
 * Created by PhpStorm.
 * User: tangliangdong
 * Date: 2017/9/28
 * Time: 22:52
 */
require __DIR__ . '/../getSchedule.php';
require __DIR__ . '/UserService.php';

class GradeService{

//    public $unit;
//
//    function __construct(){
//        $unit = new Unit();
//    }

    /**
     * 获取成绩列
     */
    public function getGrade($fromUsername){
        $unit = new Unit();
        $userService = new UserService();

        $result = $unit->getView();

        $url = 'http://jxgl.hziee.edu.cn/default2.aspx';
        if(!isset($fromUsername)){
            return 'hello';
        }
        $res = $userService->getInfoByUsername($fromUsername);
        $data = $res->fetch_row();
        $studentId = $data[0];
        $passwd = $data[1];

        $post = array(
            'TextBox1'=> $studentId,
            'TextBox2'=> $passwd,
            '__VIEWSTATE'=>$result[0],
            '__EVENTVALIDATION'=>$result[1],
            'Button1'=>iconv('utf-8', 'gb2312', '登录'),
            'RadioButtonList1'=>iconv('utf-8', 'gb2312', '学生'),
        );


        $result = $unit->curl_request($url,$post,'', 1);
        $cookie = $result['cookie'];

        // 获取提交的表单数据
        $input = $this->getInput($studentId,$cookie);
        $url = 'http://jxgl.hziee.edu.cn/xscjcx_dq.aspx?xh='.$studentId.'&xm=%u5510%u826f%u680b&gnmkdm=N121605';
        $post = array(
            '__EVENTTARGET'=> $input[0],
            '__EVENTARGUMENT'=> $input[1],
            '__LASTFOCUS'=>'',
            '__EVENTVALIDATION'=> $input[3],
            '__VIEWSTATE'=>$input[2],
            'ddlxn'=>'2016-2017',
            'ddlxq'=>'2',
        );
        $result = $unit->curl_request($url,$post, $cookie);

        $result =  iconv('GBK', 'UTF-8', $result);

        // 数据筛选处理

        $data = $this->filter_data($result);


        return $data;
    }

    public function getInput($studentId,$cookie){
        $unit = new Unit();
        $res = array();
        $url = 'http://jxgl.hziee.edu.cn/xscjcx_dq.aspx?xh='.$studentId.'&xm=%CC%C6%C1%BC%B6%B0&gnmkdm=N121605';

        $result = $unit->curl_request($url,'', $cookie);
        $pattern = '@<input type="hidden" name="__EVENTTARGET" id="__EVENTTARGET" value="(.*?)" \/>@is';
        preg_match_all($pattern, $result, $matches);
        if(isset($matches[1][0])){
            $res[0] = $matches[1][0];
        }else{
            $res[0] = '';
        }

        $pattern = '@<input type="hidden" name="__EVENTARGUMENT" id="__EVENTARGUMENT" value="(.*?)" \/>@is';
        preg_match_all($pattern, $result, $matches);
        if(isset($matches[1][0])){
            $res[1] = $matches[1][0];
        }else{
            $res[1] = '';
        }

        $pattern = '@<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.*?)" \/>@is';
        preg_match_all($pattern, $result, $matches);
        if(isset($matches[1][0])){
            $res[2] = $matches[1][0];
        }else{
            $res[2] = '';
        }

        $pattern = '@<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*?)" \/>@is';
        preg_match_all($pattern, $result, $matches);
        if(isset($matches[1][0])){
            $res[3] = $matches[1][0];
        }else{
            $res[3] = '';
        }

        $post = array(
            '__EVENTTARGET'=> 'ddlxn',
            '__EVENTARGUMENT'=> '',
            '__LASTFOCUS'=>'',
            '__EVENTVALIDATION'=> $res[3],
            '__VIEWSTATE'=>$res[2],
            'ddlxn'=>'2016-2017',
            'ddlxq'=>'2',
            'btnCx'=>'',
        );

        $url = 'http://jxgl.hziee.edu.cn/xscjcx_dq.aspx?xh='.$studentId.'&xm=%u5510%u826f%u680b&gnmkdm=N121605';

        $result = $unit->curl_request($url,$post, $cookie);

        $pattern = '@<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.*?)" \/>@is';
        preg_match_all($pattern, $result, $matches);
        if(isset($matches[1][0])){
            $res[2] = $matches[1][0];
        }else{
            $res[2] = '';
        }

        $pattern = '@<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*?)" \/>@is';
        preg_match_all($pattern, $result, $matches);
        if(isset($matches[1][0])){
            $res[3] = $matches[1][0];
        }else{
            $res[3] = '';
        }

        return $res;
    }

    function filter_data($result){
        preg_match_all('@<table.*?id="DataGrid1".*?>[\s\S]*?<\/table>@', $result, $table);
        preg_match_all('@<tr.*?>[\s\S]*?<\/tr>@', $table[0][0], $str);
        foreach ($str[0] as &$value){
            $pattern = '@<td.*?>[\s\S]*?<\/td>@is';
            preg_match_all($pattern, $value, $matches);
            $value = $matches[0];
        }
        return $str[0];
//        print_r($str);
    }
}