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
/*
if (!in_array($user->data['user_id'] ,[2,61])){
    redirect("/user.php");
}
*/
$input = file_get_contents('php://input');
$input_data = json_decode($input);
//set_time_limit(1);
try {
    switch ($input_data->method) {
        case "add_tournament":
            $date_start = strtotime($input_data->date_start);
            $date_end = strtotime($input_data->date_end);
            $date_first_tour_start = strtotime($input_data->date_start_first_tour);
            $sql = "INSERT INTO tournament (`name`,
                                            `date_start`,
                                            `pairs_amount`) 
                                  VALUES('{$input_data->name}',
                                  {$date_start},
                                  {$input_data->pairs_amount})";

            $res = $db->sql_query($sql);
            $tournament_id = $db->sql_nextid();

            for ($t = 0; $t < 3; $t++) {
                $sql = "INSERT INTO castom_question (`tounament_id`,
                                            `text`
                                            ) 
                                  VALUES({$tournament_id},
                                  ''
                                  )";

                $res = $db->sql_query($sql);
            }
            $levels = 0;
            $members = $input_data->pairs_amount * 2;
            switch ($members) {
                case 8:
                    $level = 3;
                    break;
                case 16:
                    $level = 4;
                    break;
                case 32:
                    $level = 5;
                    break;
                case 64:
                    $level = 6;
                    break;
                case 128:
                    $level = 7;
                    break;
            }
            $z = 1;
            $games_id = [];
            for ($i = $level; $i > 0; $i--) {
                $sql = "INSERT INTO levels (
                                          `number`,
                                          `tournament_id`                                          
                                          ) VALUES($z,'{$tournament_id}')";
                $res = $db->sql_query($sql);
                $level_id = $db->sql_nextid();
                for ($j = 0; $j < pow(2, $i - 1); $j++) {
                    //$sql = "INSERT INTO game (`tournament_id`,`level`) VALUES('{$tournament_id}',$z)";
                    /*if($i==$level){
                        $sql = "INSERT INTO game (
                                              `tournament_id`,
                                              `level_id`,
                                              `first_player_name`,
                                              `second_player_name`) VALUES('{$tournament_id}',$level_id,'Test1{$j}','Test2{$j}')";
                    }else{*/
                        $sql = "INSERT INTO game (
                                              `tournament_id`,
                                              `level_id`) VALUES('{$tournament_id}',$level_id)";    
                    //}
                    
                    $cur_game_id = $db->sql_nextid();
                    $games_id[$z][] = $game_id;
                    $res = $db->sql_query($sql);
                    $prev_lvl_id = $level_id;
                }
                $z++;
            }
            $sql = "SELECT g.*, lvl.number 
                    FROM game g
                    LEFT JOIN levels lvl ON lvl.id=g.level_id
                    WHERE g.tournament_id=$tournament_id 
                    ORDER BY lvl.number,id";            
            $result = $db->sql_query($sql);
            $rows = $db->sql_fetchrowset($result);
            $res_arr = [];
            foreach ($rows as $key=>$row) {
                $res_arr[$row['number']][] = $row;
            }            
            $lvls = count($res_arr);            
            
            for($z=2;$z<=$lvls;$z++){
                foreach($res_arr[$z] as $key=>$cur_child){
                    $child_id = $cur_child['id'];
                    $parent_row_1 = $res_arr[$z-1][2*$key]['id'];
                    $parent_row_2 = $res_arr[$z-1][2*$key+1]['id'];
                    $sql = "UPDATE game g SET child_id=$child_id WHERE id IN($parent_row_1,$parent_row_2)";                    
                    $result = $db->sql_query($sql);
                }
            }            
            break;
        case "edit_tournament":
            $date = strtotime($input_data->date_start);
            $id = $input_data;
            $sql = "UPDATE tournament SET `name`='{$input_data->name}', `date_start`={$date} where id={$input_data->tornament_id}";
            $res = $db->sql_query($sql);
            break;
        case "edit_game_data":
            $sql = "UPDATE game SET `first_player_name`  = '{$input_data->first_name}',
                                    `second_player_name` = '{$input_data->second_name}'                                                                                
                                    where id={$input_data->game_id}";
            $res = $db->sql_query($sql);
            if (!empty($input_data->first_player_score) || !empty($input_data->second_player_score)) {
                $sql = "SELECT max_goals 
                        FROM levels 
                        WHERE levels.id = (SELECT level_id FROM game g WHERE g.id={$input_data->game_id})";
                $res = $db->sql_query($sql);
                $max_goals_query = $db->sql_fetchrowset($res);
                $max_goals = $max_goals_query[0]['max_goals'];
                if ($input_data->first_player_score > $max_goals || $input_data->second_player_score > $max_goals) {
                    throw new Exception("Число побед не может быть больше максимального!", 300);
                }
                if ($input_data->first_player_score == $input_data->second_player_score) {
                    throw new Exception("Равный счет не допустим!", 300);
                }
                if ($input_data->first_player_score > $input_data->second_player_score &&
                    $input_data->first_player_score != $max_goals ||
                    $input_data->first_player_score < $input_data->second_player_score &&
                    $input_data->second_player_score != $max_goals
                ) {
                    throw new Exception("У одного из игроков должно быть максимальное количество побед", 300);
                }
                $sql = "UPDATE game SET `first_player_score` = {$input_data->first_player_score},
                                        `second_player_score` = {$input_data->second_player_score}                                                                                
                                    where id={$input_data->game_id}";
                $res = $db->sql_query($sql);
                $sql = "SELECT * FROM game WHERE child_id={$input_data->child_id} ORDER BY id";
                $res = $db->sql_query($sql);
                $rows = $db->sql_fetchrowset($res);
                
                if($rows[0]['id'] == $input_data->game_id){
                    if($input_data->first_player_score>$input_data->second_player_score){
                        $sql = "UPDATE game SET `first_player_name` = '{$rows[0]['first_player_name']}'                                                                                                           
                                    where id={$input_data->child_id}";    
                    }else{
                        $sql = "UPDATE game SET `first_player_name` = '{$rows[0]['second_player_name']}'                                                                                                           
                                    where id={$input_data->child_id}";
                    }
                    
                }else{
                    if($input_data->first_player_score>$input_data->second_player_score){
                        $sql = "UPDATE game SET `second_player_name` = '{$rows[1]['first_player_name']}'                                                                                                           
                                    where id={$input_data->child_id}";
                    }else{
                        $sql = "UPDATE game SET `second_player_name` = '{$rows[1]['second_player_name']}'                                                                                                           
                                    where id={$input_data->child_id}";
                    }
                }
                $res = $db->sql_query($sql);
            }
            break;
        case "edit_level_data":
            $date = strtotime($input_data->date_start);
            if (empty($input_data->max_goals) || $input_data->max_goals < 0 || $input_data->max_goals == " ") {
                throw new Exception("Необходимо указать максимальное количество побед!", 300);
            }
            $sql = "UPDATE levels SET `max_goals`  = '{$input_data->max_goals}',
                                      `date_start` = $date
                                    where id={$input_data->level_id}";
            $res = $db->sql_query($sql);
            break;
        case "edit_castom_req":            
            $sql = "UPDATE castom_question SET `text`  = '{$input_data->text}'
                                           where id={$input_data->req_id}";            
            $res = $db->sql_query($sql);
            break;
        case "set_right_answer":
            $sql = "UPDATE castom_question SET `right_answer` = '{$input_data->answer}'
                                           where id={$input_data->req_id}";
            $res = $db->sql_query($sql);
            break;
    }
    $result = [
        "status" => 200
    ];
} catch (Exception $ex) {
    $result = [
        "status" => $ex->getCode(),
        "message" => $ex->getMessage(),
    ];
}
echo json_encode($result);

