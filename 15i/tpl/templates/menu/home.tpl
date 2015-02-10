{include file="common_header.tpl"}

{*************** Include Javascript and CSS ***************}

{*** cluetip ***}
<script type="text/javascript" src="scripts/jquery.hoverIntent.js"></script>
<script type="text/javascript" src="scripts/jquery.cluetip.js"></script>{* scheduleUpdate() でイニシャライズ *}
<link rel="stylesheet" type="text/css" href="css/jquery.cluetip.css">

{*** flexslider ***}

<script type="text/javascript" src="scripts/jquery.flexslider/jquery.flexslider-min.js"></script>
<link rel="stylesheet" type="text/css" href="scripts/jquery.flexslider/flexslider.css">

{*************** Javascript ***************}
<script type="text/javascript">
{literal}
$(function() {
    // 背景画像のフルスクリーン表示
    $('html').css('height','100%');
    $('body').css('height','100%');
    $('#gen_screen_table').css('height','100%').before("<div id='background' class='background'><ul class='slides'>{/literal}{foreach from=$form.gen_background_image_arr item='image'}<li><img class='backgroundImg' src='{$image}'/></li>{/foreach}{literal}</ul></div>");
    $('#gen_footer_area').css('position','absolute').css('bottom','10px').css('width','100%').css('color','white').css('z-index','100');
    $('#background').css('display', 'none').css('z-index', '0').fadeIn(1500);
    $('#gen_chatDialog').css('display', 'none').fadeTo(4000,0.9);
    {/literal}{if $smarty.session.gen_setting_user->slideshowSpeed != '0'}{literal}
    $('.background').flexslider({
        controlNav: false,
        directionNav: false,
        animation: "fade",
        animationSpeed: 3000,
        slideshowSpeed: {/literal}{if $smarty.session.gen_setting_user->slideshowSpeed==''}30{else}{$smarty.session.gen_setting_user->slideshowSpeed}{/if}{literal}000
    });
    {/literal}{/if}{literal}
    scheduleUpdate();
});
var ctrl = false;
function scheduleUpdate(begin) {
    if (begin === undefined) {
        begin = gen.date.getDateStr(new Date());
    }
    style = $('#homeScheduleStyle').val();
    $('.gen_chiphelp').unbind('mouseover').unbind('mouseout');
    gen.ajax.connect('Config_Setting_AjaxHomeSchedule', {homeScheduleStyle : style, begin : begin},
        function(j) {
            if (!j.data) {
                return;
            }
            var html = "<tr>";
            var i=0;
            $.each(j.data, function(key, val){
                if (i%7 == 0) {
                    html += "</tr><tr>";
                }
                html += "<td class='schedule' style='width:" + (style=='1' ? "400" : "130") + "px;" + (val.is_today ? "background-color:#ffff99;" : "") + (val.is_holiday=='t' ? "background-color:#FFEAEA;" : "") + (val.is_thismonth!='t' ? "color:#5f5f5f;" : "") + "'>"
                    + "<div style='padding-bottom:5px;font-size:11px'>" + val.date + "</div>"
                    + "<div style='padding-left:3px; padding-bottom:4px; min-height: 30px'>" + (val.schedule_text==null ? '' : val.schedule_text{/literal}{*サーバー側でエスケープ済み*}{literal}) + "</div>"
                    + "</td>";
                i++;
            });
            html += "</tr>";
            $('#scheduleTable').html(html);
            $('#schedulePrevLink').attr('href', "javascript:scheduleUpdate('" + j.prev + "')");
            $('#scheduleNextLink').attr('href', "javascript:scheduleUpdate('" + j.next + "')");
            $('#scheduleArea').css('visibility','');
            $('#gen_homeScheduleBegin').val(begin);
            gen.ui.initChipHelp();
        });

    window.addEventListener('keydown', function(e){
        if (e.ctrlKey) {
            ctrl = true;
        }   
        if ($('#gen_editFrame').length != 0) {
            ctrl = false;
        }
    });
    window.addEventListener('keyup', function(e){
        if (e.key == 'Control' || e.keyIdentifier == 'Control' ) {
            ctrl = false;
        }   
    });
    window.addEventListener('blur', function(e){
        ctrl = false;
    });
}
function scheduleGoEdit(id) {
    url = 'index.php?action=Config_Schedule_Edit&schedule_id=' + id;
    if (ctrl) {
        url += '&gen_record_copy';
    }
    gen.modal.open(url);
}
function scheduleNewEdit(userId, date) {
    url = 'index.php?action=Config_Schedule_Edit&user_id=' + userId + '&begin_date=' + date;
    gen.modal.open(url);
}
{/literal}
</script>

{literal}
<style TYPE="text/css">
<!--
{/literal}{* 下の2つは背景画像のフルスクリーン表示のために必要 *}{literal}
.backgroundImg {
    min-height: 100%;
    min-width: 1024px;
    width: 100%;
    height: auto;
    position: fixed;
    left: 0;
    top: 92px;
    z-index:0;
}
@media screen and (max-width: 1024px){
    .backgroundImg {
        left: 50%;
        margin-left: -512px;
    }
}

#main {
    width: 100%;
}
a.mmlink {
    text-decoration: none;
    color: #006666;
}
table.schedule {
    min-height:50px;
    border-top:1px solid #ccc;
    border-left:1px solid #ccc
}
td.schedule {
    vertical-align: top;
    border-right:1px solid #ccc;
    border-bottom:1px solid #ccc;
}
-->
</style>
{/literal}

<div id="main" align='center'>
<input type='hidden' id='gen_homeScheduleBegin'>

<div style='position: relative; z-index:100'>
<span style='position:absolute; top: 8px; left: 10px;'>
    {if $form.showWelcomeMsgLink}<a href='index.php?action={$form.action|escape}&showWelcomeMsg' style='color:white'>{gen_tr}_g("ようこそ"){/gen_tr}</a>{/if}
    {if $form.showWelcomeMsgLink && $form.showNewFunctionLink}&nbsp;&nbsp;&nbsp;&nbsp;{/if}
    {if $form.showNewFunctionLink}<a href='index.php?action={$form.action|escape}&showNewFunction' style='color:white'>{gen_tr}_g("新しいジェネシスの新機能"){/gen_tr}</a>{/if}
</span>
{if $form.homeScheduleStyle !== false}
<span id='scheduleArea' style='position:absolute; top:40px; left:10px; background:white; -webkit-border-radius:3px; border-radius:3px; padding:6px 5px 5px 5px; opacity:0.95; visibility: hidden'>
    <div style='height:16px'>
    {gen_tr}_g("スケジュール"){/gen_tr}
    </div>
    <div style="position:absolute; top:0px; left:4px; height: 20px">
    <select id='homeScheduleStyle' onchange="scheduleUpdate()" style='margin:0px'>
        <option value="1"{if $form.homeScheduleStyle=='1'} selected{/if}>{gen_tr}_g("日"){/gen_tr}</option>
        <option value="2"{if $form.homeScheduleStyle=='2'} selected{/if}>{gen_tr}_g("1週間"){/gen_tr}</option>
        <option value="3"{if $form.homeScheduleStyle=='3'} selected{/if}>{gen_tr}_g("2週間"){/gen_tr}</option>
        <option value="4"{if $form.homeScheduleStyle=='4'} selected{/if}>{gen_tr}_g("月"){/gen_tr}</option>
    </select>
    <a href="" id='schedulePrevLink' style='color:black; padding-left:15px; font-size:14px'><</a>
    <a href="javascript:scheduleUpdate()" style='color:black; padding-left:15px; font-size:12px'>T</a>
    <a href="" id='scheduleNextLink' style='color:black; padding-left:15px; font-size:14px'>></a>
    </div>
    <table id='scheduleTable' cellspacing='0' class='schedule'>
    </table>
</span>
{/if}
</div>

</div>
{include file="common_footer.tpl"}
