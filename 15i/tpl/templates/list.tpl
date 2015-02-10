{include file="common_header.tpl"}

{*************** Include Javascript and CSS ***************}

{*** Genesiss ***}
<script type="text/javascript" src="scripts/gen_colwidth.js"></script>
<script type="text/javascript" src="scripts/gen_contextMenu.js"></script>
<link rel="stylesheet" type="text/css" href="css/list.css">

{*** cluetip ***}
<script type="text/javascript" src="scripts/jquery.hoverIntent.js"></script>
<script type="text/javascript" src="scripts/jquery.cluetip.js"></script>{* gen.list.initでイニシャライズ *}
<link rel="stylesheet" type="text/css" href="css/jquery.cluetip.css">

<script type="text/javascript" src="scripts/mousewheel/jquery.mousewheel.js"></script>

{*************** Javascript ***************}

<script type="text/javascript">
{literal}
$(function() {
    {/literal}
    gen.slider.init('gen_search_area', 'gen_search_area_link', '&gt;&gt;{gen_tr}_g("表示条件を表示"){/gen_tr} (F6)','&lt;&lt;{gen_tr}_g("表示条件を隠す"){/gen_tr} (F6)', {if $smarty.session.gen_setting_user->gen_slider_gen_search_area=='true'}true{else}false{/if}, false, 'gen.list.table.onSlideFrame','gen_search_area_icon');
    {literal}

    gen_pageInit(true); {/literal}{* listtable.tpl *}{literal}
    
    {/literal}{*表示条件パターンはそのセレクタが変更された時のみ読みだすようフラグをたてる（詳細はListBaseを「gen_savedSearchConditionUpdateFlag」で検索）。表示条件を「なし」に変更したときは、すべてをリセットするためリロードする。クロス集計関連は、すべての要素がセットされたときのみ再表示。 *}{literal}
    $('#gen_search_gen_savedSearchCondition').change(function(){if (this.value=='nothing') {location.href='index.php?action={/literal}{$form.gen_listAction|replace:"&gen_restore_search_condition=true":""|escape|replace:"&amp;":"&"}{*&amp;処理はStock_Inout_Listの&classificationのため*}{literal}&gen_search_gen_savedSearchCondition=nothing';} else {$('#gen_dataTable').append("<input type='hidden' name='gen_savedSearchConditionUpdateFlag' value='true'>");$('#gen_searchButton').click()}});
    {/literal}{*拡張DDの_showは除外。即時更新されるとonchangeによるhidden値設定が間に合わないため。拡張DDだけはsmartyプラグインで即時更新設定を行っている。 *}{literal}
    $('[id^=gen_search_],[id^=gen_datePattern_gen_search_],[id^=gen_strPattern_gen_search_]').filter(':input:not([id$=_show])').filter(':input:not([id^=gen_search_gen_cross])').filter(':input:not(#gen_search_gen_savedSearchCondition)').change(function(){$('#gen_searchButton').click()});
    $('select[id^=gen_search_gen_cross]').change(function(){if ($('select[id^=gen_search_gen_cross][id!=gen_search_gen_crossTableChart] option:selected[value=]').length==0 || this.value=='') {$('#gen_searchButton').click()}});

    {/literal}{if $form.gen_showIntro == 'true'}gen.intro.start(true);{/if}{literal}
});
{/literal}

$.event.add(window, "load", function() {literal}{{/literal}
    {$form.gen_onLoad_noEscape}
{literal}}{/literal});

{* リストテーブル部分をAjaxロード *}
function listUpdate(param, isPageLoad, isAfterReport) {literal}{{/literal}
    gen.waitDialog.show('{gen_tr}_g("処理中です。お待ちください"){/gen_tr}...');
    var url = "index.php?action={$form.gen_listAction}{*escapeしてはいけない。Stock_Inout_Listの&classificationが&amp;になる*}&gen_restore_search_condition=true";

    {* chromeの場合、帳票発行後のリスト更新を行わない。ReportへのPOSTと$.loadが同時に発生すると正しく動作しないため。 *}
    if (gen.util.isWebkit && isAfterReport) {literal}{{/literal}
        gen.waitDialog.hide();
        return;
    {literal}}{/literal}

    gen.list.table.saveScroll();

    {if $form.gen_beforeListUpdateScript_noEscape!=''}{$form.gen_beforeListUpdateScript_noEscape}{/if}
 
    {* innerHTMLで書きこむと<script>の内容がスクリプトとして認識されないため、load()やhtml()を使用すること。これらは内部的にappendするのでスクリプトが認識される *}
    $('#gen_updateArea').load(
        url + "&gen_tableload=true"
        ,param
        ,function() {literal}{{/literal}
            if ($('#gen_loginDisplayFlag').length!=0) 
                location.href='index.php?action=Login'+ ($('#gen_loginDisplayFlag').html()=='1' ? '&gen_concurrent_error=true' : '');{*セッション切れの場合*}
            gen_pageInit(false);
            if (!isPageLoad) 
                gen.waitDialog.hide();
            {if $form.gen_afterListUpdateScript_noEscape!=''}{$form.gen_afterListUpdateScript_noEscape}{/if}
        {literal}}{/literal}
        );
{literal}}{/literal}
</script>

{*************** Contents ***************}
<div id="main" align='center'>

{* click edit *}
<input type='hidden' id='gen_list_edit_action' value='{$form.gen_editAction|escape}'>

{* add <visibility:hidden>. サイズ調整終了まで非表示にしている。13iではresizeTableを非表示にしていた *}
<div id='gen_page_div' style='text-align:center;visibility:hidden'>
    {* onSubmitを追加して、Enter時のsubmitを防止 *}
    <form action="index.php?action={$form.gen_listAction|escape}&gen_restore_search_condition=true" id="form1" name="form1" method="post" autocomplete="off" onSubmit="return false;">

    <table width="100%" id="gen_page_table" cellspacing="0" cellpadding="0">
    <tr>
        <td valign="top" id="gen_search_area_all">
            <div id='gen_search_area' style='background-color: #ece8df;overflow-x:hidden;overflow-y:auto'>
            <table id='gen_search_area_inner' border="0" align="center" style="position:relative">
                <tr>
                    <td>
                        <span style="cursor:pointer"> {* style は aタグに指定するとcluetipにより上書きされてしまうので、ここに指定 *}
                            <table><tr valign='middle'>
                            <td>
                                {* formタグでsubmitを防止しているため、submitボタンではなく通常ボタンにし、onClickでsubmit処理を行っている *}
                                <input id="gen_searchButton" type="button" style="width:110px" onClick="{if $form.gen_beforeSearchScript!=''}{$form.gen_beforeSearchScript|escape}{else}gen.list.postForm(){/if}" value="  {gen_tr}_g("再表示 (F1)"){/gen_tr}  " tabindex="-1">
                            </td>
                            <td width="5px"></td>
                            <td style='font-size:11px; font-weight: bold;'>
                                <a id='gen_reloadButton' href="index.php?action={$form.gen_listAction|replace:"&gen_restore_search_condition=true":""|escape}" style="color:#8e8e93" tabindex="-1">{gen_tr}_g("リセット"){/gen_tr}</a>
                            </td>
                            </tr></table>
                            <div style='position:absolute; right:20px; top:11px'>
                                <a id='gen_addSearchColumnButton' href="javascript:gen_showSearchColumnAddDialog()" style="color:#000000" tabindex="-1" title="{gen_tr}_g("表示条件のカスタマイズ"){/gen_tr}"><img src='img/search_config.png' style="border:none" /></a>
                            </div>
                        </span>
                    </td>
                </tr>
                <tr height="10px"></tr>
                <tr>
                    <td align="left">
                        <table id="gen_search_table" align="left" style="width:250px">
                            <tr>
                                <td>
                                    {gen_search_control searchControlArray=$form.gen_searchControlArray pins=$form.gen_pins actionWithPageMode=$form.gen_actionWithPageMode}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            </div>
        </td>
        <td style='vertical-align: top; width:100%'>
            <div style='position:relative'>
                <div style='position:absolute; left: 10px; top: 0px'>
                    <table>{* 垂直中央配列のためのtable *}
                        <tr>
                            {* 下のリンクは非表示になったが、sliderの動作上、エレメントとしては必要 *}
                            <td style='display: none'><a id='gen_search_area_link' href="javascript:void(0)" style="color:#000000" tabindex="-1"></a></td>
                            <td><img id='gen_search_area_icon' style="padding-top: 3px; cursor: pointer" title="{gen_tr}_g("表示条件を表示/非表示(F6)"){/gen_tr}" src='img/list/list_showsearcharea.png'></td>
                            <td width='10px'></td>
                        </tr>
                    </table>
                </div>
            </div>
            <table>
                <tr valign="top">
                    <td align="center">
                        <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
                    </td>
                </tr>
                <tr valign="top">
                    <td align="center">
                        {* list本体 *}
                        <div id='gen_updateArea'>
                        {include file="listtable.tpl"}
                        </div>

                        <input type='hidden' id='gen_scrollTop' name='gen_scrollTop' value='{$form.gen_scrollTop|escape}'>
                        <input type='hidden' id='gen_scrollLeftF0' name='gen_scrollLeftF0' value='{$form.gen_scrollLeftF0|escape}'>
                        <input type='hidden' id='gen_scrollLeftD0' name='gen_scrollLeftD0' value='{$form.gen_scrollLeftD0|escape}'>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </table>
    </form>
</div>
<!-- selenium用 --><input type="hidden" id="gen_hilight_id" value="{$form.gen_hilight_id|escape}">
</div>
{include file="common_footer.tpl" gen_nofooter="true"}
