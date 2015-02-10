{include file="common_header.tpl"}

{*** cluetip ***}
<script type="text/javascript" src="scripts/jquery.hoverIntent.js"></script>
<script type="text/javascript" src="scripts/jquery.cluetip.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.cluetip.css">

<script>
    {literal}
    $(function(){
        gen.ui.initChipHelp();
    });
    function entry() {
        var no = [{/literal}{$form.conv_no_csv|escape}{literal}];
        var detailNo = [{/literal}{$form.conv_detail_no_csv|escape}{literal}];
        var deleted = false;
        $(no).each(function(i, val){
            if ($('[name=word_dist_' + val + ']').val() == ""
                    || (detailNo.indexOf(val)) == -1 && $('[name=word_dist_' + val + '_isdetail]').is(':checked')
                    || (detailNo.indexOf(val)) != -1 && !($('[name=word_dist_' + val + '_isdetail]').is(':checked'))) {
                deleted = true;
                return false;
            }
        });
        if (deleted) {
            if (!confirm('{/literal}{gen_tr}_g("削除、もしくは明細チェックの状態が変更された項目があります。その項目のデータがすべて削除されますが、実行してもよろしいですか？"){/gen_tr}{literal}')) {
                return;
            }
        }
        document.forms[0].submit();
    }
    
    function clearLine(index) {
        $('[name=word_dist_' + index + ']').val('');
        $('[name=word_dist_' + index + '_select]').val('');
    }
    {/literal}
</script>

<center>

<div style='height:10px'></div>

<table>
    <tr valign="top">
        <td align="center">
            <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
        </td>
    </tr>
</table>

<div style='height:30px'></div>

<form name="form1" method="POST" action="index.php?action=Config_CustomColumn_Entry{if $form.classGroup!=''}&classGroup={$form.classGroup|urlencode}{/if}">
    {gen_reload}
    {if $form.result=='success'}
        <span style='background: #99ffcc'>{gen_tr}_g("フィールド・クリエイター設定を登録しました。"){/gen_tr}</span><br><br>
    {/if}
    
    {gen_tr}_g("各画面にオリジナルの項目を作成することができます。"){/gen_tr}<br><br>
    {if $form.classGroup=='Stock_Inout'}<span style='color:blue'>{gen_tr}_g("[資材管理]-[入庫登録]、[資材管理]-[出庫登録]、[購買管理]-[支給登録] の3画面共通の設定となります。"){/gen_tr}</span><br><br>{/if}
    <input type="button" class="gen-button" style="width:200px" value="{gen_tr}_g("登録"){/gen_tr}" onClick="entry()">
    <br><br>

    {if $form.error_msg!=''}<font color="red">{$form.error_msg|escape}</font><br><br>{/if}
    <table>
        <tr valign="top">
            {assign var="customColumnCount" value=$smarty.const.GEN_CUSTOM_COLUMN_COUNT}
            {foreach from=$form.custom_cat_arr item=catName name=customCategories}
                {assign var="customCategoriesIndex" value=$smarty.foreach.customCategories.iteration-1}
                <td align="left">
                    <span style='height:20px;font-size:14px;color:blue;border-left:solid 5px blue'>&nbsp;&nbsp;{$catName[0]|escape}</span>
                    <table>
                        <tr>
                            <td colspan="2"></td>
                            <td align="center">{gen_tr}_g("項目名"){/gen_tr}
                                <p class='helptext_1' style='display:none;'>
                                    {gen_tr}_g("この項目に名称を入力すると、その名称の項目が作成されます。"){/gen_tr}<br><br>
                                    {gen_tr}_g("作成できる項目には「文字」「数値」「日付」の3つのタイプがあります。"){/gen_tr}
                                    {gen_tr}_g("「文字」であればどんなデータも入力できますが、数値や日付しか入力しない項目の場合、専用のタイプを使用したほうが便利です。入力時にチェックがかかりますし、絞り込みや集計も行いやすいためです。"){/gen_tr}<br><br>
                                    {gen_tr}_g("名称を削除すると項目が削除されます。その項目に入力されているデータも削除されます。（名称変更の場合は削除されません。）"){/gen_tr}<br>
                                </p>
                                <a class='gen_chiphelp' href='#' title='{gen_tr}_g("項目名"){/gen_tr}' rel='p.helptext_1' style='color:black;text-decoration:none;'>
                                <img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>
                                </td>
                            <td align="center">{gen_tr}_g("セレクタ選択肢"){/gen_tr}
                                <p class='helptext_2' style='display:none;'>
                                    {gen_tr}_g("この項目を設定すると、セレクタ（プルダウン）形式の項目になります。"){/gen_tr}<br><br>
                                    {gen_tr}_g("セレクタの選択肢を半角セミコロン（;）区切りで設定します。"){/gen_tr}<br>
                                    {gen_tr}_g("例：「男;女」「東京都;神奈川県;千葉県」"){/gen_tr}<br><br>
                                    {gen_tr}_g("重複した選択肢は自動的に削除されます。"){/gen_tr}<br>
                                    {gen_tr}_g("また、選択肢のなかに空文字（ブランク）を含めることはできません。例えば「男;女;;」のようにすることはできません。必要であれば「男;女;(なし)」あるいは「男;女;(未選択)」などのように設定してください。"){/gen_tr}<br>
                                </p>
                                <a class='gen_chiphelp' href='#' title='{gen_tr}_g("セレクタ選択肢"){/gen_tr}' rel='p.helptext_2' style='color:black;text-decoration:none;'>
                                <img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>
                                </td>
                            <td align="center">
                                {if $catName[1]}
                                {gen_tr}_g("明細"){/gen_tr}
                                <p class='helptext_3' style='display:none;'>
                                    {gen_tr}_g("このチェックボックスをオンにすると、編集画面の明細行に項目が追加されます。"){/gen_tr}<br><br>
                                </p>
                                <a class='gen_chiphelp' href='#' title='{gen_tr}_g("明細"){/gen_tr}' rel='p.helptext_3' style='color:black;text-decoration:none;'>
                                <img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>
                                {/if}
                                </td>
                            <td colspan="2"></td>
                        </tr>
                        {section name=customTypes loop=3}
                            {assign var="customTypesIndex" value=$smarty.section.customTypes.iteration-1}
                            {section name=converters loop=$smarty.const.GEN_CUSTOM_COLUMN_COUNT}
                                {assign var="convertersIndex" value=$smarty.section.converters.iteration}
                                <tr>
                                    {assign var="customIndex" value=$customCategoriesIndex*$customColumnCount*3+$customTypesIndex*$customColumnCount+$convertersIndex}
                                    {assign var="sourceName" value="word_source_"|cat:$customIndex}
                                    {assign var="distName" value="word_dist_"|cat:$customIndex}
                                    {assign var="distSelectName" value="word_dist_"|cat:$customIndex|cat:"_select"}
                                    {assign var="distCheckName" value="word_dist_"|cat:$customIndex|cat:"_isdetail"}
                                    <td nowrap>{$form.$sourceName|escape}<input type="hidden" name="{$sourceName|escape}" value="{$form.$sourceName|escape}"></td>
                                    <td style="width:30px; text-align: center"></td>
                                    <td><input type="textbox" name="{$distName|escape}" value="{$form.$distName|escape}" style="width:150px"></td>
                                    <td>{if $customTypesIndex==0}<input type="textbox" name="{$distName|escape}_select" value="{$form.$distSelectName|escape}" style="width:150px">{/if}</td>
                                    <td>{if $catName[1]}<input type="checkbox" name="{$distName|escape}_isdetail" value="check"{if $form.$distCheckName} checked{/if}>{/if}</td>
                                    <td style='width:10px'></td>
                                    <td><a href='javascript:clearLine({$customIndex|escape})' style='font-size:8px;color:#999'>{gen_tr}_g("クリア"){/gen_tr}</a></td>
                                </tr>
                            {/section}
                            {if $customTypesIndex < 2}
                                <tr><td colspan="3"><div style='height:1px;border-top:solid 1px #999'></div></td></tr>
                            {/if}
                        {/section}
                    </table>
                </td>
                {if $smarty.foreach.customCategories.iteration%2==0}
                    </tr>
                    <tr style="height:30px"><td></td></tr>
                    <tr valign="top">
                {else}
                    <td width='50px'></td>
                {/if}
            {/foreach}
        </tr>
    </table>
</form>
</center>

{include file="common_footer.tpl"}

