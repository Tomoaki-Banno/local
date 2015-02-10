{include file="common_header.tpl"}
<script>
    {literal}
    function clearLine(index) {
        $('[name=word_source_' + index + ']').val('');
        $('[name=word_dist_' + index + ']').val('');
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

<form name="form1" method="POST" action="index.php?action=Config_WordConvert_Entry">
    {gen_reload}
    {if $form.result=='success'}
        <span style='background: #99ffcc'>{gen_tr}_g("ネーム・スイッチャー設定を登録しました。"){/gen_tr}</span><br><br>
    {/if}

    {gen_tr}_g("ネーム・スイッチャーは上から順に適用されます。"){/gen_tr}<br><br>
    <input type="submit" value="{gen_tr}_g("登録"){/gen_tr}" style="width:150px">
    <br><br>

    {if $form.error_msg!=''}<font color="red">{$form.error_msg|escape}</font><br><br>{/if}
    <table>
        <tr valign="top">
            <td>
                <table>
                    {section name=convertList loop=40}
                        <tr>
                            {assign var="index" value=$smarty.section.convertList.iteration+$customIndex}
                            {assign var="sourceName" value="word_source_"|cat:$index}
                            {assign var="distName" value="word_dist_"|cat:$index}
                            <td style="width:50px; text-align: center">{$index|escape}</td>
                            <td><input type="textbox" name="{$sourceName|escape}" value="{$form.$sourceName|escape}" style="width:150px"></td>
                            <td style="width:60px; text-align: center">⇒</td>
                            <td><input type="textbox" name="{$distName|escape}" value="{$form.$distName|escape}" style="width:150px"></td>
                            <td style='width:10px'></td>
                            <td><a href='javascript:clearLine({$index|escape})' style='font-size:8px;color:#999'>{gen_tr}_g("クリア"){/gen_tr}</a></td>
                        </tr>
                    {/section}
                </table>
            </td>
        </tr>
    </table>
</form>
</center>

{include file="common_footer.tpl"}

