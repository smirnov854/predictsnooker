<?php
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();


$sql = "SELECT * FROM " . USERS_TABLE;
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);

$sql = "SELECT t.id,
               t.date_start,
               t.date_end,
               t.date_first_tour_start,
               t.first_round_goals,
               t.name, 
               t.pairs_amount,COUNT(g.id) as cnt 
        FROM tournament t
        LEFT JOIN game g ON g.tournament_id=t.id        
        GROUP BY t.id";
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);

page_header('О нас');
$template->set_filenames(array(
    'body' => 'test1.html',
));
?>

<!--<html>
<head>-->
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.12"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>


<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/css/bootstrap-datepicker.min.css" rel="stylesheet"/>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!--
</head>
<body>
-->

<div id="vue_app">
    <button class="btn btn-primary" data-toggle="modal" data-target="#tournament_add_modal">Добавить турнир</button>
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
                        <input type="text" class="form-control" name="tournament_name" placeholder="Название турнира" v-model="info.name" autocomplete="off" required>
                        <!--<input type="text" data-date-format="dd.mm.yyyy" id="datepicker" class="form-control" name="date_start" placeholder="Дата начала" v-model="info.date_start" autocomplete="off" required/>-->
                        Дата начала<input v-model="info.date_start" class="form-control" v-mask="'##.##.####'">
                        Дата окончания<input v-model="info.date_end" class="form-control" v-mask="'##.##.####'">
                        Дата начала первого тура<input v-model="info.date_start_first_tour" class="form-control" v-mask="'##.##.####'">
                        <input type="text" class="form-control" name="pairs_amount" placeholder="Количество пар" v-model="info.pairs_amount" autocomplete="off" required/>
                        <input type="number" class="form-control" name="pairs_amount" placeholder="Количество побед в первом туре" v-model="info.first_round_goals" autocomplete="off" required min="4" max="10"/>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger left" data-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-success" v-on:click="save_tournament">Добавить</button>
                </div>
            </div>
        </div>
    </div>


    <table class="table table-bordered">
        <thead>
        <tr>
            <th title="Просмотреть турнир">#</th>
            <th>Название турнира</th>
            <th>Начало/окончание</th>
            <th>Начало<br/>первого тура</th>
            <th>Победы 1-го тура</th>
            <th>Пары</th>
            <th>Игры</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $row):?>
            <tr>
                <td title="Просмотреть турнир">
                    <a href="edit_tornament.php?id=<?=$row['id']?>" target="_blank">>></a>
                </td>
                <td><?=$row["name"]?></td>
                <td><?=date("d.m.Y H:i",$row['date_start'])."<br/>".date("d.m.Y H:i",$row['date_end'])?></td>
                <td><?=date("d.m.Y H:i",$row['date_first_tour_start'])?></td>
                <td><?=$row["first_round_goals"]?></td>
                <td><?=$row["pairs_amount"]?></td>
                <td><?=$row["cnt"]?></td>
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
    Vue.use(VueMask.VueMaskPlugin)

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
                            alert("Данные добавлены!");
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
<?php
make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"));
page_footer();
?>
