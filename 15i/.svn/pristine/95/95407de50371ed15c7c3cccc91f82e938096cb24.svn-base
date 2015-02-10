{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{literal}
{/literal}{if $form.result == 'fail'}{literal}
$(function(){
    alert('{/literal}{gen_tr}_g("バックアップ処理でエラーが発生しました。"){/gen_tr}{literal}');
});     
{/literal}{/if}{literal}

    /*********************** バックアップ処理実行 ***********************/

    function backup_start() {
        var res = confirm('{/literal}{gen_tr}_g("バックアップ処理を実行します。他のユーザーがシステムを使用していないことを確認してください。処理が終了するまで、パソコンに手を触れないでください。処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }
        location.href = 'index.php?action=Config_AdminBackup_Backup&doBackup';    
    }
{/literal}
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

<table width="800">
    <tr style="height: 7px"><td></td></tr>
    <tr><td align="center">
        <div class="listTitle">
            {gen_tr}_g("バックアップ処理（admin専用）"){/gen_tr}
        </div>
    </td></tr>
    <tr style="height: 30px"><td></td></tr>
</table>

<form name="form1" method="POST">
<table border="0">
    <tr align="center">
        <td>
            <div id="msg" style="white-space: nowrap;"></div>
        </td>
    </tr>
    <tr align="center">
        <td id='backupStart'>
            <input type="button" class="gen-button" value="{gen_tr}_g("バックアップ実行"){/gen_tr}" onClick="backup_start()">
        </td>
    </tr>
    <tr style="height: 30px"><td></td></tr>
</table>
</form>

</div>
{include file="common_footer.tpl"}

