<!doctype html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>成绩</title>
    <?php
    require __DIR__ . '/service/GradeService.php';
    ?>
    <link rel="stylesheet" href="static/css/grade.css">
</head>
<body>
<?php
$gradeService = new GradeService();
$fromUsername = $_GET['username'];
$result = $gradeService->getGrade($fromUsername);
$sum1 = 0.0;
$sum2 = 0.0;
$sum1_credits = 0;
$sum2_credits = 0;
for ($i = 1;$i < count($result); $i++){
    $grade = preg_replace('@<td.*?>|</td>@', '', $result[$i][7]);
    $credits = preg_replace('@<td.*?>|</td>@', '', $result[$i][6]);
    $sum1_credits += $credits;
    $point = 0;
    if(is_numeric($grade)){
        if($grade>=95){
            $point = 5;
        }else{
            $point = 5-(95-$grade)*0.1;
        }
    }else{
        switch ($grade){
            case '优秀':
                $point = 5;
                break;
            case '良好':
                $point = 4;
                break;
            case '中等':
                $point = 3;
                break;
            case '及格':
                $point = 2;
                break;
            case '不及格':
                $point = 1;
                break;
        }
    }
    $classType = preg_replace('@<td.*?>|</td>@', '', $result[$i][4]);
    if($classType==='必修'||$classType==='实践'||$classType==='专业选修'){
        $sum2 += $credits*$point;
        $sum2_credits += $credits;
    }
    $result[$i][13] = $point;
    $sum1 += $credits*$point;
}
$GPA1 = number_format($sum1/$sum1_credits,3);
$GPA2 = number_format($sum2/$sum2_credits,3);
?>

<div class="header">
    <h3>GPA1：<span><?php echo $GPA1?>&nbsp;&nbsp;&nbsp;&nbsp;</span>GPA2：<span><?php echo $GPA2?></span></h3>
</div>

<table class="grade_table" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th style="width: 40%;">课程</th>
            <th>类型</th>
            <th>学分</th>
            <th>成绩</th>
            <th>单科绩点</th>
        </tr>
    </thead>
    <tbody>
    <?php
        for ($i = 1;$i < count($result); $i++){
    ?>
        <tr>
        <?php
        echo $result[$i][3] . $result[$i][4] . $result[$i][6] . $result[$i][7] .'<td>'.$result[$i][13].'</td>';
        ?>
        </tr>
    <?php
        }
    ?>
    </tbody>

</table>

</body>
</html>



