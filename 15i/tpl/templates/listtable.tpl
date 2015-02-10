<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">{*Chrome文字化け対処のためここでもエンコード指定する*}

{*************** Include Javascript and CSS ***************}

{*** chart ***}
{if $form.gen_useChart=='true'}
<link href="scripts/visualize/visualize.css" type="text/css" rel="stylesheet" />
<link href="scripts/visualize/visualize-light.css" type="text/css" rel="stylesheet" />
<script type="text/javascript" src="scripts/visualize/excanvas.js"></script>
<script type="text/javascript" src="scripts/visualize/visualize.jQuery.js"></script>
{/if}    


{*********************** script section ***********************}

<script type="text/javascript">
var menu1width, menu2width
 , gen_isLCE = {if $form.gen_gen_isClickableTable === 'true' || $smarty.session.gen_setting_user->listClickEnable === 'true' && !$form.gen_crossTableShow}true{else}false{/if}
 , gen_isDE = {if $form.gen_directEditEnable === 'true' && $smarty.session.gen_setting_user->directEdit === 'true' && !$form.gen_crossTableShow}true{else}false{/if};
{*前回のLazyLoadタイマーがまだ残っているなら消しておく*}
if ('gen_listappend_timerF' in window) {literal}{{/literal}
    clearTimeout(gen_listappend_timerF);
    gen_domArr_F = null;
{literal}}{/literal}
if ('gen_listappend_timerD' in window) {literal}{{/literal}
    clearTimeout(gen_listappend_timerD);
    gen_domArr_D = null;
{literal}}{/literal}
function gen_pageInit(isPageLoad) {literal}{{/literal}
    gen.list.init(
        false
        ,$('#gen_data_count').val()
        ,$('#gen_fixColCount').val()
        ,$('#gen_colCount').val()
        ,'{$form.gen_listAction|escape|replace:"&amp;":"&"}'{* 入出庫画面の &classification= 対応*}
        ,$('#gen_reqid').val()
        ,$('#gen_fixWidth').val()!='' ? $('#gen_fixWidth').val() : 0
        ,{$form.gen_titleAggregateSectionHeight|escape}
        ,$('#gen_actionWithColumnMode').val()
        ,$('#gen_actionWithPageMode').val()
        ,isPageLoad
        ,{if $form.gen_existWrapOn}true{else}false{/if}
    );
    {if $form.gen_useChart=='true'}gen.chart.init();{/if}
    {if $form.gen_crossTableHorizOptions}
        $('[name=gen_search_gen_crossTableHorizontal] option').remove();
        $('[name=gen_search_gen_crossTableHorizontal]').append($('{foreach from=$form.gen_crossTableHorizOptions key=key item=item}<option value="{$key|escape}"{if (string)$key==$form.gen_search_gen_crossTableHorizontal} selected{/if}>{$item|escape}</option>{/foreach}'));
    {/if}
    {if $form.gen_crossTableVertOptions}
        $('[name=gen_search_gen_crossTableVertical] option').remove();
        $('[name=gen_search_gen_crossTableVertical]').append($('{foreach from=$form.gen_crossTableVertOptions key=key item=item}<option value="{$key|escape}"{if (string)$key==$form.gen_search_gen_crossTableVertical} selected{/if}>{$item|escape}</option>{/foreach}'));
    {/if}
    {if $form.gen_crossTableValueOptions}
        $('[name=gen_search_gen_crossTableValue] option').remove();
        $('[name=gen_search_gen_crossTableValue]').append($('{foreach from=$form.gen_crossTableValueOptions key=key item=item}<option value="{$key|escape}"{if (string)$key==$form.gen_search_gen_crossTableValue} selected{/if}>{$item|escape}</option>{/foreach}'));
    {/if}
    {if $form.gen_crossTableChartOptions}
        $('[name=gen_search_gen_crossTableChart] option').remove();
        $('[name=gen_search_gen_crossTableChart]').append($('{foreach from=$form.gen_crossTableChartOptions key=key item=item}<option value="{$key|escape}"{if (string)$key==$form.gen_search_gen_crossTableChart} selected{/if}>{$item|escape}</option>{/foreach}'));
    {/if}
    {if $form.gen_searchConditionRestoreByScript == 't'}
        {foreach from=$form.gen_searchControlArray item=ctl}
            {if $ctl.field != ''}
                {assign var="searchName" value="gen_search_"|cat:$ctl.field|escape}
                if ($('#{$searchName}').length > 0) $('#{$searchName}').val('{if in_array($searchName, $form)}{$form[$searchName]|escape}{/if}');
                {assign var="searchNameMatch" value="gen_search_match_mode_gen_search_"|cat:$ctl.field|escape}
                if ($('#{$searchNameMatch}').length > 0) $('#{$searchNameMatch}').val('{if in_array($searchNameMatch, $form) && $form[$searchNameMatch] != ""}{$form[$searchNameMatch]|escape}{else}0{/if}');
                {assign var="searchNameFrom" value=$searchName|cat:"_from"}
                if ($('#{$searchNameFrom}').length > 0) $('#{$searchNameFrom}').val('{if in_array($searchNameFrom, $form)}{$form[$searchNameFrom]|escape}{/if}');
                {assign var="searchNameTo" value=$searchName|cat:"_to"}
                if ($('#{$searchNameTo}').length > 0) $('#{$searchNameTo}').val('{if in_array($searchNameTo, $form)}{$form[$searchNameTo]|escape}{/if}');
                {assign var="searchNameDP" value="gen_datePattern_"|cat:$searchName}
                if ($('#{$searchNameDP}').length > 0) $('#{$searchNameDP}').val('{if in_array($searchNameDP, $form)}{$form[$searchNameDP]|escape}{/if}');
                {assign var="searchNameSP" value="gen_strPattern_"|cat:$searchName}
                if ($('#{$searchNameSP}').length > 0) $('#{$searchNameSP}').val('{if in_array($searchNameSP, $form)}{$form[$searchNameSP]|escape}{/if}');
                {assign var="searchNameY" value="gen_search_"|cat:$ctl.field|cat:"_Year"|escape}
                if ($('#{$searchNameY}').length > 0) $('#{$searchNameY}').val('{if in_array($searchNameY, $form)}{$form[$searchNameY]|escape}{/if}');
                {assign var="searchNameM" value="gen_search_"|cat:$ctl.field|cat:"_Month"|escape}
                if ($('#{$searchNameM}').length > 0) $('#{$searchNameM}').val('{if in_array($searchNameM, $form)}{$form[$searchNameM]|escape}{/if}');
            {/if}
        {/foreach}
    {/if}
    {$form.gen_onPageInit|escape}
    {* チャット未読件数の更新 *}
    {literal}
    badge = $('#gen_chatUnreadCount');
    if (badge.length > 0) {
        var cnt = '{/literal}{$form.gen_chat_unread_count}{literal}';
        if (cnt != '' && parseInt(cnt) > 0) {
            badge.text(cnt);
        } else {
            badge.remove();
        }
    }
    badge = $('#gen_chatUnreadCountEcom');
    if (badge.length > 0) {
        var cntEcom = '{/literal}{$form.gen_chat_unread_count_ecom}{literal}';
        if (cntEcom != '' && parseInt(cntEcom) > 0) {
            badge.text(cntEcom);
        } else {
            badge.remove();
        }
    }
    badge = $('#gen_chatUnreadCountSystem');
    if (badge.length > 0) {
        var cntSystem = '{/literal}{$form.gen_chat_unread_count_system}{literal}';
        if (cntSystem != '' && parseInt(cntSystem) > 0) {
            badge.text(cntSystem);
        } else {
            badge.remove();
        }
    }
    {/literal}
{literal}}{/literal}

function gen_showColumnAddDialog() {literal}{{/literal}
    var obj = {literal}{{/literal}{literal}}{/literal};
    {foreach from=$form.gen_fixColumnArray item=item}{if $item.denyMove==true || $item.visible===false}{else}obj['{$item.gen_num|escape}']="{if $item.hide==true}@{/if}{$item.label|escape}{$item.label_noEscape}";{/if}{/foreach}
    {foreach from=$form.gen_columnArray item=item}{if $item.denyMove==true || $item.visible===false}{else}obj['{$item.gen_num|escape}']="{if $item.hide==true}@{/if}{$item.label|escape}{$item.label_noEscape}";{/if}{/foreach}
    gen.list.table.showColumnAddDialog(obj, false);
{literal}}{/literal}

function gen_showSearchColumnAddDialog() {literal}{{/literal}
    var obj = {literal}{{/literal}{literal}}{/literal};
    {foreach from=$form.gen_searchControlArray item=item}{if $item.denyMove==true || $item.visible===false || $item.field=='' || $item.type=='hidden' || $item.type=='holder'}{else}obj['{$item.gen_num|escape}']="{if $item.hide==true}@{/if}{$item.label|escape|strip_tags}{$item.label_noEscape|strip_tags}";{/if}{/foreach}{*strip_tagsは「表示条件パターン」のアイコンの削除用*}
    gen.list.table.showColumnAddDialog(obj, true);
{literal}}{/literal}

{if $form.gen_csvImportAction_noEscape != ''}
function gen_showImportDialog() {literal}{{/literal}
    {if $form.gen_readonly}
    alert('{gen_tr}_g("インポートを行う権限がありません。詳細はシステム管理者にお尋ねください。"){/gen_tr}');
    {else}
    gen.csvImport.showImportDialog('{$form.gen_csv_page_request_id|escape}', '{$form.gen_csvImportAction_noEscape}', '{$form.gen_importMax|escape}', '{$form.gen_importMsg_noEscape}', '{$form.gen_importFromEncoding|escape}', '{$form.gen_allowUpdateCheck|escape}', '{$form.gen_allowUpdateLabel|escape}');
    {/if}
{literal}}{/literal}
{/if}

function gen_exportData(isCsv, action) {literal}{{/literal}
    var max, msg1, msg2;
    if (isCsv) {literal}{{/literal}
        max = {$smarty.const.GEN_CSV_EXPORT_MAX_COUNT};
	msg1 = '{gen_tr}_g("レコードの数が一度にエクスポート可能な件数(%s1件)を超えています。何番目のレコードからエクスポートするかを指定してください。（指定したレコードから%s2件が出力されます。）"){/gen_tr}'.replace('%s1','{$smarty.const.GEN_CSV_EXPORT_MAX_COUNT}').replace('%s2','{$smarty.const.GEN_CSV_EXPORT_MAX_COUNT}');
    {literal}}{/literal} else {literal}{{/literal}
        max = {$smarty.const.GEN_EXCEL_EXPORT_MAX_COUNT};
        msg1 = '{gen_tr}_g("レコードの数が一度にエクセル出力可能な件数(%s1件)を超えています。何番目のレコードから出力するかを指定してください。（指定したレコードから%s2件が出力されます。）"){/gen_tr}'.replace('%s1','{$smarty.const.GEN_EXCEL_EXPORT_MAX_COUNT}').replace('%s2','{$smarty.const.GEN_EXCEL_EXPORT_MAX_COUNT}');
    {literal}}{/literal}
    msg2 = '{gen_tr}_g("入力が正しくありません。1以上の数値を指定してください。"){/gen_tr}';
    gen.list.table.exportData('index.php?action='+action,'{$form.gen_totalCount|escape}',max , msg1, msg2);
{literal}}{/literal}

function gen_showListSettingDialog() {literal}{{/literal}
    gen.list.table.showListSettingDialog('index.php?action={$form.gen_listAction|escape}&gen_restore_search_condition=true', '{$form.gen_aggregateType|escape}', '{$form.gen_isClickableTable|escape}', '{$form.gen_directEditEnable|escape}', '{$form.gen_readonly|escape}', '{$form.gen_customColumnClassGroup|escape}');
{literal}}{/literal}

function gen_showReportEditDialog(reportAction) {literal}{{/literal}
    gen.reportEdit.showReportEditDialog(reportAction);
{literal}}{/literal}

{$form.gen_javascript_noEscape}
</script>

{*********************** html section ***********************}
{* 15iでは表示時のがたつきを防ぐため、メッセージ系もgen_dataTableの中に含めるようにした（だが非表示エリアをgen_page_divに拡大したため、そうでなくてもよくなったが） *}
<div id="gen_dataTable">

    <div id='gen_error'>{gen_error errorList=$errorList}</div>
    <div id="gen_modMsg"></div>
    {if strlen($form.gen_message_noEscape) > 0}<br>{$form.gen_message_noEscape}<br>{/if}
    {if strlen($form.gen_cross_message) > 0}<br><span style='color:red'>{$form.gen_cross_message|escape}</span><br>{/if}
    {if count($form.gen_data) > 0 && count($form.gen_dataMessage_noEscape) > 0}<br>{$form.gen_dataMessage_noEscape}{* エスケープしないこと *}{/if}
    {if $form.gen_useChart=='true'}
    <table class="gen_chartSource" border="1" style="display:none" data-charttype="{$form.gen_chartType|escape}" data-chartwidth="{$form.gen_chartWidth|escape}" data-chartheight="{$form.gen_chartHeight|escape}"{if $form.gen_chartAppendKey=='true'} data-appendkey="true"{/if}>
        {foreach from=$form.gen_chartData item=dataRow name=rowLoop}
            <tr>
            {foreach from=$dataRow item=dataCol name=colLoop}
                {* 1行目のthは系列ラベル（凡例）として、1列目のthは横軸ラベルとして使用される。したがって1列目・1行目をthにしておく。ただし左上隅セルはthにしてはいけない *}
                {if ($smarty.foreach.rowLoop.iteration==1 && $smarty.foreach.colLoop.iteration!=1) || ($smarty.foreach.rowLoop.iteration!=1 && $smarty.foreach.colLoop.iteration==1)}<th>{else}<td>{/if}
                {$dataCol|escape}
                {if ($smarty.foreach.rowLoop.iteration==1 && $smarty.foreach.colLoop.iteration!=1) || ($smarty.foreach.rowLoop.iteration!=1 && $smarty.foreach.colLoop.iteration==1)}</th>{else}</td>{/if}
            {/foreach}
            </tr>
        {/foreach}
    </table>
    <br>
    {/if}

    {if $form.gen_afterEntryMessage != ''}
        <div id='gen_afterEntryMessage'>
        <table border="0" cellspacing="2" cellpadding="2">
        <tr><td bgcolor="#99ffcc">{$form.gen_afterEntryMessage|escape}</td></tr>
        </table>
        <br><br>
        </div>
    {/if}
    {* クロス集計時に小計基準やフィルタを新規設定させないためのフラグ *}
    {if $form.gen_crossTableShow}<span id='gen_crossTableShow'/>{/if}

    <div id='gen_dataTableInner'>
    <table cellspacing="0" cellpadding="0" style="width:100%" align="center">
        <tr>
            <td align="left">
                <table align="left">
                    <tr>
                        {if $form.gen_returnUrl != ''}
                            <td>
                                <table><tr valign='middle'>
                                <td><img class='imgContainer sprite-arrow-180' src='img/space.gif' /></td>
                                <td><a href='{$form.gen_returnUrl|escape}' style="color:#000000">{$form.gen_returnCaption|escape}</a></td>
                                </tr></table>
                            </td>
                            <td width="30px"></td>
                        {/if}
                        <td>
                            <table cellspacing="0" padding="0">{* 垂直中央配列のためのtable *}
                                <tr>
                                    <td nowrap>
                                        <input type="text" id="gen_special_search" name="gen_special_search" style="width:120px" value="{$form.gen_special_search|escape}" onchange="$('#gen_searchButton').click()">
                                        <input type='text' style='width:0px;height:0px;opacity:0;' onfocus="$('#gen_special_search').focus()">{* ダミー。これがないと gen_special_search で enter を押しても onchangeが発火しない場合がある（次にフォーカスを得られるエレメントが存在しないとき） *}
                                    </td>
                                    <td>
                                        <img src='img/pin02.png' id='gen_pin_off_gen_special_search' style='vertical-align: middle; cursor:pointer;{if $form.gen_special_search_isOn}display:none;{/if}' onclick="gen.pin.turnOn('{$form.gen_actionWithPageMode|escape}', 'gen_special_search', '');">
                                        <img src='img/pin01.png' id='gen_pin_on_gen_special_search' style='vertical-align: middle; cursor:pointer;{if !$form.gen_special_search_isOn}display:none;{/if}' onclick="gen.pin.turnOff('{$form.gen_actionWithPageMode|escape}', 'gen_special_search', '');">
                                    </td>
                                    <td><img src='img/list/list_quicksearch.png' style="cursor:pointer" title="{gen_tr}_g("再表示(F1)"){/gen_tr}" onclick="$('#gen_searchButton').click()"></td>
                                </tr>
                            </table>
                        </td>
                        <td width="5px"></td>
                        
                        {if $form.gen_editAction != '' && $form.gen_hideNewRecordButton != "true"}
                            <td>
                                <input type="button" id="gen_newRecordButton" style="width:110px" value="{gen_tr}_g("新規登録(F3)"){/gen_tr} " style="width:130px" onClick="gen.modal.open('index.php?action={$form.gen_editAction|escape}')" {if $form.gen_readonly == 'true'}disabled="true"{/if}>
                            </td>
                            <td width="5" align="center" nowrap></td>
                            {if $form.gen_inlineEditEnable == 'true'}
                                <td>
                                    <input type="button" id="gen_inlineNewRecordButton" style="width:110px" value="{gen_tr}_g("インライン(F7)"){/gen_tr} " onClick="gen.list.newRecord()" {if $form.gen_readonly == 'true'}disabled="true"{/if}>
                                </td>
                                <td width="5" align="center" nowrap></td>
                            {/if}
                            {if $form.gen_multiEditEnable == 'true'}
                                <td>
                                    <input type="button" id="gen_multiEditRecordButton" style="width:110px" value="{gen_tr}_g("一括編集"){/gen_tr}" onClick="gen.list.multiEdit('index.php?action={$form.gen_editAction|escape}&gen_multi_edit=true&{$form.gen_idField|escape}=', 'check', {$smarty.const.GEN_MULTI_EDIT_COUNT})" {if $form.gen_readonly == 'true'}disabled="true"{/if}>
                                </td>
                                <td width="5" align=center nowrap></td>
                            {/if}
                        {/if}
                        
                        {foreach from=$form.gen_checkAndDoLinkArray item=item value=value name=icon}
                            <td align="center">
                                <table><tr valign='middle'>
                                <td><img src='img/tick.png' border='0'></td>
                                <td nowrap><a id="{$item.id|escape}" href="{$item.onClick|escape}" style="color:#000000">{$item.value|escape}</a></td>
                                </tr></table>
                            </td>
                            <td width="5" align="center" nowrap></td>
                        {/foreach}
                        {foreach from=$form.gen_checkAndGoLinkArray item=item value=value name=icon}
                            <td align="center">
                                <table><tr valign='middle'>
                                <td nowrap><img src='img/tick.png' border='0'><img src='img/arrow.png' border='0'></td>
                                <td nowrap><a id="{$item.id|escape}" href="{$item.onClick|escape}" style="color:#000000">{$item.value|escape}</a></td>
                                </tr></table>
                            </td>
                            <td width="5" align="center" nowrap></td>
                        {/foreach}
                        {foreach from=$form.gen_goLinkArray item=item value=value name=icon}
                            <td align="center">
                                <table><tr valign='middle'>
                                <td><img src='img/arrow.png' border='0'></td>
                                <td nowrap><a id="{$item.id|escape}" href="{$item.onClick|escape}" style="color:#000000">{$item.value|escape}</a></td>
                                </tr></table>
                            </td>
                            <td width="5" align="center" nowrap></td>
                        {/foreach}
                        {foreach from=$form.gen_excelLinkArray item=item value=value name=icon}
                            <td align="center">
                                <table><tr valign='middle'>
                                <td><img src='img/report-excel.png' border='0'></td>
                                <td nowrap><a href="javascript:gen_exportData(false, '{$item.action|escape}')" style="color:#000000">{$item.label|escape}</a></td>
                                </tr></table>
                            </td>
                            <td width="5" align="center" nowrap></td>
                        {/foreach}
                        
                        {foreach from=$form.gen_reportArray item=item value=value name=icon}
                            <td align="center">
                                <table><tr valign='middle'>
                                <td><img src="img/list/list_printout.png" /></td>
                                <td nowrap><a href="{$item.link|escape}" style="color:#000000">{$item.label|escape}</a></td>
                                </tr></table>
                            </td>
                            {if $item.reportEdit != ''}
                                <td align="center" style="cursor:pointer;"> {* style は aタグに指定するとcluetipにより上書きされてしまうので、ここに指定 *}
                                    <a id="gen_reportEditButton_{$item.reportEdit|escape}" class="gen_reportEditButton" title="{gen_tr}_g("帳票を自由にカスタマイズすることができます。"){/gen_tr}" onclick="javascript:gen_showReportEditDialog('{$item.reportEdit|escape}')"><img src='img/list/list_reportedit.png' border='0'/></a>
                                </td>
                                <td width="5" align=center nowrap></td>
                            {/if}
                            <td width="5" align=center nowrap></td>
                        {/foreach}
                    </tr>
                </table>
            </td>
            <td align="right">
                <table align="right">
                    <tr style='height:41px'>{* heightはこの行のアイコン群が収まる高さにする。ここで指定しておかないと、画像ロードが遅れてgen.list.table.setListSize()より後になった場合にリストの高さ調整がうまくいかない *}
                        {if $form.gen_csvImportAction_noEscape != ''}
                            <td align=center>
                                <a href="javascript:gen_showImportDialog()" style="color:#000000"><img id='gen_importButton' src="img/list/list_import.png" title="{gen_tr}_g("CSVインポート"){/gen_tr}" border="0"></a>
                            </td>

                            <td width="5" align=center nowrap></td>
                        {/if}
                        {if $form.gen_csvExportAction_noEscape != ''}
                            <td align=center>
                                <a href="javascript:gen_exportData(true, '{$form.gen_csvExportAction_noEscape}&gen_special_search={$form.gen_special_search|escape}')" style="color:#000000"><img id='gen_exportButton' src="img/list/list_export.png" title="{gen_tr}_g("CSVエクスポート"){/gen_tr}" border="0"></a>
                            </td>
                            <td width="5" align=center nowrap></td>
                        {/if}
                        {if $form.gen_excelAction != ''}
                            <td align=center>
                                <a href="javascript:gen_exportData(false, '{$form.gen_excelAction|escape}&gen_special_search={$form.gen_special_search|escape}')" style="color:#000000"><img id='gen_excelExportButton' src="img/list/list_excel.png" title="{gen_tr}_g("エクセル出力"){/gen_tr}(F4)" border="0"></a>
                            </td>
                            <td width="5" align=center nowrap></td>
                        {/if}
                        {if $form.gen_editExcel=='true'}
                            <td align=center>
                                <a href="index.php?action={$form.gen_editExcelAction|escape}" style="color:#000000"><img id='gen_excelEditButton' src="img/list/list_exceledit.png" title="{gen_tr}_g("エクセルで編集"){/gen_tr}" border="0"></a>
                            </td>
                            <td width="5" align=center nowrap></td>
                        {/if}
                        <td align=center>
                            <a href="javascript:gen_showListSettingDialog()" style="color:#000000"><img id='gen_listSettingButton' src="img/list/list_setting.png" title="{gen_tr}_g("設定変更"){/gen_tr}" border="0"></a>
                        </td>
                        <td width="5" align=center nowrap></td>
                        <td align=center>
                            <a href="javascript:gen_showColumnAddDialog()" style="color:#000000"><img id='gen_addColumnButton' src="img/list/list_columnselect.png" title="{gen_tr}_g("表示項目選択"){/gen_tr}" border="0"></a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {* widthはJSで調整される。0pxではなく1pxにしているのはChromeでのがたつき防止のため。0だとサイズ調整までの間、大きな幅が確保されてしまう*}
    {* ----- design change ----- delete "visibility:hidden". ここではなく、dataTableを非表示にするようにした *}
    {* margin-right:auto はリストを左寄せするための設定。メニューやリスト上ボタン類よりウィンドウ幅が狭い状態のとき、左寄せでないと不自然 *}
    {* iPadでは、サブメニュー表示時に下の方が切れてしまうのを防ぐため min-height を設定している *}
    <div id="gen_resizeTable" style="width:1px; {if $form.gen_iPad}min-height:500px{else}height:0px{/if}; overflow:hidden; margin-right:auto; background-color:#f8f8f8; border:0px #cccccc solid">
        <table border="0" cellspacing="0" cellpadding="0" align="center">
            <tr valign="top">
            <td>
            {if count($form.gen_fixColumnArray) > 0}
                <div id="F0" style="text-align:left; height:{$form.gen_titleAggregateSectionHeight|escape}px; overflow:hidden;">
                <table border="0" bgcolor='#cccccc' cellspacing='1' cellpadding='2' style="">
                   {gen_data_list
                        data=$form.gen_data
                        aggregateRowHeight=$form.gen_aggregateRowHeight
                        columnArray=$form.gen_fixColumnArray
                        isFixTable=true
                        isTitle=true
                        isDetailEdit=false
                        orderby=$form.gen_orderby
                        titleRowHeight=$form.gen_titleRowHeight
                        subSumCriteria=$form.gen_subSumCriteria
                        isiPad=$form.gen_iPad
                    }
                </table>
                </div>
            {/if}
            </td>
            <td style='text-align:left; height:{$form.gen_titleRowHeight|escape}px'>

            {if count($form.gen_columnArray) > 0}
                <div id="D0" style="text-align:left; height:{$form.gen_titleAggregateSectionHeight|escape}px; overflow:hidden">
                <table border="0" bgcolor='#cccccc' cellspacing='1' cellpadding='2' style="">
                   {gen_data_list
                        data=$form.gen_data
                        aggregateRowHeight=$form.gen_aggregateRowHeight
                        columnArray=$form.gen_columnArray
                        isFixTable=false
                        isTitle=true
                        isDetailEdit=false
                        orderby=$form.gen_orderby
                        titleRowHeight=$form.gen_titleRowHeight
                        subSumCriteria=$form.gen_subSumCriteria
                        isiPad=$form.gen_iPad
                    }
                </table>
                </div>
            {/if}
            </td>
            </tr>

            {if is_array($form.gen_data) && count($form.gen_data) > 0}
            <tr valign="top">
            <td>
            {if count($form.gen_fixColumnArray) > 0}
                <div id="F1" style="text-align:left; width:{$form.gen_fixWidth|escape}px; overflow-x:scroll; overflow-y:hidden; ">
                <table border="0" bgcolor='#cccccc' cellspacing="1" cellpadding="2" style="">
                    {gen_data_list
                        alterColorDisable=$form.gen_alterColorDisable
                        columnArray=$form.gen_fixColumnArray
                        data=$form.gen_data
                        dataRowHeight=$form.gen_dataRowHeight
                        deleteAction=$form.gen_deleteAction
                        editAction=$form.gen_editAction
                        existFixTable=$form.gen_existFixTable
                        existScrollTable=$form.gen_existScrollTable
                        hilightId=$form.gen_hilight_id
                        idField=$form.gen_idField
                        isClickableTable=$form.gen_isClickableTable
                        isDetailEdit=false
                        isFixTable=true
                        isOrderby=$form.gen_isOrderby
                        isTitle=false
                        optionParam=$form.gen_optionParam
                        optionValue=$form.gen_optionValue
                        orderby=$form.gen_orderby
                        readonly=$form.gen_readonly
                        rowColorCondition=$form.gen_rowColorCondition
                        titleRowHeight=$form.gen_titleRowHeight
                        aggregateRowHeight=$form.gen_aggregateRowHeight
                        subSumCriteria=$form.gen_subSumCriteria
                        subSumCriteriaDateType=$form.gen_subSumCriteriaDateType
                        page=$form.gen_page
                        isLastPage=$form.gen_isLastPage
                        isiPad=$form.gen_iPad
                    }
                </table>
                </div>
            {/if}
            </td>
            <td valign="top">
            {if count($form.gen_columnArray) > 0}
                <div id="D1" style="text-align:left; overflow-x:scroll; overflow-y:scroll" onScroll="gen.list.table.onDivScroll()">
                <table border="0" bgcolor='#cccccc' cellspacing="1" cellpadding="2" style="">
                    {gen_data_list
                        alterColorDisable=$form.gen_alterColorDisable
                        columnArray=$form.gen_columnArray
                        data=$form.gen_data
                        dataRowHeight=$form.gen_dataRowHeight
                        deleteAction=$form.gen_deleteAction
                        editAction=$form.gen_editAction
                        existFixTable=$form.gen_existFixTable
                        existScrollTable=$form.gen_existScrollTable
                        hilightId=$form.gen_hilight_id
                        idField=$form.gen_idField
                        isClickableTable=$form.gen_isClickableTable
                        isDetailEdit=false
                        isFixTable=false
                        isOrderby=$form.gen_isOrderby
                        isTitle=false
                        optionParam=$form.gen_optionParam
                        optionValue=$form.gen_optionValue
                        orderby=$form.gen_orderby
                        readonly=$form.gen_readonly
                        rowColorCondition=$form.gen_rowColorCondition
                        titleRowHeight=$form.gen_titleRowHeight
                        aggregateRowHeight=$form.gen_aggregateRowHeight
                        subSumCriteria=$form.gen_subSumCriteria
                        subSumCriteriaDateType=$form.gen_subSumCriteriaDateType
                        page=$form.gen_page
                        isLastPage=$form.gen_isLastPage
                        isiPad=$form.gen_iPad
                    }
                 </table>
                </div>
            {/if}
            </td>
            </tr>

            {else}
            <tr>
               <td colspan="{$form.gen_columnCount|escape}">
                    <div style='height:20px'></div>
                    <table style="height:30px; margin-left:auto ; margin-right:auto;"><tr><td bgcolor="#fdd9db">{gen_tr}_g("表示するデータがありません。"){/gen_tr}</td></tr></table>
                    <div id="D1" style="text-align:center"/>
                </td>
            </tr>
            {/if}
        </table>
    </div> {* resizeTable *}

    <table padding="0" cellspacing="0" width="100%">
        <tr>
            <td width="150px">
                {if count($form.gen_filterColumn) > 0}
                <a class="gen_chiphelp" href="#" rel="p.helptext_filterColumn" title="{gen_tr}_g("フィルタがかかっている列"){/gen_tr}" style="color: red; text-decoration: none;">{gen_tr}_g("フィルタON"){/gen_tr}</a>
                <p class='helptext_filterColumn' style='display:none;'>
                    {foreach from=$form.gen_filterColumn key=key item=item name=filterColumn}
                        {$item|escape}<br>
                    {/foreach}
                </p>
                &nbsp;&nbsp;<a style='font-size:11px;color:gray' href="javascript:gen.list.table.filterReset();">{gen_tr}_g("全解除"){/gen_tr}</a>
                {/if}
            </td>
            <td align="center">
                <div id='gen_nav_noEscape' style='white-space:nowrap'><span id='gen_intro_nav'>{$form.gen_nav_noEscape}</span></div>
            </td>
            <td width="150px" align="right">
                {if count($form.gen_colorSample)>0}
                    <table padding="0" cellspacing="0">
                        <tr align="right">
                            <td style="padding:0"><img src="img/list/list_color.png" style="border:none"></td>
                            <td style="padding:0"><a class="gen_chiphelp" href="#" rel="p.helptext_colorSample" title="{gen_tr}_g("色の意味"){/gen_tr}" style="color: #999; text-decoration: none;">{gen_tr}_g("リストの色の意味"){/gen_tr}</a></td>
                        </tr>
                    </table>
                    <p class='helptext_colorSample' style='display:none;'>
                        {foreach from=$form.gen_colorSample key=key item=item name=colorSample}
                            <span style='background-color:#{$key|escape};font-size:11px'>{$item[0]|escape}</span> {$item[1]|escape}{if $smarty.foreach.colorSample.iteration < count($form.gen_colorSample)}<br>{/if}
                        {/foreach}
                    </p>
                {/if}
            </td>
        </tr>
    </table>
    </div> {* gen_dataTableInner *}
</div> {* gen_dataTable *}


<div>
    <input type='hidden' id='gen_reqid' value='{$form.gen_page_request_id|escape}'>
    <input type="hidden" id="gen_data_count" value="{$form.gen_data|@count}">
    <input type="hidden" id="gen_fixColCount" value="{$form.gen_fixColumnArray|@count}">
    <input type="hidden" id="gen_colCount" value="{$form.gen_columnArray|@count}">
    <input type="hidden" id="gen_fixWidth" value="{$form.gen_fixWidth|escape}">
    <input type="hidden" id="gen_actionWithColumnMode" value="{$form.gen_actionWithColumnMode|escape}">
    <input type="hidden" id="gen_actionWithPageMode" value="{$form.gen_actionWithPageMode|escape}">
</div>
<script>
{literal}                                                
function rc (cellId, rowNum, id) {
    if (gen_isDE) { gen.listcell.focus(cellId, true); return }; 
    if(!gen_isLCE) return; gen.modal.open('index.php?action={/literal}{$form.gen_editAction|escape}&{$form.gen_idField|escape}{literal}=' + id); 
}
{/literal}
</script>                                                
<span></span>{*IEでは$('...').load()したときに、なぜか後ろから4行が切れてしまうようなので*}
<span></span>
<span></span>
<span></span>
