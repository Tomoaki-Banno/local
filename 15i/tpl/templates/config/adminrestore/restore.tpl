{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">

{if $form.gen_restore_done}
    // リストアによりセッションが無効になるため強制ログアウトする
    alert('{gen_tr}_g("読み込み処理が正常に終了しました。自動的にログアウトします。再度ログイン処理を行ってください。"){/gen_tr}');
    location.href='index.php?action=Logout';
{/if}

{if $form.gen_restore_error}
    alert('{gen_tr}_g("読み込み処理でエラーが発生しました。バックアップファイルが正しくなかった可能性があります。"){/gen_tr}');
{/if}

{if $form.gen_size_error}
    alert('{gen_tr}_g("ファイルのサイズが大きすぎて読み込めませんでした。ファイルが正しいことを確認してください。"){/gen_tr}' +
    '{gen_tr}_g("ファイルが正しいのに読み込めない場合、システム管理者に相談してください。"){/gen_tr}\n' +
    '{gen_tr}_g("システム管理者のための情報：フロントコントローラの設定および php.iniのupload_max_filesize と post_max_sizeを確認してください。"){/gen_tr}');
{/if}

{literal}

    function restore_start() {
        var res = confirm('{/literal}{gen_tr}_g("バックアップデータの読み込み処理を実行します。他のユーザーがシステムを使用していないことを確認してください。"){/gen_tr}{literal}' +
            '{/literal}{gen_tr}_g("この処理を取り消すことはできませんので、リストア前にバックアップを行っておくことを強くお勧めします。処理が終了するまで、パソコンに手を触れずにお待ちください。処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        document.getElementById('restore_section').style.display = 'none';
        document.getElementById('restore_section2').style.display = '';
        document.form1.submit();
    }

{/literal}
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

<table width="800">
    <tr style="height: 7px"><td></td></tr>
    <tr><td align="center">
        <div class="listTitle">
            {gen_tr}_g("バックアップデータ　読み込み処理"){/gen_tr}
        </div>
    </td></tr>
    <tr style="height: 30px"><td></td></tr>
</table>

<table border="0">
    <tr align="center">
        <td>
            <div id="msg">{$form.gen_msg_noEscape}</div>
        </td>
    </tr>
    <tr align="center">
        <form enctype="multipart/form-data" name="form1" method="POST" action="index.php?action=Config_AdminRestore_Restore">
        <td id="restore_section">
            {gen_tr}_g("読み込むデータを指定してください"){/gen_tr}：<BR><BR>
            <INPUT TYPE="hidden" NAME="MAX_FILE_SIZE" SIZE="{$form.gen_max_upload_file_size|escape}">
            <input TYPE="file" id="restoreFile" NAME="restoreFile" SIZE="50"><BR><BR>
            <input type="button" class="gen-button" id="restoreButton" value="{gen_tr}_g("読み込み実行"){/gen_tr}" onClick="restore_start()">
        </td>
        <td id="restore_section2" style="display:none">
            {gen_tr}_g("読み込み処理中。パソコンに手を触れずにお待ちください..."){/gen_tr}
        </td>
        </form>
    </tr>
    <tr style="height: 30px"><td></td></tr>
</table>

</div>
{include file="common_footer.tpl"}

