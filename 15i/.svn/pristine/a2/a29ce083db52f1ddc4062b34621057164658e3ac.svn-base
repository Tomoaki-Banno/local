{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{literal}

    $(function() {
        document.getElementById('resotreStart').style.display='';
        document.getElementById('number{/literal}{$form.defaultNumber|escape}{literal}').checked = true;
        rowSelect({/literal}{$form.defaultNumber|escape}{literal});
    });

    {/literal}checkNo = {$form.defaultNumber|escape};{literal}
    function rowSelect(no) {
        document.getElementById("row_" + checkNo).style.backgroundColor="#ffffff";
        document.getElementById("row_" + no).style.backgroundColor="#ffcc99";
        checkNo = no;
    }

    /*********************** リストア処理実行 ***********************/

    function resotre_start() {
        {/literal}
        {foreach item=info from=$form.restoreFileInfo}
        if (checkNo == {$info.number|escape}) rem = '{$info.remarks|escape}';
        {/foreach}
        {literal}
        rem = rem.replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/&amp;/g,"&").replace(/&quot;/g,"\"").replace(/&apos;/g,"'");
        if (rem !='') rem = '（' + rem + '）';
        var res = confirm('No.' + checkNo + ' ' + rem + '{/literal}{gen_tr}_g("のバックアップデータの読み込み処理を実行します。他のユーザーがシステムを使用していないことを確認してください。処理が終了するまで、パソコンに手を触れないでください。処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        gen.ajax.connect("Config_Restore_AjaxRestore", {backup_number: checkNo} ,
            function(j) {
                var msg = "";
                if (j.result == 'success') {
                    msg = '{/literal}{gen_tr}_g("読み込み処理が正常に終了しました。自動的にログアウトします。再度ログイン処理を行ってください。"){/gen_tr}{literal}';
                    alert(msg);
                    location.href='index.php?action=Logout';
                } else if (j.result == 'fileMissing') {
                    msg = '{/literal}{gen_tr}_g("指定されたバックアップファイルが存在しませんでした。他のユーザーによって削除された可能性があります。"){/gen_tr}{literal}';
                    alert(msg);
                } else {
                    msg = '{/literal}{gen_tr}_g("読み込み処理でエラーが発生しました。"){/gen_tr}{literal}';
                    alert(msg);
                }
                document.getElementById('resotreStart').style.display='';
                document.getElementById('msg').innerHTML = msg;
            });

        document.getElementById('resotreStart').style.display='none';
        document.getElementById('msg').innerHTML = '<span>{/literal}{gen_tr}_g("読み込み処理実行中。パソコンに手を触れずにお待ちください・・・"){/gen_tr}{literal}</span>';
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
<tr class="dataListTitle" style="background: #ccc">
    <th width="50px">{gen_tr}_g("選択"){/gen_tr}</th>
    <th width="50px">{gen_tr}_g("番号"){/gen_tr}</th>
    <th width="180px">{gen_tr}_g("バックアップ日時"){/gen_tr}</th>
    <th width="100px">{gen_tr}_g("サイズ"){/gen_tr}</th>
    <th width="100px">{gen_tr}_g("実行ユーザー"){/gen_tr}</th>
    <th width="300px">{gen_tr}_g("バックアップ メモ"){/gen_tr}</th>
</tr>

{foreach item=info from=$form.restoreFileInfo}
<tr height="25px" id="row_{$info.number|escape}">
    <td align="center">{if $info.date !=''}<input type="radio" id="number{$info.number|escape}" name="number" value="{$info.number|escape}" onclick="rowSelect({$info.number|escape})">{/if}&nbsp;</td>
    <td align="center">{$info.number|escape}&nbsp;</td>
    <td align="center">{$info.date|escape}&nbsp;</td>
    <td align="center">{$info.size|escape}&nbsp;</td>
    <td align="center">{$info.user|escape}&nbsp;</td>
    <td align="left">{$info.remarks|escape}&nbsp;</td>
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
        <td id='resotreStart'>
        {if $form.fileExist=='true'}
        {gen_tr}_g("上のリストの中から読み込むバックアップデータを選択して、実行ボタンを押してください。"){/gen_tr}<br><br>
            <input type="button" class="gen-button" value="{gen_tr}_g("読み込み実行"){/gen_tr}" onClick="resotre_start()"><br><br>
        {else}
        {gen_tr}_g("バックアップデータが存在しません。"){/gen_tr}
        {/if}
        </td>
    </tr>
</table>

</form>

</div>
{include file="common_footer.tpl"}

