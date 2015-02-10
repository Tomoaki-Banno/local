<!DOCTYPE html>
<html lang="ja">{* 13iまではスクロールバーを常時表示（class='outer')だった *}
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">{*UTF-7によるXSS防止のため、エンコーディングは確実に指定*}
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Expires" CONTENT="-1"> {* Cache-Control:no-cache だと「戻る」等もキャッシュ無効になってしまう *}
<meta http-equiv="Pragma" content="no-cache">  {* 旧サーバ用 *}

<link rel="shortcut icon" href="img/15i_favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="img/15i_favicon.ico" type="image/vnd.microsoft.icon">

<title>{if $form.gen_pageTitle!=''}{$form.gen_pageTitle|escape} - {/if}GENESISS</title>

{*************** Include Javascript and CSS ***************}

{*** jQuery ***}
<!--[if lt IE 9]>
   <script type="text/javascript" src="scripts/jquery/jquery-1.9.0.min.js"></script>
<![endif]-->
<!--[if gte IE 9]><!-->
    <script type="text/javascript" src="scripts/jquery/jquery-2.0.1.min.js"></script>
<!--<![endif]-->
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.core.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.widget.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.mouse.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.sortable.min.js"></script>
<script type="text/javascript" src='scripts/expanding/expanding.js'></script>
{if $form.gen_iPad}{* for draggable *}
    <script type="text/javascript" src="scripts/jquery.ui/jquery.ui.draggable.min.js"></script>
    <script type="text/javascript" src="scripts/jquery.ui.touch-punch/jquery.ui.touch-punch.js"></script>
{/if}
    
{*** YUI ***}
<script type="text/javascript" src="yui/yahoo-dom-event/yahoo-dom-event.js"></script>{*最初に*}
<script type="text/javascript" src="yui/animation/animation-min.js"></script>
<script type="text/javascript" src="yui/calendar/calendar-min.js"></script>
<script type="text/javascript" src="yui/connection/connection-min.js"></script>
<script type="text/javascript" src="yui/container/container-min.js"></script>
<script type="text/javascript" src="yui/dragdrop/dragdrop-min.js"></script>
<script type="text/javascript" src="yui/element/element-min.js"></script>
<script type="text/javascript" src="yui/resize/resize-min.js"></script>
<link rel="stylesheet" type="text/css" href="yui/calendar/assets/skins/sam/calendar.css">
<link rel="stylesheet" type="text/css" href="yui/container/assets/skins/sam/container.css">
<link rel="stylesheet" type="text/css" href="yui/resize/assets/skins/sam/resize.css">

{*** JS Gettext/wordConvert ***}
{assign var="getTextLastModPropertyName" value="jsGetText"|cat:$smarty.session.gen_language|cat:"LastMod"}
<link rel="gettext" type="application/json" href="index.php?action=download&cat=jsgettext&{$smarty.session.gen_setting_company->$getTextLastModPropertyName}{*.json*}">{*GetText.jsに読ませるために最後に.jsonが必要*}
<script type="text/javascript" src="scripts/gettext/Gettext.js"></script>

{*** Genesiss ***}
<script type="text/javascript" src="scripts/gen_calendar.js"></script>
<script type="text/javascript" src="scripts/gen_dropdown.js"></script>
<script type="text/javascript" src="scripts/gen_script.js?rev=20141028"></script>
<script type="text/javascript" src="scripts/gen_slider.js"></script>
<script type="text/javascript" src="scripts/gen_stickynote.js"></script>
<script type="text/javascript" src="scripts/gen_shortcut.js"></script>
<script type="text/javascript" src="scripts/gen_chat.js"></script>
<script type="text/javascript" src="scripts/gen_waitdialog.js"></script>
<script type="text/javascript" src="scripts/gen_intro.js"></script>
<script type="text/javascript" src="scripts/gen_modal.js"></script>
<link rel="stylesheet" type="text/css" href="css/common.css">
<link rel="stylesheet" type="text/css" href="css/common{if $form.gen_iPad}_ipad{else}_pc{/if}.css">
<link rel="stylesheet" type="text/css" href="css/header.css">
<link rel="stylesheet" type="text/css" href="css/footer.css">
{if $gen_menuCssFile !=''}<link rel="stylesheet" type="text/css" href="css/{$gen_menuCssFile|escape}.css">{/if}

<script type="text/javascript" src="scripts/jquery.lazyload/jquery.lazyload.min.js" type="text/javascript"></script>
<script type="text/javascript" src="scripts/intro/intro.min.js"></script>
<link rel="stylesheet" type="text/css" href="scripts/intro/introjs.min.css">

{*************** Javascript ***************}

<script type="text/javascript">
var gen_iPad = {if $form.gen_iPad}true{else}false{/if};
var gen_gettext;    
 $(function() {literal}{{/literal}
    gen_gettext = new Gettext({literal}{"domain": "messages"}{/literal});{*これを$(function() {..}) の外でやると、他のJS処理がGettextコンストラクタの中で行われるJSONダウンロードをブロックするようで、サーバー上で動作が異常に遅くなることがある*}
    
    $('#gen_companyLogoTag').attr('src', 'index.php?action=download&cat=companylogo&{$smarty.session.gen_setting_company->companyLogoFileLastMod|strtotime}');{*srcを直接タグに指定すると上と同じ現象が起きる*}
    $('#gen_profileImageTag').attr('src', 'index.php?action=download&cat=profileimage&{$smarty.session.gen_setting_user->profileImageLastMod|strtotime}');
    
    gen.menu.initMenuSlider('▼{gen_tr}_g("メニューバーを表示"){/gen_tr}','▲{gen_tr}_g("メニューバーを隠す"){/gen_tr}',{if $smarty.session.gen_setting_user->gen_slider_gen_menubar=='true'}true{else}false{/if});

    {foreach item=row from=$form.gen_stickynote_info name=rowloop}
    gen.stickynote.showNote('{$row.stickynote_id|escape}', {$row.x_pos|escape}, {$row.y_pos|escape}, {$row.width|escape}, {$row.height|escape}, {if $row.show_all_user=='t'}true{else}false{/if}, {if $row.allow_edit_all_user=='t'}true{else}false{/if}, {if $row.show_all_action=='t'}true{else}false{/if}, {if $row.system_note_no==''}false{else}true{/if}, '{$smarty.session.user_id|escape}', '{$row.author_id|escape}', '{$row.author_name|escape}', "{*無害化ずみ。エスケープしないこと*}{$row.content}", '{$row.color|escape}');
    {/foreach}
    {if $form.gen_allow_chat==1 && $form.gen_show_chat_dialog==1 && $smarty.session.user_customer_id=='-1'}{*アクセス権のないユーザー、およびEDIユーザーは非表示*}
    gen.chat.init('d','{$form.gen_last_chat_header_id|escape}','','{$form.gen_chat_dialog_x|escape}','{$form.gen_chat_dialog_y|escape}','{$form.gen_chat_dialog_width|escape}','{$form.gen_chat_dialog_height|escape}'{if $form.action=='Menu_Home'},true{/if});
    {/if}
        
    gen.myMenu.sortInit(); {*マイメニューを並べ替え可能に*}
    
    gen.desktopNotification.timeSpan = {$smarty.const.GEN_DESKTOP_NOTIFICATION_SPAN};
    {*デスクトップ通知するカテゴリを増やすときは下のif文に条件を追加。あと、AjaxDesktopNotificationの修正も必要*}
    {if $smarty.session.gen_setting_user->desktopNotification_chat && $form.gen_allow_chat==1 && $smarty.session.user_customer_id=='-1'}
    setTimeout(gen.desktopNotification.show, 60000); {* 画面表示の1分後にデスクトップ通知情報の確認と通知が行われる。その後、画面を表示したままにすると規定の時間間隔で実行される。ちなみに、サーバー側で更新間隔の制限をしているのでここは即時更新でもいいように思えるが、ajaxの回数を減らすために時間をおいている *}
    {/if}
{literal}}{/literal});

{literal}
function _g(msgid) { return gen_gettext.gettext(msgid); }    
{/literal}
function regMyMenu() {literal}{{/literal}
   gen.myMenu.regist('{$form.gen_actionForMenu}{*escapeしない。&classification=のある画面のため*}', '{$form.gen_pageTitle|escape}', '{gen_tr}_g("マイメニュー操作に失敗しました。"){/gen_tr}');
{literal}}{/literal}
function delMyMenu(action, page) {literal}{{/literal}
   gen.myMenu.deleteMenu(action, page, '{$form.gen_actionForMenu|escape}', '{gen_tr}_g("%sをマイメニューから削除してもよろしいですか？"){/gen_tr}', '{gen_tr}_g("マイメニュー操作に失敗しました。"){/gen_tr}');
{literal}}{/literal}
function resetMyMenu() {literal}{{/literal}
   gen.myMenu.reset('{$form.action|escape}', '{gen_tr}_g("マイメニューをすべて削除してもよろしいですか？"){/gen_tr}');
{literal}}{/literal}
{literal}    
 function gen_showSubMenu_click(id) {
      $('#gen_menu_'+id).css('left','auto');
      $('body').on('click.gen_submenuhide', function() {$('body').off('click.gen_submenuhide'); $('#gen_menu_'+id).css('left','-999em');});
 }
 function gen_showSubMenu(id, wait) {
     if (wait) {{/literal}{* メニューをマウスが通過するたびちらつくのを防ぐため、メニュー表示までにタイムラグをもうける。setTimeoutのタイマーIDは、グローバル変数で管理するとclearTimeoutのときにうまくいかない場合があるので、jQueryのdataで管理する *}{literal}
        $('#gen_menu_'+id).data('gen_menuTimer'+id, setTimeout(function(){$('#gen_menu_'+id).css('left','auto')}, 400));
     } else {
        $('#gen_menu_'+id).css('left','auto');
     }
 }
 function gen_hideSubMenu(id) {
     clearTimeout($('#gen_menu_'+id).data('gen_menuTimer'+id));
     $('#gen_menu_'+id).css('left','-999em');
 }
{/literal}    
{if $smarty.session.user_id==-1}
{literal}     
 function gen_adminWordConvertModeChange() {
     var mode = $('#gen_adminWordConvertMode').val();
     if ($('form').length > 0) {
         $('form:first').append("<input type='hidden' name='gen_adminWordConvertMode' value='" + mode + "'>").get(0).submit();
     } else {
         location.href = "index.php?action={/literal}{$form.action|escape}{literal}&gen_adminWordConvertMode=" + mode;
     }
 }
{/literal}
{/if}
</script>
</head>

{*************** BODY ***************}

{*jQueryのready() （$(document).ready(function(){}) もしくは $(function(){})）を使用する場合、onLoadを指定してはいけない *}
<body id="gen_body" text="#000000">

<input type='hidden' id='gen_max_upload_file_size' value='{$smarty.const.GEN_MAX_UPLOAD_FILE_SIZE}'>
<input type='hidden' id='gen_support_link' value='{$smarty.const.GEN_SUPPORT_LINK}'>
<input type='hidden' id='gen_faq_search_link' value='{$smarty.const.GEN_FAQ_SEARCH_LINK}'>
<input type='hidden' id='gen_ajax_token' value='{$smarty.session.gen_ajax_token|escape}'>
<input type='hidden' id='gen_starting_month' value='{$smarty.session.gen_setting_company->starting_month_of_accounting_period|escape}'>

<table id="gen_screen_table" border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="#{$background_color|escape}" align="center" style="overflow:hidden;height:100%;">
  <tr valign="top">
  <td width="100%">

    {* --------- Header Bar --------- *}

    <div id='gen_header_bar' class='gen_header' style='position:relative; width:100%; height:40px;'>
        <table height="40px" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td width="10px"></td>
            <td width="50px" align="center"><a href="index.php?action=Menu_Home" title="{gen_tr}_g("パティオ"){/gen_tr}"><img src="img/header/header_gen{if $form.action=='Menu_Home'}_2{/if}.png" style="border:none"></a></td>
            {if $smarty.session.user_customer_id=='-1'}{*EDIユーザーは非表示*}
            <td width="50px" align="center"><a href="index.php?action=Menu_Home2" title="{gen_tr}_g("コンパス"){/gen_tr}"><img src="img/header/header_dashboard{if $form.action=='Menu_Home2'}_2{/if}.png" style="border:none"></a></td>
            <td width="50px" align="center"><a href="index.php?action=Menu_Map" title="{gen_tr}_g("マップ"){/gen_tr}"><img src="img/header/header_map{if $form.action=='Menu_Map'}_2{/if}.png" style="border:none"></a></td>
            {/if}
            <td width="50px" align="center"><img id="gen_menubar_switch_icon" src="img/header/header_menubar.png" title="{gen_tr}_g("メニューバーの開閉"){/gen_tr}" style="cursor:pointer"></td>
        </tr>
        </table>
        
        <span style='position:absolute; top:0px; right:10px; font-size:12px; color:#003366; white-space:nowrap'>
            <table height="40px" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
            {if $smarty.session.user_id==-1}
                <td>
                {gen_tr}_g("ネーム・スイッチャーモード（adminのみ）"){/gen_tr}
                <br><select id='gen_adminWordConvertMode' onchange="gen_adminWordConvertModeChange()" tabindex="-1">
                <option value='0'{if $smarty.session.adminWordConvertMode==0} selected{/if}>{gen_tr}_g("通常"){/gen_tr}</option>
                <option value='1'{if $smarty.session.adminWordConvertMode==1} selected{/if}>{gen_tr}_g("[ ] で囲む"){/gen_tr}</option>
                <option value='2'{if $smarty.session.adminWordConvertMode==2} selected{/if}>{gen_tr}_g("元の用語を表示"){/gen_tr}</option>
                </select>
                </td>
                <td width="30px" style="text-align:center"><img src="img/header/header_separator.png"></td>
            {/if}
            {if $smarty.session.user_customer_id=='-1'}{*EDIユーザーは非表示*}
                <td style="vertical-align:top;padding-top:9px">
                    <a id='gen_showSchedule' href="index.php?action=Config_Schedule_List" title="{gen_tr}_g("スケジュール"){/gen_tr}"><img src="img/header/header_schedule.png" style="border:none"></a>
                </td>
                <td width="30px" style="text-align:center"></td>
            {/if}    
            {if $form.gen_allow_chat==1 && $form.action!='Menu_Chat' && $smarty.session.user_customer_id=='-1'}{*アクセス権のないユーザー、およびEDIユーザーは非表示*}
                <td>
                    <div style="position: relative">
                        <a id='gen_showChat' href="javascript:gen.chat.init('d','','','{$form.gen_chat_dialog_x|escape}','{$form.gen_chat_dialog_y|escape}','{$form.gen_chat_dialog_width|escape}','{$form.gen_chat_dialog_height|escape}')" title="{if $form.gen_chat_unread_count_msg!=''}{$form.gen_chat_unread_count_msg}{else}{gen_tr}_g("トークボード"){/gen_tr}{/if}">
                        <img src="img/header/header_chat.png" style="border:none">
                        {if $form.gen_chat_unread_count!=''}<span id='gen_chatUnreadCount' class='gen_number_icon' style="position: absolute; top: 5px; left: 14px">{$form.gen_chat_unread_count|escape}</span>{/if}
                        {if $form.gen_chat_unread_count_ecom!=''}<span id='gen_chatUnreadCountEcom' class='gen_number_icon' style="position: absolute; top: -10px; left: 14px; background: green">{$form.gen_chat_unread_count_ecom|escape}</span>{/if}
                        {if $form.gen_chat_unread_count_system!=''}<span id='gen_chatUnreadCountSystem' class='gen_number_icon' style="position: absolute; top: 5px; left: -6px; background: blue">{$form.gen_chat_unread_count_system|escape}</span>{/if}
                        </a>
                    </div>
                </td>
                <td width="30px" style="text-align:center"><img src="img/header/header_separator.png"></td>
            {/if}    
            <td><img id="gen_profileImageTag" style="width:40px" height="40px" title='{gen_tr}_g("クリックで画像を変更できます。"){/gen_tr}' onclick="gen.profileImage.showUploadDialog()"></td>{* id は gen_script.js gen.profileImage.show() で使用 *}
            <td width="10px"></td>
            <td style="text-align:right">{$smarty.session.company_name|escape}<br>{$smarty.session.user_name|escape}</td>
            <td width="30px" style="text-align:center"><img src="img/header/header_separator.png"></td>
            <td><img id="gen_companyLogoTag"></td>
            </tr></table>
        </span>
    </div>

    {* --------- Menu Bar --------- *}
    <div style='position:relative;'>{* これがないと home.tpl においてメニューバー部分が透明になってしまう。一方、これを下のgen_menubar のdivに指定するとサブメニューが表示されなくなってしまう。 *}
    <div id='gen_menubar' style='background: #F7F7F7;display:{if $smarty.session.gen_setting_user->gen_slider_gen_menubar=='true'}{else}none{/if}'>
     <ul>
         <li style='display:none'>{* GENアイコンによる開閉機能があるので、ここは不要と判断しとりあえず非表示。しかしgen.slider.init()処理の関係で、エレメントとしては存在している必要がある。　*}
             <a id='gen_menubar_link' href="javascript:void(0)" tabindex="-1">▲{gen_tr}_g("メニューバーを隠す"){/gen_tr}</a>
         </li>
         {foreach item=menu from=$form.gen_menuArr name=menuloop}
             {* 基本的にはマウスオーバーで動作するが、タブレットのためにクリックでも動作するようにしてある *}
             <li{if $menu[2]}{else}{if count($menu[3])>0} onmouseover="gen_showSubMenu('{$smarty.foreach.menuloop.iteration}',true)" onmouseout="gen_hideSubMenu('{$smarty.foreach.menuloop.iteration}')"{/if}{/if}><a href="{if $menu[2]}#{else}{if count($menu[3])>0}javascript:gen_showSubMenu_click('{$smarty.foreach.menuloop.iteration}'){else}{$menu[0]|escape}{/if}{/if}"{if $menu[2]} class='gen_menu_disable'{/if}{if $menu[4]} class='gen_menu_current'{/if} tabindex="-1">{$menu[1]|escape}{if count($menu[3])>0}▼{/if}</a>
             {if count($menu[3])>0}
                 <ul id='gen_menu_{$smarty.foreach.menuloop.iteration}' onmouseover="gen_showSubMenu('{$smarty.foreach.menuloop.iteration}',false)" onmouseout="gen_hideSubMenu('{$smarty.foreach.menuloop.iteration}')">
                 {foreach item=submenu from=$menu[3] name=submenuloop}
                     <li><a href="{$submenu[0]|escape}" tabindex="-1">{$submenu[1]|escape}</a></li>
                 {/foreach}
                 </ul>
             {/if}
             </li>
         {/foreach}
     </ul>
    </div>
    </div>
         
    {* --------- My Menu & function links --------- *}
    <div style='position:relative; top:0px; left:0px'>
        <table cellspacing="0" cellpadding="0" width="100%" style="padding-top:2px; background: #e0e3e5; border-bottom: 1px solid #c7c7c7;">
            <tr>
                <td width='15px'></td>
                <td>
                    {* line-height指定は、画面横幅が足りずマイメニューが2段表示になったときのために必要 *}
                    <span id='gen_myMenu' style='line-height:1.8'>{$form.gen_myMenuHtml_noEscape}</span>
                    <span id='gen_addMyMenu' class='my_menu' style="line-height:1.8;{if $form.gen_isMyMenuExist || $form.gen_pageTitle=='' || $smarty.session.user_customer_id!='-1'}visibility:hidden{/if}"><a id='gen_addMyMenu' href="javascript:regMyMenu()" class="my_menu_link" tabindex="-1" title="{gen_tr}_g("この画面をマイメニューに追加"){/gen_tr}">+</a></span>
                </td>
                <td align='right' nowrap>
                    <table cellspacing="0" cellpadding="0">
                        <tr>
                            <td id='gen_addStickyNoteButton'>
                                <a class='function_link' href="javascript:gen.stickynote.createNote('{$form.action|escape}', $('#gen_addStickyNoteButton').offset().left-120, $('#gen_addStickyNoteButton').offset().top+20, true, false)" tabindex="-1">
                                    <table cellspacing='0' cellpadding='0'><tr><td><img src='img/header/menu_stickynote.png' style='margin-right:10px;border:none'></td><td>{gen_tr}_g("メモパッド"){/gen_tr}</td></tr></table>
                                </a>
                            </td>
                            <td width='15px' nowrap></td>
                            <td id='gen_introButton'>
                                <a class='function_link' href="javascript:gen.intro.start()" tabindex="-1">
                                    <table cellspacing='0' cellpadding='0'><tr><td><img src='img/header/menu_intro.png' style='margin-right:10px;border:none'></td><td>{gen_tr}_g("ガイド"){/gen_tr}</td></tr></table>
                                </a>
                            </td>
                            <td width='15px' nowrap></td>
                            <td id='gen_pageHelpDialogParent'>
                                <a class='function_link' href="javascript:gen.list.showPageHelpDialog('{$form.gen_pageHelp|escape}')" tabindex="-1">
                                    <table cellspacing='0' cellpadding='0'><tr><td><img src='img/header/menu_help.png' style='margin-right:10px;border:none'></td><td>{gen_tr}_g("ヘルプ"){/gen_tr}</td></tr></table>
                                </a>
                            </td>
                            <td width='15px' nowrap></td>
                            <td>
                                <a class='function_link' href="index.php?action=Logout" tabindex="-1">
                                    <table cellspacing='0' cellpadding='0'><tr><td><img src='img/header/menu_logout.png' style='margin-right:10px;border:none'></td><td>{gen_tr}_g("ログアウト"){/gen_tr}</td></tr></table>
                                </a>
                            </td>
                            <td width='10px' nowrap></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <div id="gen_headerGap" style="height:5px"></div>
