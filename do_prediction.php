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
$id = $request->variable("id",0);
if(empty($id)){

}
$sql = "SELECT COUNT(DISTINCT number) as cnt 
        FROM `levels` WHERE tournament_id=$id";
$result = $db->sql_query($sql);
$levels = $db->sql_fetchrowset($result)[0]['cnt'];

$sql = "SELECT t.id, 
               t.date_start, 
               t.name as tournament_name, 
               t.pairs_amount,
               g.first_player_name,
               g.second_player_name,
               g.id as game_id,
               lvl.number as game_level,
               lvl.id as level_id,
               g.first_player_score,
               g.second_player_score,
               g.date_start as game_date_start,               
               lvl.max_goals,
               up.first_player_score as up_first_player_score,
               up.second_player_score as up_second_player_score,
               IF((g.first_player_score !=0 OR g.second_player_score!= 0),
                  IF(up.first_player_score = g.first_player_score AND up.second_player_score=g.second_player_score,3,0),-1) as point_res,
                                  IF((g.first_player_score !=0 OR g.second_player_score!= 0),
                  IF(up.first_player_score = g.first_player_score OR up.second_player_score=g.second_player_score,1,0),-1) as point_winner
        FROM tournament t
        LEFT JOIN game g ON g.tournament_id=t.id    
        LEFT JOIN user_prediction up ON up.game_id = g.id AND up.user_id={$user->data['user_id']}
        LEFT JOIN levels lvl ON lvl.id=g.level_id
        WHERE -- lvl.date_start IS NOT NULL AND 
              t.id = $id AND 
              g.first_player_name IS NOT NULL AND 
              g.first_player_name !='' AND 
              g.second_player_name IS NOT NULL AND 
              g.second_player_name !=''
        ORDER BY lvl.number, g.id";

$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);
$res_arr = [];
$max_level = 0;
foreach($rows as $row){
    $res_arr[$row['game_level']][]=$row;
    $max_level = $row['game_level']> $max_level ? $row['game_level'] :$max_level;
}
$total_points = 0;
for($i = 0;$i < count($rows);$i++){
    $total_points = $total_points + (($rows[$i]['point_res'] !='-1')&&!empty($rows[$i]['point_res'])  ? $rows[$i]['point_res']: 0 );
    $total_points = $total_points + (($rows[$i]['point_winner'] !='-1')&&!empty($rows[$i]['point_winner'])  ? $rows[$i]['point_winner']: 0);    
}



$req = "SELECT  cq.*,ucq.answer as user_answer, cq.right_answer,
                IF(cq.right_answer=ucq.answer, 3,0) as points_for_question
        FROM castom_question cq
        LEFT JOIN user_castom_question ucq ON ucq.castom_req_id=cq.id AND user_id={$user->data['user_id']}    
        WHERE tounament_id = $id";
$result = $db->sql_query($req);
$req = $db->sql_fetchrowset($result);

$sql = "SELECT DISTINCT first_player_name as player
        FROM game
        WHERE tournament_id= $id AND first_player_name IS NOT NULL AND first_player_name<>''
        UNION 
        SELECT DISTINCT second_player_name as player
        FROM game
        WHERE tournament_id= $id AND second_player_name IS NOT NULL AND second_player_name<>''"; 

$result = $db->sql_query($sql);
$player_list = $db->sql_fetchrowset($result);

?>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>
    <!-- 
     <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
     <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
     -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="common_styles.css"/>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.21.0/moment.min.js" type="text/javascript"></script>

    <script src="https://cdn.jsdelivr.net/npm/v-mask/dist/v-mask.min.js"></script>
    <meta name="viewport" content="width=600">
   
</head>
<body>

<div id="main_content">
    
    <div class="col-lg-12 col-md-12 col-sm-12 my-md-4 my-sm-4"><a class='btn btn-success-light btn-block' href="user.php">К списку турниров</a>
    </div>    
    <div class="col-lg-12 col-md-12 col-sm-12 row mx-md-2 my-md-2">
        <div class="col-lg-3 col-md-6 col-sm-12"><b>Название турнира :</b> {{tournament_name}}</div>
        <div class="col-lg-3 col-md-6 col-sm-12"><b>Дата начала:</b> {{date_start }}</div>
        <div class="col-lg-3 col-md-6 col-sm-12"><b>Количество туров:</b> <?=$levels?></div>
    </div>
    <div class="col-lg-12 col-md-12 col-sm-12">
        <label class="col-lg-12 col-md-12 col-sm-12"><b>Дополнительные вопросы</b></label>

        <div class="form-row col-lg-12 col-md-12 col-sm-12 my-md-2 my-sm-4">{{custom_req[0].text}}&nbsp&nbsp<input class="form-control" type="text" v-model="custom_req[0].user_answer" 
                   @focusout="save_custom_req(custom_req[0].req_id,custom_req[0].user_answer)" :disabled="!access_to_do_predict" style="width: 60px!important;">
        </div>
        <div class="form-row col-lg-12 col-md-12 col-sm-12 my-md-2 my-sm-4">{{custom_req[1].text}}&nbsp&nbsp
            <input class="form-control" type="text" v-model="custom_req[1].user_answer"  :disabled="!access_to_do_predict"
                   @focusout="save_custom_req(custom_req[1].req_id,custom_req[1].user_answer)" style="width: 60px!important;">
        </div>
        <div class="form-row col-lg-12 col-md-12 col-sm-12 my-md-2 my-sm-4">{{custom_req[2].text}}&nbsp&nbsp
            <input class="form-control" type="text" v-model="custom_req[2].user_answer" :disabled="!access_to_do_predict"
                   @focusout="save_custom_req(custom_req[2].req_id,custom_req[2].user_answer)" style="width: 60px!important;">            
        </div>
        <div class="form-row col-lg-12 col-md-12 col-sm-12 my-md-2 my-sm-4">Победитель &nbsp&nbsp
            <select class="form-control col-lg-3 col-sm-8" v-model="custom_req[3].user_answer" :disabled="!access_to_do_predict" @change="save_custom_req(custom_req[3].req_id,custom_req[3].user_answer)">
                <option v-for="player in players" v-bind:value="player">{{player}}</option>
            </select>
        </div>
       
    </div>
    <div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'><b>Вы заработали {{points_counter}} баллов</b></div>
    <app-games></app-games>

</div>


<script>
    Vue.use(VueMask.VueMaskPlugin)
    //Vue.directive('mask', VueMask.VueMaskDirective);

    Vue.component('app-games',{
        data:function(){
            return{
                total_points:0,
                levels :{
            <?php foreach($res_arr as $level=>$rows):?>
            <?=$level?>:
            {
                games:[
                    <?php  for($i = 0;$i < count($rows);$i++): ?>
                    {
                        id:<?=$rows[$i]['game_id']?>,
                        level: <?=$rows[$i]['game_level']?>,
                        name_1: "<?=empty($rows[$i]['first_player_name']) ? "" : $rows[$i]['first_player_name']?>",
                        name_2: "<?= empty($rows[$i]['second_player_name']) ? "" : $rows[$i]['second_player_name']?>",
                        date_start: "<?=!empty($rows[$i]['game_date_start']) ? date("d.m.Y H:i:s", $rows[$i]['game_date_start']) : ""?>",
                        first_player_score:<?=$rows[$i]['first_player_score']?>,
                        second_player_score:<?=$rows[$i]['second_player_score']?>,
                        max_goals:<?=!empty($rows[$i]['max_goals'])? $rows[$i]['max_goals'] : "' '"?>,
                        up_first_player_score: <?=!empty($rows[$i]['up_first_player_score']) ? $rows[$i]['up_first_player_score'] : 0?>,
                        up_second_player_score: <?=!empty($rows[$i]['up_second_player_score']) ? $rows[$i]['up_second_player_score'] : 0?>,
                        disabled_time: <?= time()<$rows[$i]['game_date_start'] ? "false": "true"?>,
                        not_disabled : <?=empty($rows[$i]['up_first_player_score']) && empty($rows[$i]['up_second_player_score'])  ? "true":"false" ?>,                        
                        has_result:<?= !empty($rows[$i]['first_player_score']) || !empty($rows[$i]['second_player_score']) ? "true":"false" ?>,
                        point_res:<?= (($rows[$i]['point_res'] !='-1')  ? $rows[$i]['point_res']: "false" )?>,
                        level_name : '<?= ($level == $levels) ? "Финал" : (($level == ($levels-1)) ? "1/2 финала" : (($level == ($levels-2)) ? "1/4 финала" : "Тур ".$level))?>',
                        point_winner:<?= (($rows[$i]['point_winner'] !='-1')  ? $rows[$i]['point_winner']: "false" )?>
                    }<?=","//($i < count($rows) - 1) ? "," : ""?>
                    <?php endfor?>
                ]
            }<?= ","//($level == $max_level) ? "" : ","?>
            <?php endforeach;?>
        }
            

        }
        },       
        template:"<div class='col-lg-12 col-md-12 col-sm-12 d-flex flex-column-reverse'>" +
        "<div class='col-lg-4 col-md-12 col-sm-12 col-xs-12 my-md-2 float-left' v-for='(level,index) in levels' >" +
        "<label class='col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center bg-success'>{{level.games[0].level_name}}</label>" +
        //"<div class='col col-lg-12 col-md-12 col-sm-12 col-xs-12'>Окончание принятия прогнозов: {{level.games[0].date_start}}</div>" +
        "<div class='col col-lg-12 col-md-12 col-sm-12 col-xs-12'>Максимальное количество побед: {{level.games[0].max_goals}}</div>" +
        "<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12 bg-light rounded border-bottom px-md-2 py-md-2 my-md-3' v-for='game in level.games'>" +
        "<div class='row'>" +
        "<div class='col'>" +
        "<label v-if='!game.not_disabled'>Ваш прогноз</label>" +
        "<label v-if='game.not_disabled && game.disabled_time'>Игра уже началась</label>" +
        "</div>" +
        "<div class='col'><span v-if='game.point_winner'>Баллы: {{game.point_winner + (game.point_res ? game.point_res : 0)}} </span></div>" +
        "</div>" +            
        "<div class='row'>" +
        "<div class='col col-lg-4 col-md-4 col-sm-4 col-xs-4'>{{game.name_1}}</div>" +
        "<div class='col col-lg-3 col-md-3 col-sm-3 col-xs-3 my-md-1 my-sm-1'>" +
        "<input class='form-control' type='text' v-if='game.not_disabled && !game.disabled_time' v-model='game.first_player_score'>" +
        "<span class='float-left' title='Ваш прогноз' v-else>{{game.up_first_player_score}}</span>" +
        "<span class='float-right' title='Результат игры' v-if='game.has_result'>{{game.first_player_score}}</span>" +
        "</div>" +
        "<div class='col col-lg-5 col-md-5 col-sm-5 col-xs-5'>{{game.date_start}}</div>" +
        "</div>" +
        "<div class='row my-md-1'>" +
        "<div class='col col-lg-4 col-md-4 col-sm-4 col-xs-4'>{{game.name_2}}</div>" +
        "<div class='col col-lg-3 col-md-3 col-sm-3 col-xs-3 my-md-1 my-sm-1'>" +
        "<input class='form-control float-left' type='text' v-if='game.not_disabled && !game.disabled_time' v-model='game.second_player_score'>" +
        "<span class='float-left' title='Ваш прогноз' v-else>{{game.up_second_player_score}}</span>" +
        "<span class='float-right' title='Результат игры' v-if='game.has_result'>{{game.second_player_score}}</span>" +
        "</div>" +
        "<div class='col col-lg-5 col-md-5 col-sm-5 col-xs-5'><button class='btn btn-sm btn-success-light my-md-1' v-if='game.not_disabled && !game.disabled_time' v-on:click='do_prediction(game)'>Отправить прогноз</button></div>" +
        "</div>" +
        "</div>" +
        "</div>",
        methods: {            
            do_prediction: function(game){
                axios.post("user_ajax.php", {
                    method: "do_prediction",
                    game_id : game.id,  
                    first_player_score: game.first_player_score,
                    second_player_score: game.second_player_score
                }).then(function(response) {
                    switch(response.data.status){
                        case 200:
                            console.log("ok")
                            location.reload();
                            break;
                        case 300:
                            alert(response.data.message)
                            break;
                    }
                },(error)=>{
                    alert("Ошибка обращения к серверу!")
            });
            }
        }
    })


    new Vue({
        el:"#main_content",
        
        data:{                
            points_counter : <?=$total_points + $req[0]['points_for_question'] + $req[1]['points_for_question']+$req[2]['points_for_question'] + $req[3]['points_for_question']?>,
            tournament_id: <?=$id?>,
            tournament_name:'<?=$rows[0]['tournament_name']?>',
            date_start:'<?=date("d.m.Y",$rows[0]['date_start'])?>',
            access_to_do_predict: <?= (time() < $rows[0]['game_date_start']) ? 'true' : 'false'?>,
            custom_req :[
                {req_id:'<?=$req[0]['id']?>',text:'<?=$req[0]['text']?>',user_answer:'<?=$req[0]['user_answer']?>',points:'<?=$req[0]['points_for_question']?>'},
                {req_id:'<?=$req[1]['id']?>',text:'<?=$req[1]['text']?>',user_answer:'<?=$req[1]['user_answer']?>',points:'<?=$req[1]['points_for_question']?>'},
                {req_id:'<?=$req[2]['id']?>',text:'<?=$req[2]['text']?>',user_answer:'<?=$req[2]['user_answer']?>',points:'<?=$req[2]['points_for_question']?>'},
                {req_id:'<?=$req[3]['id']?>',text:'<?=$req[3]['text']?>',user_answer:'<?=$req[3]['user_answer']?>',points:'<?=$req[3]['points_for_question']?>'}
            ],
            players : [
                <?php 
                $cnt = count($player_list);
                foreach($player_list as $row):?>
                '<?=$row['player']?>',
                <?php endforeach;?>
            ],
            winner : "",
        },
        methods: {
            save_custom_req: function(req_id,user_answer){
                axios.post("user_ajax.php", {
                    method: "common_prediction",                    
                    req_id : req_id,
                    req_answer: user_answer
                }).then(function(response) {
                    switch(response.data.status){
                        case 200:
                            console.log("ok")                            
                            break;
                        case 300:
                            alert(response.data.message)
                            break;
                    }
                },(error)=>{
                    alert("Ошибка обращения к серверу!")
            });
            }    
        },        
        })
</script>
</body>
</html>