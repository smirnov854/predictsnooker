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
$id = $request->variable("id",0);
/*
if (!in_array($user->data['user_id'] ,[2,61])){
    redirect("/user.php");
}
*/

$sql = "SELECT COUNT(DISTINCT number) as cnt FROM `levels` WHERE tournament_id=$id";

$result = $db->sql_query($sql);
$levels = $db->sql_fetchrowset($result)[0]['cnt'];

$sql = "SELECT t.id, 
               t.date_start, 
               t.name as tournament_name, 
               t.pairs_amount,
               g.first_player_name,
               g.second_player_name,
               g.id as game_id,
               IFNULL(g.child_id,0) as child_id,
               lvl.number as game_level,
               lvl.id as level_id,
               g.first_player_score,
               g.second_player_score,
               lvl.date_start as game_date_start,               
               lvl.max_goals
        FROM tournament t
        LEFT JOIN game g ON g.tournament_id=t.id    
        LEFT JOIN levels lvl ON lvl.id=g.level_id
        WHERE t.id = $id
        ORDER BY lvl.number, g.id";

$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);
$res_arr = [];
$max_level = 0;
foreach($rows as $row){
    $res_arr[$row['game_level']][]=$row;
    $max_level = $row['game_level']> $max_level ? $row['game_level'] :$max_level;    
}
$rows = $res_arr[1];

$req = "SELECT * FROM castom_question WHERE tounament_id = $id";
$result = $db->sql_query($req);
$req = $db->sql_fetchrowset($result);

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

    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/all.js"></script>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/fontawesome.css" integrity="sha384-eHoocPgXsiuZh+Yy6+7DsKAerLXyJmu2Hadh4QYyt+8v86geixVYwFqUvMU8X90l" crossorigin="anonymous"/>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.3"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.22"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css" rel="stylesheet">


    <!-- Date-picker itself -->
    <script src="https://cdn.jsdelivr.net/npm/pc-bootstrap4-datetimepicker@4.17/build/js/bootstrap-datetimepicker.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/pc-bootstrap4-datetimepicker@4.17/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue-bootstrap-datetimepicker@5"></script>

    <meta name="viewport" content="width=600">
    
</head>
<script>
    // Initialize as global component
    Vue.component('date-picker', VueBootstrapDatetimePicker);
    $.extend(true, $.fn.datetimepicker.defaults, {
        icons: {
            time: 'far fa-clock',
            date: 'far fa-calendar',
            up: 'fas fa-arrow-up',
            down: 'fas fa-arrow-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right',
            today: 'fas fa-calendar-check',
            clear: 'far fa-trash-alt',
            close: 'far fa-times-circle'
        }
    });
</script>
<body>

<div id="main_content" class="container col-lg-11 col-md-11 col-sm-11 col-xs-11">
    <div class="row my-md-3 my-sm-3"><a class="btn btn-sm btn-success-light btn-block" href="admin.php">К списку турниров</a></div>
    <div class="row mx-md-3 mx-sm-3">
        <div class="col-lg-3 col-md-3 col-sm-12">Название турнира<input  class="form-control" type="text" v-model="tournament_name"></div>
        <div class="col-lg-3 col-md-3 col-sm-12">Дата начала <date-picker v-model='date_start' :config='options' ></date-picker></div>
        <div class="col-lg-3 col-md-3 col-sm-12">Количество туров: <?=$levels?></div>  
        <div class="col-lg-3 col-md-3 col-sm-12"><button class="btn btn-sm btn-success-light" @click="update_param">Сохранить</button></div>
    </div>

    <div class="col-lg-12 col-md-12 col-sm-12">
        <label class="col-lg-12 col-md-12 col-sm-12">Дополнительные вопросы</label>
        <div class="form-row col-lg-12 col-md-12 col-sm-12">
            <input class="form-control left" style="width: 400px!important;" type="text" v-model="custom_req[0].text" placeholder="Вопрос 1" @focusout="save_custom_req(custom_req[0].req_id,custom_req[0].text,custom_req[0].answer)">
            <input class="form-control left" style="width: 60px!important;" type="text" v-model="custom_req[0].answer" @focusout="set_right_answer(custom_req[0].req_id,custom_req[0].text,custom_req[0].answer)">
        </div>        
        <div class="form-row col-lg-12 col-md-12 col-sm-12">
            <input class="form-control left" style="width: 400px!important;" type="text" v-model="custom_req[1].text" placeholder="Вопрос 2" @focusout="save_custom_req(custom_req[1].req_id,custom_req[1].text,custom_req[1].answer)">
            <input class="form-control left" style="width: 60px!important;" type="text" v-model="custom_req[1].answer" @focusout="set_right_answer(custom_req[1].req_id,custom_req[1].text,custom_req[1].answer)">
        </div>
        <div class="form-row col-lg-12 col-md-12 col-sm-12">
            <input class="form-control left"  style="width: 400px!important;" type="text" v-model="custom_req[2].text" placeholder="Вопрос 3" @focusout="save_custom_req(custom_req[2].req_id,custom_req[2].text,custom_req[2].answer)">
            <input class="form-control left" style="width: 60px!important;"type="text" v-model="custom_req[2].answer" @focusout="set_right_answer(custom_req[2].req_id,custom_req[2].text,custom_req[2].answer)">
        </div>
    </div>
    
    <div class="clearfix"></div>    
        <app-games></app-games>       
    
</div>


<script>
    Vue.use(VueMask.VueMaskPlugin)
    //Vue.directive('mask', VueMask.VueMaskDirective);
    
    Vue.component('app-games',{
        data:function(){
            return{
                options: {
                    // https://momentjs.com/docs/#/displaying/
                    format: 'DD.MM.YYYY H:mm:ss',
                    useCurrent: false,
                    showClear: true,
                    showClose: true,
                },
                levels :{
                    <?php foreach($res_arr as $level=>$rows):?>
                    <?=$level?>:
            {
                games:[
                    <?php for($i = 0;$i < count($rows);$i++):?>
                    {
                        id:<?=$rows[$i]['game_id']?>,
                        child_id : <?=$rows[$i]['child_id']?>,
                        level: <?=$rows[$i]['game_level']?>,
                        name_1: "<?=empty($rows[$i]['first_player_name']) ? "" : $rows[$i]['first_player_name']?>",
                        name_2: "<?= empty($rows[$i]['second_player_name']) ? "" : $rows[$i]['second_player_name']?>",
                        date_start: "<?=!empty($rows[$i]['game_date_start']) ? date("d.m.Y H:i:s", $rows[$i]['game_date_start']) : ""?>",
                        first_player_score:<?=$rows[$i]['first_player_score']?>,
                        max_goals:<?=!empty($rows[$i]['max_goals'])? $rows[$i]['max_goals'] : "' '"?>,
                        level_id:<?=$rows[$i]['level_id']?>, 
                        cur_index:<?=$i?>,
                        second_player_score:<?=$rows[$i]['second_player_score']?>,
                        level_name : '<?= ($level == $max_level) ? "Финал" : (($level == ($max_level-1)) ? "1/2 финала" : (($level == ($max_level-2)) ? "1/4 финала" : "Тур ".$level))?>'
                    }<?=($i < count($rows) - 1) ? "," : ""?>
                    <?php endfor?>
                ]
            }<?=($level == $max_level) ? "" : ","?>
                    <?php endforeach;?>    
                }
                
                
            }
        },
        
        template:"<div>" +
        "<div class='col-lg-4 col-md-12 col-sm-12 col-xs-12 float-left' v-for='(level,index) in levels'>" +
        "<label class='col-12 text-center bg-success'>{{level.games[0].level_name}}</label>" +
        "<div class='row my-md-3 my-sm-3'>" +
        "<div class='col col-5'><date-picker v-model='level.games[0].date_start' :config='options'></date-picker><font style='font-size: 0.6em'>Дата окончания принятия прогнозов</font></div>" +
        "<div class='col col-3'><input class='form-control' type='text' v-model='level.games[0].max_goals'><font style='font-size: 0.6em'>До скольки побед</font></div>" +        
        "<div class='col col-4'><button class='btn btn-sm btn-success-light' v-on:click='update_level_data(level.games[0])'>Сохранить</button></div>" +
        "</div>" +
        "<div class='bg-light rounded border-bottom px-md-2 py-md-2 my-md-3' v-for='game in level.games'>" +               
        "<div class='row my-md-3 my-sm-3'>" +
        "<div class='col col-4'><input class='form-control' type='text'   v-model='game.name_1' placeholder='Имя игрока'></div>" +
        "<div class='col col-3'><input class='form-control' type='number' v-model='game.first_player_score'></div>" +
        "<div class='col col-5'><button class='btn btn-sm btn-success-light' v-on:click='update_game_data(game,levels)'>Сохранить</button></div>" +        
        "</div>" +
        "<div class='row my-md-3 my-sm-3'>" +
        "<div class='col col-4'><input class='form-control' type='text'   v-model='game.name_2' placeholder='Имя игрока'></div>" +
        "<div class='col col-3'><input class='form-control' type='number' v-model='game.second_player_score'></div>" +
        "<div class='col col-5'></div>" +        
        "</div>" +
        "</div>" +
        "</div>",
        methods: {
            update_level_data: function(game){                
                axios.post("admin_ajax.php", {
                    method: "edit_level_data",                    
                    date_start: game.date_start,
                    level_id : game.level_id,
                    max_goals : game.max_goals                    
                }).then(function(response) {
                    switch(response.data.status){                        
                        case 200:
                            break;
                        case 300:
                            alert(response.data.message)
                            break;
                    }
                },(error)=>{
                    alert("Ошибка обращения к серверу!")
            });
            },
            
            update_game_data: function(game,levels){
                axios.post("admin_ajax.php", {
                    method: "edit_game_data",
                    game_id : game.id,                    
                    first_name : game.name_1,
                    second_name : game.name_2,
                    child_id : game.child_id,
                    date_start: game.date_start,
                    first_player_score: game.first_player_score,
                    second_player_score: game.second_player_score
                }).then(function(response) {
                    switch(response.data.status){
                        case 200:
                            var tmp_name = "";
                            if(game.first_player_score !=0 || game.second_player_score !=0){
                                if(Number(game.first_player_score) > Number(game.second_player_score)){
                                    tmp_name = game.name_1
                                }else{
                                    tmp_name = game.name_2
                                }
                                for(var z in levels[game.level+1].games){
                                    if(levels[game.level+1].games[z].id == game.child_id){
                                        if(game.id%2 == 0){
                                            levels[game.level+1].games[z].name_1 = tmp_name
                                        }else{
                                            levels[game.level+1].games[z].name_2 = tmp_name
                                        }
                                        break
                                    }
                                }  
                            }
                            
                            //location.reload()
                            break;
                        case 300:                            
                            alert(response.data.message)
                            break;
                    }
                },(error)=>{
                    alert("Ошибка обращения к серверу!")
            });
            },
            
        }
    }) 
        

    new Vue({
        el:"#main_content",
        data:{
            options: {
                // https://momentjs.com/docs/#/displaying/
                format: 'DD.MM.YYYY H:mm:ss',
                useCurrent: false,
                showClear: true,
                showClose: true,
            },
            tournament_id: <?=$rows[0]['id']?>,
            tournament_name:'<?=$rows[0]['tournament_name']?>',
            date_start:'<?=date("d.m.Y H:i:s",$rows[0]['date_start'])?>',
            custom_req :[
                        {req_id:'<?=$req[0]['id']?>',text:'<?=$req[0]['text']?>',answer:'<?=$req[0]['right_answer']?>'},
                        {req_id:'<?=$req[1]['id']?>',text:'<?=$req[1]['text']?>',answer:'<?=$req[1]['right_answer']?>'},
                        {req_id:'<?=$req[2]['id']?>',text:'<?=$req[2]['text']?>',answer:'<?=$req[2]['right_answer']?>'}
            ]           
        },
        
        methods:{            
            update_param: function(){
                axios.post("admin_ajax.php", {
                    method: "edit_tournament",
                    tornament_id : this.tournament_id,
                    name : this.tournament_name,
                    date_start : this.date_start
                    //pairs_amount : this.pair
                }).then(function(response) {
                    response.data
                    switch(response.data.status){
                        case 200:                            
                            break;
                        case 300:                           
                            alert(response.data.message)
                            break;
                    }
                },(error)=>{
                    alert("Ошибка обращения к серверу!")
            });
            },
            save_custom_req : function(req_id,text,answer){                
                axios.post("admin_ajax.php", {
                    method: "edit_castom_req",
                    req_id : req_id,
                    text : text                   
                    //pairs_amount : this.pair
                }).then(function(response) {
                    response.data
                    switch(response.data.status){
                        case 200:
                            break;
                        case 300:
                            alert(response.data.message)
                            break;
                    }
                },(error)=>{
                    alert("Ошибка обращения к серверу!")
            });
            },
            set_right_answer : function(req_id,text,answer){
                axios.post("admin_ajax.php", {
                    method: "set_right_answer",
                    req_id : req_id,
                    text : text,
                    answer : answer
                    //pairs_amount : this.pair
                }).then(function(response) {
                    response.data
                    switch(response.data.status){
                        case 200:
                            break;
                        case 300:
                            alert(response.data.message)
                            break;
                    }
                },(error)=>{
                    alert("Ошибка обращения к серверу!")
            });
            },
            
            
        }
    })
</script>
</body>
</html>