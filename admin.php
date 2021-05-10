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
page_header("Прогнозы");

if ($user->data['user_id'] == ANONYMOUS)
{
    login_box('', $user->lang['LOGIN']);
}
/*
if (!in_array($user->data['user_id'] ,[2,61])){
    redirect("/user.php");
}
*/
$sql = "SELECT * FROM " . USERS_TABLE;
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);

$sql = "SELECT t.id,
               t.date_start,   
               t.name, 
               t.pairs_amount,
               COUNT(g.id) as cnt 
        FROM tournament t
        LEFT JOIN levels lvl ON lvl.tournament_id=t.id
        LEFT JOIN game g ON g.level_id=lvl.id        
        GROUP BY t.id 
        ORDER BY t.date_start DESC";
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);

?>

<!--<html>
<head>-->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12"></script>    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>

<link rel="stylesheet" href="common_styles.css"/>
<script defer src="https://use.fontawesome.com/releases/v5.0.13/js/all.js"></script>
<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/fontawesome.css" integrity="sha384-eHoocPgXsiuZh+Yy6+7DsKAerLXyJmu2Hadh4QYyt+8v86geixVYwFqUvMU8X90l" crossorigin="anonymous"/>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.3"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.22"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css" rel="stylesheet">
<meta name="viewport" content="width=600">


<!-- Date-picker itself -->
<script src="https://cdn.jsdelivr.net/npm/pc-bootstrap4-datetimepicker@4.17/build/js/bootstrap-datetimepicker.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/pc-bootstrap4-datetimepicker@4.17/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/vue-bootstrap-datetimepicker@5"></script>

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
<!--
</head>
<body>
-->

<div id="vue_app">
    <button class="btn btn-success-light btn-block my-md-3 my-sm-3" data-toggle="modal" data-target="#tournament_add_modal">Добавить турнир</button>
    <div class="modal" tabindex="-1" role="dialog" id="tournament_add_modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавление турнира</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">                        
                        <input type="text" class="form-control py-md-2 py-sm-2" name="tournament_name" placeholder="Название турнира" v-model="info.name" autocomplete="off" required>                        
                        Дата начала <date-picker v-model="info.date_start" :config="options"></date-picker>
                        <input type="text" class="form-control my-md-2 my-sm-2" name="pairs_amount" placeholder="Количество пар" v-model="info.pairs_amount" autocomplete="off" required/>                        
                    </div>                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger left" data-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-success-" v-on:click="save_tournament">Добавить</button>
                </div>
            </div>
        </div>
       
    </div>


    <table class="table table-bordered">
        <thead>
        <tr>
            <th title="Просмотреть турнир">#</th>
            <th>Название</th>
            <th>Дата начала</th>
           <!-- <th>Игры</th>-->
            <th>Прогнозы</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $row):?>
        <tr>
            <td title="Просмотреть турнир">
                <a class="btn btn-sm btn-success-light" href="edit_tornament.php?id=<?=$row['id']?>">>></a>
            </td>
            <td><?=$row["name"]?></td>
            <td><?=date("d.m.Y H:i",$row['date_start'])?></td>
            <!--<td><?=$row["pairs_amount"]?></td>-->
            <!--<td><?=$row["cnt"]?></td>-->
            <td><a class="btn btn-sm btn-success-light" href="tournament_res.php?id=<?=$row['id']?>">Посмотреть</td>
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
<script>
   
   
    new Vue({
        el:"#tournament_add_modal",
        data:{
            info:{
                name : "",
                date_start: "",
                date_end:"",
                pairs_amount: "",
                date_start_first_tour:"",
                first_round_goals:"",
            },
            options: {
                // https://momentjs.com/docs/#/displaying/
                format: 'DD.MM.YYYY H:mm:ss',
                useCurrent: false,
                showClear: true,
                showClose: true,
            }
        },
        methods:{
            save_tournament: function(){
                if(!this.check_even()){
                    alert("Количество пар должно быть степень 2 и не менее 4")
                    return
                }                
                axios.post("admin_ajax.php", {
                    method: "add_tournament",
                    name : this.info.name,
                    date_start : this.info.date_start,
                    pairs_amount : this.info.pairs_amount,
                    date_end : this.info.date_end,
                    date_start_first_tour : this.info.date_start_first_tour,
                    first_round_goals : this.info.first_round_goals
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
            },
            check_even:function() {
                    var haystack = [4,8,16,32,64];
                    var length = haystack.length;
                    for(var i = 0; i < length; i++) {
                        if(haystack[i] == this.info.pairs_amount) return true;
                    }
                    return false;
                }               
            }
        /*},
        mounted(){
            var vm = this;
            $('#datepicker').datepicker({
                weekStart: 1,
                daysOfWeekHighlighted: "6,0",
                autoclose: true,
                todayHighlight: true,
            });         
            $('#datepicker').mask("99.99.9999")
        }*/
    })
</script>
<!--
</body>
</html>
-->
