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
$input = file_get_contents('php://input');
$input_data = json_decode($input);
$user_id = $user->data['user_id'];
try {
    switch ($input_data->method) {
        case "do_prediction":
            if (!empty($input_data->first_player_score) || !empty($input_data->second_player_score)) {
                $sql = "SELECT max_goals, g.date_start 
                        FROM game g
                        LEFT JOIN levels lvl ON lvl.id=g.level_id 
                        WHERE g.id={$input_data->game_id}";
                $res = $db->sql_query($sql);
                $max_goals_query = $db->sql_fetchrowset($res);
                $max_goals = $max_goals_query[0]['max_goals'];
                $date_start = $max_goals_query[0]['date_start'];
                if ($date_start < time()) {
                    throw new Exception("Матч уже начася!", 300);
                }
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
                    throw new Exception("У одного из игроков должно быть максимальное количество побед для данного тура", 300);
                }
                $sql = "INSERT INTO user_prediction (`user_id`,
                                            `game_id`,
                                            `first_player_score`,
                                            `second_player_score`) 
                                  VALUES('{$user_id}',
                                  {$input_data->game_id},
                                  {$input_data->first_player_score},
                                  {$input_data->second_player_score}
                                 )";
                $res = $db->sql_query($sql);
            }
            break;
        case "common_prediction":
                $sql = "INSERT INTO user_castom_question (`user_id`,
                                            `castom_req_id`,
                                            `answer`) 
                                  VALUES('{$user_id}',
                                  '{$input_data->req_id}',
                                  '{$input_data->req_answer}'                                  
                                 )
                                 ON DUPLICATE KEY UPDATE  `answer` = '{$input_data->req_answer}'";    
            
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


