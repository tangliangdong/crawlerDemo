<?php
require __DIR__ . '/getSchedule.php';
require __DIR__ . '/service/UserService.php';
?>
<!doctype html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>课表</title>
    <link rel="stylesheet" href="static/css/schedule.css" />
</head>
<body>
<?php
$userService = new UserService();
$unit = new Unit;
$username = $_GET['username'];
$result = $userService->getInfoByUsername($username);
$user = $result->fetch_row();
$studentId = $user[0];
$passwd = $user[1];
$content = $unit->getSchedule($studentId,$passwd);
?>
<table class="schedule_table" cellspacing="0" cellpadding="0">
<?php
for ($i = 0; $i < 14; $i++) {
    echo $content[0][$i];
}
?>
</table>
<script src="static/js/jquery.min.js"></script>
<script>
var num = 1;
$(function(){
    $('.schedule_table tr:eq(1)').remove();
    $('.schedule_table td:contains("周")').each(function (index) {
        $(this).addClass('has-lesson');
    })
    $('.schedule_table tr').each(function (index) {
        var $this = $(this);

        $this.children('td').each(function (index2) {
            if(index===0){
                $(this).css('text-align','center');
            }
            if(index>0&&index2===0){
                $(this).text(num);
                $(this).css({'text-align':'center','font-size':'16px','font-weight':'bold'});
                num++;
            }
        })
    });
    $('.schedule_table td').each(function (index) {
        $(this).removeAttr("width");
        var num = 1;
//        var content = $(this).text();
//        $(this).text('');
//        $(this).append('<div>'+content+'</div>');
    });
    $('.schedule_table tr:first').children('td').each(function (index) {
        var word = $(this).text();
        word = word.replace(/星期/, "");
        if(index===0){
            $(this).attr('width','5%');
        }else{
            $(this).attr('width','12%');
        }
        $(this).text(word);
        $(this).addClass('table-title');
    });
});
</script>
</body>
</html>