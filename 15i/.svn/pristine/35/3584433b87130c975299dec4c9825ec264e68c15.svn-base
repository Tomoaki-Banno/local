{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{literal}

    $(function() {
        document.getElementById('backupStart').style.display='';
        document.getElementById('number{/literal}{$form.defaultNumber|escape}{literal}').checked = true;
        rowSelect({/literal}{$form.defaultNumber|escape}{literal});
    });

    {/literal}checkNo = {$form.defaultNumber|escape};{literal}
    function rowSelect(no) {
        document.getElementById("row_" + checkNo).style.backgroundColor="#ffffff";
        document.getElementById("row_" + no).style.backgroundColor="#66ffff";
        checkNo = no;
    }

    /*********************** バックアップ処理実行 ***********************/

    function backup_start() {
        var res = confirm('{/literal}{gen_tr}_g("バックアップ処理を実行します。既存のバックアップは上書きされます。他のユーザーがシステムを使用していないことを確認してください。処理が終了するまで、パソコンに手を触れないでください。処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        for (i = 0; i < document.form1.number.length; i++) {
           if (document.form1.number[i].checked) {
               number = document.form1.number[i].value;
               break;
           }
        }

        gen.ajax.connect("Config_Backup_AjaxBackup", {backup_number: number, remarks: document.form1.remarks.value} ,
            function(j) {
                var msg = "";
                if (j.result == 'success') {
                    msg = '{/literal}{gen_tr}_g("バックアップ処理が正常に終了しました。"){/gen_tr}{literal}';
                    alert(msg);
                    location.reload();
                } else {
                    msg = '{/literal}{gen_tr}_g("バックアップ処理でエラーが発生しました。"){/gen_tr}{literal}';
                    alert(msg);
                    document.getElementById('msg').innerHTML = "<font color='red'>" + msg + "</font>";
                    document.getElementById('backupStart').style.display='';
                }
            });

        document.getElementById('backupStart').style.display='none';
        document.getElementById('msg').innerHTML = '<span>{/literal}{gen_tr}_g("バックアップ処理実行中。パソコンに手を触れずにお待ちください・・"){/gen_tr}{literal}</span>';
    }

    function backup_delete(number) {
        document.getElementById('number' + number).checked = true;
        rowSelect(number);
        var rem = '';
        {/literal}
        {foreach item=info from=$form.backupFileInfo}
        if (number == {$info.number|escape}) rem = '{$info.remarks|escape}';
        {/foreach}
        {literal}
        if (rem !='') rem = '（' + rem + '）';
        var res = confirm('No.' + number + ' ' + rem + '{/literal}{gen_tr}_g("のバックアップデータを削除します。この処理を元に戻すことはできません。処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        gen.ajax.connect("Config_Backup_AjaxBackupDelete", {backup_number: number} ,
            function(j) {
                var msg = "";
                if (j.result == 'success') {
                    msg = '{/literal}{gen_tr}_g("バックアップが正常に削除されました。"){/gen_tr}{literal}';
                    alert(msg);
                    location.reload();
                } else {
                    msg = '{/literal}{gen_tr}_g("バックアップデータの削除に失敗しました。。"){/gen_tr}{literal}'
                    alert(msg);
                    document.getElementById('msg').innerHTML = "<font color='red'>" + msg + "</font>";
                    document.getElementById('backupStart').style.display='';
                }
            });

        document.getElementById('backupStart').style.display='none';
        document.getElementById('msg').innerHTML = '{/literal}{gen_tr}_g("バックアップデータの削除中・・・"){/gen_tr}{literal}';
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

<div style='height:10px'></div>

<table>
    <tr valign="top">
        <td align="center">
            <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
        </td>
    </tr>
</table>

<div style='height:30px'></div>

<form name="form1" method="POST">

<table border="1" cellspacing="0" cellpadding="2" style="border-style: solid; border-color: #696969; border-collapse: collapse;">
<tr class="dataListTitle" style="background:#ccc">
    <th width="50px">{gen_tr}_g("選択"){/gen_tr}</th>
    <th width="50px">{gen_tr}_g("番号"){/gen_tr}</th>
    <th width="180px">{gen_tr}_g("バックアップ日時"){/gen_tr}</th>
    <th width="100px">{gen_tr}_g("サイズ"){/gen_tr}</th>
    <th width="100px">{gen_tr}_g("実行ユーザー"){/gen_tr}</th>
    <th width="300px">{gen_tr}_g("バックアップ メモ"){/gen_tr}</th>
    <th width="50px">{gen_tr}_g("削除"){/gen_tr}</th>
</tr>

{foreach item=info from=$form.backupFileInfo}
<tr height="25px" id="row_{$info.number}">
    <td align="center"><input type="radio" id="number{$info.number|escape}" name="number" value="{$info.number|escape}" onclick="rowSelect({$info.number|escape})"></td>
    <td align="center">{$info.number|escape}&nbsp;</td>
    <td align="center">{$info.date|escape}&nbsp;</td>
    <td align="center">{$info.size|escape}&nbsp;</td>
    <td align="center">{$info.user|escape}&nbsp;</td>
    <td align="left">{$info.remarks|escape}&nbsp;</td>
    <td align="center">{if $info.date!=''}<a href='javascript:backup_delete({$info.number|escape})'>{gen_tr}_g("削除"){/gen_tr}</a>{/if}&nbsp;</td>
</tr>
{/foreach}
</table>

<div style="height:20px"></div>
<table border="0">
    <tr align="center">
        <td>
            <div id="msg"></div>
        </td>
    </tr>
    <tr align="center">
        <td id='backupStart'>
            <table border="0">
                <tr>
                    <td align="left">
                    {gen_tr}_g("上のリストの中から、バックアップデータを保存する場所（バックアップ番号）を選択して、実行ボタンを押してください。"){/gen_tr}<br>
                    {gen_tr}_g("※指定した番号にすでにバックアップデータが存在する場合、上書きされます。"){/gen_tr}<br>
                    {gen_tr}_g("※バックアップされるデータの中には、画像・帳票テンプレート・レコードやトークボードの添付ファイルは含まれていません。"){/gen_tr}<br><br>
                    </td>
                </tr>
                <tr>
                    <td align="center">
                    {gen_tr}_g("バックアップ メモ"){/gen_tr}：<input type="text" id="remarks" style="width:300px">
                    <input type="button" class="gen-button" value="{gen_tr}_g("バックアップ実行"){/gen_tr}" onClick="backup_start()"><br><br>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</form>

</div>
{include file="common_footer.tpl"}

