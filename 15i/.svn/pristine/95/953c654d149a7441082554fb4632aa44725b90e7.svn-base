{include file="common_header.tpl"}

{*************** Include Javascript and CSS ***************}

<script type="text/javascript" src="scripts/jquery.hoverIntent.js"></script>
<script type="text/javascript" src="scripts/jquery.cluetip.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.cluetip.css">

{*************** Javascript ***************}

<script type="text/javascript">
{literal}
$(document).ready(function () {
	window.document.onkeydown = gen.window.onkeydown;
    $(".gen_chiphelp").each(function(){
        $(this).cluetip({local: true, hoverIntent: {interval:250}});
    });
});
{/literal}
$.event.add(window, "load", function() {literal}{{/literal}
    {$form.gen_onLoad_noEscape}
{literal}}{/literal});
{$form.gen_javascript_noEscape}
</script>

{*************** CSS ***************}
{literal}
<style TYPE="text/css">
<!--
#main {
    width: 100%;
    min-height: 640px;
}
-->
</style>
{/literal}

{*************** Contents ***************}
<div id="main" align='center'>

<div style='text-align: center; min-height: 500px;'>{*ページヒントが切れないよう、最低高さを設定する*}
    <table width="100%" id="gen_page_table" cellspacing="0" cellpadding="0">

    <tr style="height:12px"><td>
    <div style='position:relative; top:0px;'>
    {if $form.gen_returnUrl!=''}
        <span style='position:absolute; top:0px; left:10px;'>
        <table><tr valign='middle'>
        <td><img class='imgContainer sprite-arrow-180' src='img/space.gif' /></td>
        <td><a href='{$form.gen_returnUrl|escape}' style="color:#000000">{$form.gen_returnCaption|escape}</a></td>
        </tr></table>
        </span>
    {/if}

    </div>
    </td></tr>

    <tr style="height:10px">
        <td align="center">
            <div class="listTitle">
                <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
            </div>
        </td>
    </tr>

    <tr style="height:30px"><td></td></tr>
    <tr>
        <td colspan="3" align="center">

            <form name="form1" id="form1" action="index.php?action={$form.gen_reportAction|escape}" method="post" AUTOCOMPLETE="OFF">

            {gen_error errorList=$errorList}
            
            {if count($form.gen_searchControlArray) > 0}
            <table class="12px" border="0" bgcolor='#666666' cellspacing="1" cellpadding="2">
                <tr bgcolor='#c9c9c9' align="left">
                    <td>&nbsp;&nbsp;&nbsp;{gen_tr}_g("条件指定"){/gen_tr}</td>
                </tr>
                <tr bgcolor='#ffffff'>
                    <td>
                        <table>
                            {gen_search_control searchControlArray=$form.gen_searchControlArray pins=$form.gen_pins actionWithPageMode=$form.gen_actionWithPageMode}
                        </table>
                    </td>
                </tr>
            </table>
            {/if}

            <br>

            {if $form.gen_buttonLabel != ''}
            <input type="submit" id="submit1" value="{$form.gen_buttonLabel|escape}">
            {/if}
            {if $form.gen_reportEdit == 'true'}
            <span style='cursor:pointer'> {* style は aタグに指定するとcluetipにより上書きされてしまうので、ここに指定 *}
            <a id='gen_reportEditButton_{$item.reportEdit|escape}' class="gen_chiphelp" rel="p.helptext_reportEdit" title="{gen_tr}_g("帳票テンプレート画面へ"){/gen_tr}" onclick="gen.reportEdit.showReportEditDialog('{$item.reportEdit|escape}')"><img class='imgContainer sprite-wrench' src='img/space.gif' border='0'/></a>
            </span>
            {/if}
            {if $form.gen_reportEdit == 'true'}
                <p class='helptext_reportEdit' style='display:none;'>{gen_tr}_g("帳票を自由にカスタマイズすることができます。"){/gen_tr}</p>
            {/if}
            </form>
        </td>
    </tr>
</table>
<br>
<span style='font-size:12px'>
{$form.gen_message_noEscape}
</span>
</div>

</div>
{include file="common_footer.tpl"}
