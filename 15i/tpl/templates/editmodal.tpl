<!DOCTYPE html>
{* add class（常にスクロールバーを表示させる。品目マスタでタブを切り替えたときのがたつきを防ぐため）*}
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Expires" CONTENT="-1">       <!-- Cache-Control:no-cache だと「戻る」等もキャッシュ無効になってしまう -->
<meta http-equiv="Pragma" content="no-cache">  <!-- 旧サーバ用 -->

<title>GENESISS15i</title>

{*************** Include Javascript and CSS ***************}

<LINK rel="stylesheet" type="text/css" href="css/common.css">
<link rel="stylesheet" type="text/css" href="css/common{if $form.gen_iPad}_ipad{else}_pc{/if}.css">

{*** jQuery ***}
<!--[if lt IE 9]>
   <script type="text/javascript" src="scripts/jquery/jquery-1.9.0.min.js"></script>
<![endif]-->
<!-[if gte IE 9]><!->
    <script type="text/javascript" src="scripts/jquery/jquery-2.0.1.min.js"></script>
<!-[endif]->

{*** jQuery UI(AutoComplete) ***}
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.core.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.widget.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.position.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.menu.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.autocomplete.min.js"></script>
<link rel="stylesheet" type="text/css" href="scripts/jquery.ui/css/jquery-ui.min.css">

{*** YUI ***}
<script type="text/javascript" src="yui/yahoo-dom-event/yahoo-dom-event.js"></script>{*最初に*}
<script type="text/javascript" src="yui/animation/animation-min.js"></script>
<script type="text/javascript" src="yui/datasource/datasource-min.js"></script>{*autocompleteより前に*}
<script type="text/javascript" src="yui/calendar/calendar-min.js"></script>
<script type="text/javascript" src="yui/connection/connection-min.js"></script>
<script type="text/javascript" src="yui/container/container-min.js"></script>
<script type="text/javascript" src="yui/dragdrop/dragdrop-min.js"></script>
<script type="text/javascript" src="yui/element/element-min.js"></script>
<script type="text/javascript" src="yui/tabview/tabview-min.js"></script>
<script type="text/javascript" src="yui/resize/resize-min.js"></script>
<link rel="stylesheet" type="text/css" href="yui/calendar/assets/skins/sam/calendar.css">
<link rel="stylesheet" type="text/css" href="yui/container/assets/skins/sam/container.css">
<link rel="stylesheet" type="text/css" href="yui/tabview/assets/skins/sam/tabview.css">
<link rel="stylesheet" type="text/css" href="yui/resize/assets/skins/sam/resize.css">

{*** JS Gettext/wordConvert ***}
{assign var="getTextLastModPropertyName" value="jsGetText"|cat:$smarty.session.gen_language|cat:"LastMod"}
<link rel="gettext" type="application/json" href="index.php?action=download&cat=jsgettext&{$smarty.session.gen_setting_company->$getTextLastModPropertyName}{*.json*}">{*GetText.jsに読ませるために最後に.jsonが必要*}
<script type="text/javascript" src="scripts/gettext/Gettext.js"></script>

{*** Genesiss ***}
<script type="text/javascript" src="scripts/gen_calendar.js"></script>
<script type="text/javascript" src="scripts/gen_dropdown.js"></script>
<script type="text/javascript" src="scripts/gen_script.js"></script>
<script type="text/javascript" src="scripts/gen_shortcut.js"></script>
<script type="text/javascript" src="scripts/gen_slider.js"></script>
<script type="text/javascript" src="scripts/gen_stickynote.js"></script>
<script type="text/javascript" src="scripts/gen_waitdialog.js"></script>
<link rel="stylesheet" type="text/css" href="css/edit.css">

{*** cluetip ***}
<script type="text/javascript" src="scripts/jquery.hoverIntent.js"></script>
<script type="text/javascript" src="scripts/jquery.cluetip.js"></script>{* gen.edit.initでイニシャライズ *}
<link rel="stylesheet" type="text/css" href="css/jquery.cluetip.css">

{*************** Javascript ***************}

<script type="text/javascript">
{* getText/wordConvert *}{literal}
var gen_gettext = new Gettext({"domain": "messages"});
function _g(msgid) { return gen_gettext.gettext(msgid); }
{/literal}
var gen_iPad = {if $form.gen_iPad}true{else}false{/if};

$(function () {literal}{{/literal}
    $('#submit1').focus();{* このダイアログのどこかにフォーカスをあてておかないと、ショートカットキーが効かない（背面に効いてしまう）。例：受注で納品済みレコードを開いて（Editクラスでフォーカス指定がない状態）、F3を押したとき誤動作*}
    gen.edit.init('{$form.gen_editActionWithKey}', '{$form.gen_nextPageReport_noEscape}','','{$form.gen_focus_element_id|escape}',true,'{$form.gen_readonly|escape}');

    {foreach item=row from=$form.gen_stickynote_info name=rowloop}
    gen.stickynote.showNote('{$row.stickynote_id|escape}', {$row.x_pos|escape}, {$row.y_pos|escape}, {$row.width|escape}, {$row.height|escape}, {if $row.show_all_user=='t'}true{else}false{/if}, {if $row.allow_edit_all_user=='t'}true{else}false{/if}, {if $row.show_all_action=='t'}true{else}false{/if}, {if $row.system_note_no==''}false{else}true{/if}, '{$smarty.session.user_id|escape}', '{$row.author_id|escape}', "{$row.author_name|escape}", "{*無害化ずみ。エスケープしないこと*}{$row.content}", '{$row.color|escape}');
    {/foreach}
{literal}}{/literal});

jQuery.event.add(window, "load", function() {literal}{{/literal}
    {$form.gen_onLoad_noEscape}
{literal}}{/literal});

{literal}
function gen_round(val) {
    return gen.util.round(val, {/literal}{$form.GEN_DECIMAL_POINT_EDIT|escape}{literal});
}

function gen_onSubmit(reportPrint) {
    {/literal}{*バリデーションと登録の処理は一応、ajaxが終了してから行っている。しかしajax中の判断は単純にフラグで行っているので、完璧ではない。*}
        {*複数のajaxリクエストが錯綜しているときはチェックが漏れることがある。（ひとつのリクエストが終わるたびにフラグがオフになるので。）*}
        {*また、登録前スクリプト（$form['gen_beforeEntryScript_noEscape']）でsubmitを行っている場合はこのチェックが効かない。*}{literal}
    if (gen.ajax.isInProgress) {
        setTimeout(function(){gen_onSubmit(reportPrint)},300);
        return;
    }
    if (!gen_allcontrols_check()) {
        alert('{/literal}{gen_tr}_g("入力内容に誤りがあります。メッセージが表示されている箇所を確認してください。"){/gen_tr}{literal}');
        return;
    }
    {/literal}

    {if $form.gen_reportParamForEntry_noEscape!=''}if (reportPrint) $('#form1').attr('action', $('#form1').attr('action') + '{$form.gen_reportParamForEntry_noEscape}');{/if}
    {if $form.gen_beforeEntryScript_noEscape!=''}{$form.gen_beforeEntryScript_noEscape}{else}document.forms[0].submit(){/if};
    {literal}
}

function gen_showControlAddDialog() {
    var obj = {};
    {/literal}
    {foreach from=$form.gen_editControlArray item=item}{if $item.denyMove==true || $item.visible===false || $item.type=='literal' || $item.type=='table' || $item.type=='list'}{else}obj['{$item.gen_num|escape}']="{if $item.hide==true}@{/if}{if $item.label!=''}{$item.label|replace:"\"":"\""|escape}{else}{$item.label_noEscape}{/if}";{/if}{/foreach}
    gen.edit.showControlAddDialog(obj, '{$form.gen_editActionWithKey}', '{$form.gen_actionWithPageMode_noEscape|escape}', '{gen_tr}_g("表示する項目を選択"){/gen_tr}', '{gen_tr}_g("登録"){/gen_tr}', '{gen_tr}_g("キャンセル"){/gen_tr}', '{gen_tr}_g("全チェック/全解除"){/gen_tr}', '{gen_tr}_g("リセット"){/gen_tr}', '{gen_tr}_g("項目の表示/非表示と並び順を初期状態に戻します。実行してもよろしいですか？"){/gen_tr}');
    {literal}
}

{/literal}

{*クライアントバリデーション*}
function gen_allcontrols_check() {literal}{{/literal}
{if $form.gen_multiEditAction != ''} return true; {else}
    chk = true;
    {foreach from=$form.gen_clientValidArr key="key" item="value" name="list"}
    {if $value.script!=''} {*dependColumnのみの場合、scriptが指定されていない。その際はallCheckの対象にしてはいけない*}
    {if $value.listCount!=''}
    for (i=1;i<={$value.listCount|escape};i++) {literal}{{/literal}
        if (!{$key|escape}_check(i)) chk = false;
    {literal}}{/literal}
    {else}
    if (!{$key|escape}_check()) chk = false;
    {/if}{/if}
    {/foreach}
    return chk;
{/if}
{literal}}{/literal}

{foreach from=$form.gen_clientValidArr key="key" item="value" name="list"}
function {$key|escape}_check({if $value.listCount!=''}lineNo{/if}) {literal}{{/literal}
{if $form.gen_multiEditAction != ''} return true; {else}
    {if $value.script!=''} {*dependColumnのみの場合、scriptが指定されていない*}
    {$value.script}
    {/if}
{/if}
{literal}}{/literal}
{/foreach}

{$form.gen_javascript_noEscape}
</script>
</head>

{*************** BODY ***************}

<body id='gen_body' bgcolor="#{$background_color|escape}" style="color: #000000;">

<input type='hidden' id='gen_max_upload_file_size' value='{$smarty.const.GEN_MAX_UPLOAD_FILE_SIZE}'>
<input type='hidden' id='gen_support_link' value='{$smarty.const.GEN_SUPPORT_LINK}'>
<input type='hidden' id='gen_faq_search_link' value='{$smarty.const.GEN_FAQ_SEARCH_LINK}'>
<input type='hidden' id='gen_ajax_token' value='{$smarty.session.gen_ajax_token|escape}'>
<input type='hidden' id='gen_starting_month' value='{$smarty.session.gen_setting_company->starting_month_of_accounting_period|escape}'>

{* onSubmitを追加して、Enter時のsubmitを防止 *}
<form action="index.php?action={$form.gen_entryAction|escape}{$form.gen_keyParamForUrl}"
  id="form1" name="form1" method="post" AUTOCOMPLETE="OFF" onSubmit="return false;">

    {if $form.gen_record_copy == true}
    <input type="hidden" name="gen_record_copy" value="true">
    {/if}

    {if $form.gen_overlapFrame == 'true'}
    <input type="hidden" name="gen_overlapFrame" value="true">
    {/if}

    {if $form.gen_multiEditAction != ''}
    <input type="hidden" name="gen_multiEditAction" value="{$form.gen_multiEditAction|escape}">
    {/if}
    {if $form.gen_multiEditKey != ''}
    <input type="hidden" name="gen_multiEditKey" value="{$form.gen_multiEditKey|escape}">
    {/if}

    { gen_reload }

    <div id='gen_operationBar' style='width:100%; height:50px; font-size:12px; background:{$form.gen_edit_mode_sub_background_color|escape}'>
        <table width='100%' border='0' cellspan='0' cellpadding='0'>
            <tr>
                <td width='285px' height='45px' align='left'>
                    <table cellspacing="0" cellpadding="0">
                        <tr>
                            <td style='width:60px; font-size:15px; font-weight:bold; white-space:nowrap; text-align:center'>[{$form.gen_edit_mode_label|escape}]</td>
                            <td style='width:225px;height:45px;overflow:hidden'>
                                {if $form.gen_record_copy=='' && ($form.gen_last_update!='' || $form.gen_last_updater!='')}
                                    {if $form.gen_last_update!=''}{gen_tr}_g("最終更新"){/gen_tr}&nbsp;：&nbsp;{$form.gen_last_update|escape}{/if}
                                    {if $form.gen_last_updater!=''}<br>{gen_tr}_g("入力者"){/gen_tr}&nbsp;：&nbsp;{$form.gen_last_updater|escape}{/if}<br>
                                    <input type='hidden' id='gen_last_update' name='gen_last_update' value='{$form.gen_last_update|escape}'>
                                    <input type='hidden' id='gen_last_updater' name='gen_last_updater' value='{$form.gen_last_updater|escape}'>
                                {/if}
                                <span id='gen_titlebar' style='width:225px;overflow:hidden;white-space:nowrap'></span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td align='center' valign='middle' nowrap>
                    {* formタグでsubmitを防止しているため、submitボタンではなく通常ボタンにし、onClickでsubmit処理を行っている *}
                    <input type="button" class="gen_button" id="submit1" style="width:100px" value="{gen_tr}_g("登録 (F3)"){/gen_tr}" {if $form.gen_readonly!='true'}{else}disabled="true"{/if} onClick="gen_onSubmit(false)">
                    {if $form.gen_reportParamForEntry_noEscape!=''}
                    &nbsp;&nbsp;&nbsp;<input type="button" class="gen_button" id="submitPrint1" style="width:100px" value="{gen_tr}_g("登録して印刷"){/gen_tr}" {if $form.gen_readonly!='true'}{else}disabled="true"{/if} onClick="gen_onSubmit(true)">
                    {/if}
                    &nbsp;&nbsp;&nbsp;<input type="button" class="gen_button" id="gen_cancelButton" style="width:100px" value="{gen_tr}_g("閉じる (Esc)"){/gen_tr}" onClick="{if $form.gen_overlapFrame != 'true'}parent.gen.modal.close({if $form.gen_updated == 'true'}true{else}false{/if}){else}parent.document.getElementById('gen_parentFrame').close();{/if}">
                </td>
                <td width='300px' align='right' nowrap> {*左端tdよりスクロールバーの分だけ長くしておかないと、中央tdがセンターに来ない*}
                    <table cellspacing="0" cellpadding="0">
                        <tr>
                            <td>
                                {*ヘルプ*}
                                <table cellspacing="0"><tr>
                                    <td><img class='imgContainer sprite-question-frame' src='img/space.gif' /></td>
                                    <td><span id='gen_pageHelpDialogParent'><a href="javascript:gen.list.showPageHelpDialog('{$form.gen_pageHelp|escape}')" style="color:#000000">{gen_tr}_g("ヘルプ"){/gen_tr}</a></span></td>
                                </tr></table>
                            </td>
                            <td align="right">
                                {*項目選択*}
                                <table cellspacing="0"><tr>
                                    <td><img class='imgContainer sprite-wrench' src='img/space.gif' /></td>
                                    <td><span id='gen_addControlSpan'><a id='gen_addControlButton' href="javascript:gen_showControlAddDialog()" style="color:#000000">{gen_tr}_g("項目選択"){/gen_tr}</a></span></td>
                                </tr></table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                {*メモパッド*}
                                <table cellspacing="0"><tr>
                                {if $smarty.session.user_customer_id!='-1'} {* EDIユーザーはメモパッドを使えない *}
                                    <td></td>
                                    <td></td>
                                {else}
                                    <td><img src='img/sticky-note--pencil.png'></td>
                                    <td><a id='gen_addStickyNoteButton' href="javascript:gen.stickynote.createNote('{$form.action|escape}', $('#gen_addStickyNoteButton').offset().left-120, $('#gen_addStickyNoteButton').offset().top+20, true, false)" style="color:#000000">{gen_tr}_g("メモパッド"){/gen_tr}</a></td>
                                {/if}
                                </tr></table>
                            </td>
                            <td>
                                {*帳票印刷*}
                                <table cellspacing="0"><tr>
                                    {if $form.gen_reportAction==''}
                                        <td><img src="img/space.gif" style='width:15px'/></td>
                                        <td style="color:#ccc">{gen_tr}_g("帳票印刷"){/gen_tr}</td>
                                    {else}
                                        <td><img class="imgContainer sprite-reports-stack" src="img/space.gif"/></td>
                                        <td><a href="javascript:gen.edit.outputReport('{$form.gen_reportAction|escape}{$form.gen_reportParam|escape}');" style='color:#000'>{gen_tr}_g("帳票印刷"){/gen_tr}</a></td>
                                    {/if}
                                </tr></table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div id="gen_contents" align="center" style="height:0px; overflow-y: scroll; ">
    <table id='all' style='margin-top: 10px;'>
        <tr>
        <td align="center">
            <table border='0' cellspacing='0' cellpadding='0'>
                <tr bgcolor='#ffffff'>
                    <td colspan="3">
                        {* height追加。表示前にスクロールバーが出てしまうのを防ぐ。onLoadであらためて高さ設定される *}
                        {* position:relative はメモパッドやページヒントの位置指定をabsoluteで行うため。またIE7で、tabview(品目マスタ)がdivからはみ出してしまうのを防いでいる *}
                        <div id="gen_edit_area" align="center" style="width: 100%; {if $form.gen_overlapFrame != 'true'}height:100%;{/if} position:relative">
                            {* メッセージ群は枠内に移動した。メッセージ表示時に枠がフレームからはみ出し、スクロールバーが出てしまうのを避けるため *}
                            {if $form.gen_readonly == 'true'}<font color='blue'>{$form.gen_readonlyMessage|escape}</font><br>{/if}
                            <br>
                            { gen_error errorList=$errorList }
                            {if $form.gen_afterEntryMessage != ''}
                                <table border="0" cellspacing="2" cellpadding="2">
                                    <tr><td bgcolor="#99ffcc">{$form.gen_afterEntryMessage|escape}{$form.gen_afterEntryMessage_noEscape}</td></tr>
                                </table>
                            {/if}

                            <div style='text-align:center; font-size:11px; color:#999999'>{gen_tr}_g("ブルーの項目は入力必須です。各項目のタイトルをドラッグすると並び順を変更できます。"){/gen_tr}
                            <a href="javascript:gen.edit.columnReset('{$form.gen_editActionWithKey}','{gen_tr}_g("項目の並び順と表示状態をリセットします。実行してよろしいですか？"){/gen_tr}')" style='font-size:11px; color:#999'>{gen_tr}_g("リセット"){/gen_tr}</a>
                            </div><br>

                            <div id="gen_message_noEscape" align='center'>{$form.gen_message_noEscape}</div>
                            {if $form.gen_multiEditKey != ''}
                            <center><div style="height:15px"></div><div style="width:300px; background-color:#ffcc99">{gen_tr}_g("複数のレコードを一括編集しています。"){/gen_tr}</div><div style="height:15px"></div></center>
                            {/if}

                            <table border='0' cellspacing='0' cellpadding='0'>
                                {gen_edit_control
                                    editControlArray=$form.gen_editControlArray
                                    editColCount=$form.gen_edit_colcount
                                    labelWidth=$form.gen_labelWidth
                                    dataWidth=$form.gen_dataWidth
                                    pins=$form.gen_pins
                                    hidePins=$form.gen_hidePins
                                    hideHistorys=$form.gen_hideHistorys
                                    isNew = $form.gen_isNew
                                    action=$form.action
                                    actionWithKey=$form.gen_editActionWithKey
                                    actionWithPageMode_noEscape=$form.gen_actionWithPageMode_noEscape
                                    isCopyMode=$form.gen_record_copy
                                    clientValidArr=$form.gen_clientValidArr
                                    customEditListControlArray=$form.gen_customEditListControlArray
                                }
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        </tr>
    </table>{* end of id = 'all' *}
    </div>
</form>

{if $form.gen_fileUploadRecordId != ''}
    <div style='position:relative; width:100%; height:40px; background-color:#f0f0f0;'>
        {if $form.gen_readonly!='true'}
        <div>
            <script>
                gen.fileUpload.init('index.php?action=Config_Setting_FileUpload&actionGroup={$form.gen_action_group|escape}&id={$form.gen_fileUploadRecordId|escape}','','gen_editFileUpload_callback','');
                {literal}
                function gen_editFileUpload_callback(res, callbackParam) {
                    if (res.msg != '')
                        alert(res.msg);
                    if (!res.success) return;
                    var no = 10;
                    while(true) {
                        if ($('#gen_uploadfile_' + no).length == 0)
                            break;
                        no++;
                    }
                    $('#gen_uploadfiles_area').append("<span id='gen_uploadfile_" + no + "'>" +
                        "&nbsp;" +
                        "<a href=\"index.php?action=download&cat=files&file=" + res.fileName + "\" style='color:#000000'>" + res.originalFileName + "</a>" +
                        "<a href=\"javascript:gen.fileUpload.deleteUploadFile('" + res.fileName + "','" + res.originalFileName + "','gen_editFileUpload_deleteSuccess','" + no + "')\" style='color:#000000'><img class='imgContainer sprite-close' src='img/space.gif' border='0'></a>" +
                        "</span>");
                }
                function gen_editFileUpload_deleteSuccess(callbackParam) {
                    $('#gen_uploadfile_' + callbackParam).remove();
                }
                {/literal}
            </script>
        </div>
        {/if}

        <table style='position:absolute; top:0px; left:10px; height:100%; margin-left:120px'><tr><td>{* 垂直中央配置を実現するためのtable *}
            <div id='gen_uploadfiles_area'>
                {if $form.gen_uploadFiles!=false}
                {foreach from=$form.gen_uploadFiles item=uploadFile name=uploadFilesLoop}
                    {* このセクションを変更するときは、gen_script.js の gen.fileUpload.doUpload() 内のHTML部も変更すること *}
                    <span id='gen_uploadfile_{$smarty.foreach.uploadFilesLoop.iteration}'>
                    &nbsp;
                    <a href="index.php?action=download&cat=files&file={$uploadFile.file_name|escape}" style="color:#000000">{$uploadFile.original_file_name|escape}</a>
                    {if $form.gen_readonly!='true'}<a href="javascript:gen.fileUpload.deleteUploadFile('{$uploadFile.file_name|escape}', '{$uploadFile.original_file_name|escape}', 'gen_editFileUpload_deleteSuccess', '{$smarty.foreach.uploadFilesLoop.iteration}')" style="color:#000000"><img class="imgContainer sprite-close" src="img/space.gif" border="0"></a>{/if}
                    </span>
                {/foreach}
                {/if}
            </div>
        </td></tr></table>
    </div>
{/if}

</body>
</html>