{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{literal}

    /*********************** データ削除処理実行 ***********************/

    function delete_start() {
        var year = $('#delete_Year').val();
        var month = $('#delete_Month').val();

        var res = confirm('{/literal}{gen_tr}_g("データ削除処理を実行します。この操作を取り消すことはできません。"){/gen_tr}{literal}' +
            '{/literal}{gen_tr}_g("指定範囲内に受入が完了していない注文データなどがある場合は行なわないでください。"){/gen_tr}{literal}' +
            '{/literal}{gen_tr}_g("他のユーザーがシステムを使用していないことを確認してください。処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        var p = {
            delete_date : year + '-' + month + '-01',
            gen_page_request_id : $('[name=gen_page_request_id]').val()
        };
        gen.ajax.connect('Config_DataDelete_AjaxDelete', p, 
            function(j){
                if (j.status == 'success') {
                    // alertよりlocation.hrefの方を先に。このほうが見栄えがよい
                    location.href = 'index.php?action=Config_DataDelete_List';
                    alert('{/literal}{gen_tr}_g("データを削除しました。"){/gen_tr}{literal}');
                } else {
                    alert('{/literal}{gen_tr}_g("データ削除処理でエラーが発生しました。"){/gen_tr}{literal}');
                    document.getElementById('msg').innerHTML = '<font color=red>{/literal}{gen_tr}_g("データ削除処理でエラーが発生しました。"){/gen_tr}{literal}</font><br><br>';
                }
            });

        // 画面
        document.getElementById('msg').innerHTML = '<br><br><br>{/literal}{gen_tr}_g("データ削除処理実行中・・・"){/gen_tr}{literal}<br><br><br><br>';
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
            {gen_tr}_g("下記のデータが削除されます。"){/gen_tr}<br><br>
            {gen_tr}_g("受注・納品・見積・入金・製造指示・実績・注文・受入・外製・外製受入"){/gen_tr}<br>
            {gen_tr}_g("支払・入出庫・ロケーション間移動登録・製番引当登録・棚卸登録・データ更新ログ"){/gen_tr}<br><br>
            {gen_tr}_g("この処理を行うことによりシステムの速度が向上します。ただし削除したデータは元に戻せません。"){/gen_tr}<br>
            {gen_tr}_g("実行前にシステム管理者に連絡し、データのバックアップをとっておくことをおすすめします。"){/gen_tr}<br><br>
            <table border="1" cellspacing="0" cellpadding="2" style="border-style: solid; border-color: #999999; border-collapse: collapse;">
                <tr>
                    <td width='400px' style="padding: 20px; background-color: {$form.bgColor|escape}" halign="middle" align="center">
                    <font color='blue'>{gen_tr}_g("削除するデータの範囲を指定してください。"){/gen_tr}</font><br><br>
                    {html_select_date_j prefix="delete_" time=$form.startup_date_year start_year="-5" end_year="+0"
                        month_format="%m" field_order="YM"}&nbsp;{gen_tr}_g("より前のデータを削除する"){/gen_tr}<br><br>
                    <input type="button" class="gen-button" value="{gen_tr}_g("データ削除"){/gen_tr}" onClick="delete_start();">
                    </td>
                </tr>
            </table>
            </div>
        </td>
    </tr>
</table>

</form>

</div>
{include file="common_footer.tpl"}