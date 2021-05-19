<?php
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();
page_header("Прогнозы");

if ($user->data['user_id'] == ANONYMOUS)
{
    login_box('', $user->lang['LOGIN']);
}

$sql = "SELECT * FROM " . USERS_TABLE;
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);
$tournmanent_id = $request->variable("id",0);


$sql = "SELECT u.username, SUM(
                              IF(up.first_player_score = g.first_player_score AND up.second_player_score=g.second_player_score,3,0)+
                              IF(g.first_player_score > g.second_player_score AND up.first_player_score = g.first_player_score OR 
                                 g.first_player_score < g.second_player_score AND up.second_player_score=g.second_player_score,1,0)) as point_res,
        ( 
                SELECT SUM(IF(ucq1.answer = cq1.right_answer,3,0))
                FROM `user_castom_question` ucq1
                LEFT JOIN castom_question cq1 ON cq1.id=ucq1.`castom_req_id`
                WHERE ucq1.user_id = u.user_id AND cq1.tounament_id=g.tournament_id    
         ) as points_castom_req,
         SUM(
                              IF(up.first_player_score = g.first_player_score AND up.second_player_score=g.second_player_score,3,0)+
                              IF(g.first_player_score > g.second_player_score AND up.first_player_score = g.first_player_score OR 
                                 g.first_player_score < g.second_player_score AND up.second_player_score=g.second_player_score,1,0))+
        ( 
                SELECT SUM(IF(ucq1.answer = cq1.right_answer,3,0))
                FROM `user_castom_question` ucq1
                LEFT JOIN castom_question cq1 ON cq1.id=ucq1.`castom_req_id`
                WHERE ucq1.user_id = u.user_id AND cq1.tounament_id=g.tournament_id    
         ) as res_points
        FROM user_prediction up 
        LEFT JOIN game g ON g.id=up.game_id        
        LEFT JOIN ". USERS_TABLE ." as u  ON up.user_id=u.user_id  
        WHERE g.tournament_id=$tournmanent_id
        GROUP BY u.user_id
        ORDER BY res_points DESC
        ";
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);

?>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.12"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>

<link rel="stylesheet" href="common_styles.css"/>
<script defer src="https://use.fontawesome.com/releases/v5.0.13/js/all.js"></script>
<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/fontawesome.css" integrity="sha384-eHoocPgXsiuZh+Yy6+7DsKAerLXyJmu2Hadh4QYyt+8v86geixVYwFqUvMU8X90l" crossorigin="anonymous"/>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.3"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.22"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css" rel="stylesheet">
<meta name="viewport" content="width=600">
<a class='btn btn-success-light btn-block my-md-3 my-sm-3' href="admin.php">К списку турниров</a>
<table class="table table-bordered">
    <thead>
    <tr>        
        <th>Логин</th>
        <th>Счет/победитель</th>
        <th>Доп.вопросы</th>
        <th>Суммарно</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $row):?>
        <tr>
            <td><?=$row['username']?></td>
            <td><?=$row["point_res"]?></td>
            <td><?=$row["points_castom_req"]?></td>
            <td><?=$row["res_points"]?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>