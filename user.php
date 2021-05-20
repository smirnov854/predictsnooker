<?php
date_default_timezone_set("Etc/UTC");
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();

if ($user->data['user_id'] == ANONYMOUS)
{
    login_box('', $user->lang['LOGIN']);
}

$sql = "SELECT * FROM " . USERS_TABLE;
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);
$user_id = $user->data['user_id'];
$sql = "DROP TABLE IF EXISTS tmp_table";
$db->sql_query($sql);
$sql = "CREATE TEMPORARY TABLE tmp_table
        SELECT t.id,
               SUM((IF(up.first_player_score = g.first_player_score AND up.second_player_score=g.second_player_score,3,0) + 
               IF(g.first_player_score > g.second_player_score AND up.first_player_score = g.first_player_score OR 
                                 g.first_player_score < g.second_player_score AND up.second_player_score=g.second_player_score,1,0))) as point_res,
                ( 
                SELECT SUM(IF(ucq1.answer = cq1.right_answer,3,0))
                FROM `user_castom_question` ucq1
                LEFT JOIN castom_question cq1 ON cq1.id=ucq1.`castom_req_id`
                WHERE ucq1.user_id = $user_id AND cq1.tounament_id=t.id    
         ) as points_castom_req
        FROM tournament t
        LEFT JOIN game g ON g.tournament_id=t.id    
        LEFT JOIN user_prediction up ON up.game_id = g.id
        WHERE up.user_id=$user_id     
        GROUP BY t.id            
";
$db->sql_query($sql);
$sql = "SELECT t.id,
               t.date_start,   
               t.name, 
               t.pairs_amount,
               COUNT(g.id) as cnt,
               point_res,
               points_castom_req,
               (
                  SELECT right_answer
                  FROM castom_question 
                  WHERE tounament_id=t.id AND right_answer IS NOT NULL 
                  LIMIT 1
               ) as can_do_predict               
        FROM tournament t
        LEFT JOIN levels lvl ON lvl.tournament_id=t.id
        LEFT JOIN game g ON g.level_id=lvl.id 
        LEFT JOIN tmp_table tmp ON tmp.id=t.id
        GROUP BY t.id
        ORDER BY t.date_start DESC";
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);


?>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12"></script>
    <link rel="stylesheet" href="style_for_table.css">
    <link rel="stylesheet" href="common_styles.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/css/bootstrap-datepicker.min.css" rel="stylesheet"/>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
    <meta name="viewport" content="width=600">
</head>
<body>


<div id="vue_app">
    <img src="logo.png" class="float-left mx-auto">
    <table class="table table-bordered">
        <thead>
        <tr>            
            <th>Название</th>
            <th>Статус</th>
            <th>Баллы</th>
            <!--<th>Пары</th>
            <th>Игры</th>-->
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $row):?>
            <tr>
                
                <td><?=$row["name"]?></td>
                <td><?= (!empty($row['can_do_predict'])  ? "Турнир завершен" : "Прием прогнозов")?></td>
                <td title="за доп вопросы <?=$row['points_castom_req']?>, за угаданный счет/победителей <?=$row['point_res']?>"><?=$row['points_castom_req'] + $row['point_res']?></td>
                <!--<td><?=$row["pairs_amount"]?></td>
                <td><?=$row["cnt"]?></td>-->
                <td title="Просмотреть турнир">
                    <a class="btn btn-success-light" href="do_prediction.php?id=<?=$row['id']?>">
                        <?= (empty($row['can_do_predict'])) ? "Сделать прогноз" : "Посмотреть результат"?>
                    </a>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
    

    

</div>

<script src="https://cdn.jsdelivr.net/npm/v-mask/dist/v-mask.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

<!--
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js" integrity="sha512-d4KkQohk+HswGs6A1d6Gak6Bb9rMWtxjOa0IiY49Q3TeFd5xAzjWXDCBW9RS7m86FQ4RzM2BdHmdJnnKRYknxw==" crossorigin="anonymous"></script>
-->
<script></script>
</body>
</html>
