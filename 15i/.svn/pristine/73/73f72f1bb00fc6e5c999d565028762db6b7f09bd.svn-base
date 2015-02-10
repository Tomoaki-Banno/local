{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{literal}

    /*********************** 処理実行 ***********************/

    function doLock(cat) {
        if (cat == 1) {
            var year = $('[name=lock_Year]').val();
            var month = $('[name=lock_Month]').val();
            var d = new Date();
            var ym = d.getFullYear() * 100 + (d.getMonth() + 1);
            var lock = year + month;
            if (ym <= lock) {
                alert("{/literal}{gen_tr}_g("過去の年月を指定してください。"){/gen_tr}{literal}");
                return;
            }

            var res = confirm('{/literal}{gen_tr}_g("ロック処理を実行します。他のユーザーがシステムを使用していないことを確認してください。処理を実行してよろしいですか？"){/gen_tr}{literal}');
            if (res != true) {
                alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
                return;
            }

            $('.lockButton').attr('disabled', 'disabled');
            gen.ajax.connect('{/literal}{$form.ajaxAction1|escape}{literal}', {lock_year : year, lock_month : month},
                function(j) {
                    if (j.status=='success') {
                        alert('{/literal}{gen_tr}_g("データロック処理が終了しました。"){/gen_tr}{literal}');
                    } else {
                        alert('{/literal}{gen_tr}_g("処理に失敗しました。"){/gen_tr}{literal}');
                    }
                    location.reload();
                });
        } else {
            var unlock1 = $("#unlock_object_1").is(':checked');
            var unlock2 = $("#unlock_object_2").is(':checked');
            var unlock3 = $("#unlock_object_3").is(':checked');
            var unlock4 = $("#unlock_object_4").is(':checked');

            gen.ajax.connect('{/literal}{$form.ajaxAction2|escape}{literal}', {unlock_object_1 : unlock1, unlock_object_2 : unlock2, unlock_object_3 : unlock3, unlock_object_4 : unlock4},
                function(j) {
                    if (j.status=='success') {
                        alert('{/literal}{gen_tr}_g("データロック対象の変更が終了しました。"){/gen_tr}{literal}');
                    } else {
                        alert('{/literal}{gen_tr}_g("処理に失敗しました。"){/gen_tr}{literal}');
                    }
                    location.reload();
                });
        }
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
{* 2010i rev.20110501 で追加。CSRF対策。JSで読み取ってPOSTしている *}
{ gen_reload }
<table border="0">
    <tr align="center">
        <td>
            <div id="msg">
            {gen_tr}_g("この処理を行うと、指定した年月およびそれ以前のデータがロックされます。（表示はできますが、登録や更新ができなくなります）。"){/gen_tr}<br>
            {gen_tr}_g("過去のデータを誤操作により書き換えてしまうことがないよう保護したい場合に、この処理を実行してください。"){/gen_tr}<br><br>
            {$form.addMsg_noEscape}<br><br>
            <table border="0">
                <tr>
                    <td width="400" style="padding: 20px; border: solid 1px #999999; background-color: {$form.bgColor1|escape}" valign="middle" align="center">
                        {if $form.titleMsg1 != ''}
                        <font color='#000000'><b>{$form.titleMsg1|escape}</b></font><br><br>
                        {/if}
                        <font color='blue'>{$form.lockMsg1|escape}</font><br><br>
                        {$form.target1|escape}<br><br>
                        {html_select_date_j prefix="lock_" time=$form.default_date start_year="-5" end_year="+0"
                            month_format="%m" field_order="YM"}&nbsp;{gen_tr}_g("およびそれ以前のデータをロックする"){/gen_tr}<br><br>
                        <input type="button" class="gen-button" value="{gen_tr}_g("データロック処理を実行"){/gen_tr}" onClick="doLock(1)">
                    </td>
                    {if $form.gen_objectLock != ''}
                    <td width="10"></td><td>
                    <td width="400" style="padding: 20px; border: solid 1px #999999; background-color: {$form.bgColor2|escape}" valign="middle" align="center">
                        <font color='#000000'><b>{$form.titleMsg2|escape}</b></font><br><br>
                        <font color='blue'>{$form.lockMsg2|escape}</font><br><br>
                        {$form.target2_noEscape}<br><br>
                        <table border="0">
                            <tr>
                                <td><input type='checkbox' id='unlock_object_1' name='unlock_object_1' value='true' {$form.checked1|escape}></td>
                                <td>{gen_tr}_g("受注登録"){/gen_tr}</td>
                                <td width="15"></td>
                                <td><input type='checkbox' id='unlock_object_2' name='unlock_object_2' value='true' {$form.checked2|escape}></td>
                                <td>{gen_tr}_g("製造指示登録"){/gen_tr}</td>
                            </tr>
                            <tr>
                                <td><input type='checkbox' id='unlock_object_3' name='unlock_object_3' value='true' {$form.checked3|escape}></td>
                                <td>{gen_tr}_g("注文登録"){/gen_tr}</td>
                                <td></td>
                                <td><input type='checkbox' id='unlock_object_4' name='unlock_object_4' value='true' {$form.checked4|escape}></td>
                                <td>{gen_tr}_g("外製指示登録"){/gen_tr}</td>
                            </tr>
                        </table><br>
                        <input type="button" class="gen-button" value="{gen_tr}_g("データロック対象を変更"){/gen_tr}" onClick="doLock(2)">
                    </td>
                    {/if}
                </tr>
            </table>
            </div>
        </td>
    </tr>
</table>
</form>

</div>
{include file="common_footer.tpl"}

