
<?php
//require __DIR__ . "/phpQuery-onefile.php";

class Unit{

    public function checkRight($studentId,$passwd){
        $result = $this->getView();

        $url = 'http://jxgl.hziee.edu.cn/default2.aspx';
        if(!isset($studentId)||!isset($passwd)||trim($studentId)===''||trim($passwd)===''){
            return false;
        }
        $post = array(
            'TextBox1'=> $studentId,
            'TextBox2'=> $passwd,
            '__VIEWSTATE'=>$result[0],
            '__EVENTVALIDATION'=>$result[1],
            'Button1'=>iconv('utf-8', 'gb2312', '登录'),
            'RadioButtonList1'=>iconv('utf-8', 'gb2312', '学生'),
        );
        $result = $this->curl_request($url,$post,'', 1);
        $cookie = $result['cookie'];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        $data = curl_exec($curl);
        $data =  iconv('GB2312', 'UTF-8', $data);
        if(strpos($data,'密码错误')){
            return 0;
        }else if(strpos($data,'用户名不存在')){
            return -1;
        }else{
            return 1;
        }
    }

    public function getSchedule($studentId,$passwd){
        $result = $this->getView();

        $url = 'http://jxgl.hziee.edu.cn/default2.aspx';
        if(!isset($studentId)||!isset($passwd)){
            return '333';
        }
        $post = array(
            'TextBox1'=> $studentId,
            'TextBox2'=> $passwd,
            '__VIEWSTATE'=>$result[0],
            '__EVENTVALIDATION'=>$result[1],
            'Button1'=>iconv('utf-8', 'gb2312', '登录'),
            'RadioButtonList1'=>iconv('utf-8', 'gb2312', '学生'),
        );
        $result = $this->curl_request($url,$post,'', 1);
        $cookie = $result['cookie'];
        $url = 'http://jxgl.hziee.edu.cn/xskbcx.aspx?xh='.$studentId.'&xm=%CC%C6%C1%BC%B6%B0&gnmkdm=N121603';
        $result = $this->curl_request($url,'', $cookie);  //我们保存的cookies
        $result =  iconv('GBK', 'UTF-8', $result);

        preg_match_all('@<tr[\s\S]*?>[\s\S]*?</tr>@', $result, $str);
        foreach ($str[0] as &$value){
            $value = str_replace(array("\r\n", "\r", "\n"), "", $value);
            $value = str_replace(array("<br>"), ";", $value);
            $value = preg_replace('@<td[\s\S]*?>[\s\S]*午<\/td>@', '', $value);
            $value = preg_replace('@<td[\s\S]*?>晚上<\/td>@', '', $value);
            $value = preg_replace('@colspan="[0-9]+"@', '', $value);
//            $value = preg_replace('@rowspan=@', 'class="has-lesson" rowspan=', $value);
//            $value = preg_replace('@<td[\s\S]*>第[\s\S]+节<\/td>@', '', $value);
//            echo $value;
        }
//        return $result;
        return $str;
    }

    // 获取表单数据
//    public function getView(){
//        $result = array();
//        $url = 'http://jxgl.hziee.edu.cn/default2.aspx';
//        phpQuery::newDocumentFile($url);
//        $result[0] = pq('input[name=__VIEWSTATE]')->val();
//        return $result;
//        $result[1] = pq('input[name=__EVENTVALIDATION]')->val();
//        return $result;
//    }

    public function getView(){
        $res = array();
        $url = 'http://jxgl.hziee.edu.cn';
        $result = $this->curl_request($url);
        $pattern = '@<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.*?)" \/>@is';
        preg_match_all($pattern, $result, $matches);
        $res[0] = $matches[1][0];

        $pattern = '@<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*?)" \/>@is';
        preg_match_all($pattern, $result, $matches);
        $res[1] = $matches[1][0];
        return $res;
    }

    function curl_request($url,$post='',$cookie='', $returnCookie=0){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        if($returnCookie){
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            print_r($data);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }else{

//            return $data;
//            $contents =
//                mb_convert_encoding($data, 'utf-8', 'GBK,UTF-8,ASCII');
            return $data;
        }
    }
}

?>
