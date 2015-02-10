{include file="common_header.tpl"}
<!-- このコメントはエクセルで使用しているので変えないこと：　Permission Error Flag for Excel -->

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
    <tr style="height:100px"><td></td></tr>
    <tr>
        <td align="center">
            <div class="listTitle">
                {gen_tr}_g("メール送信エラー"){/gen_tr}
            </div>
        </td>
    </tr>
    <tr style="height:30px"><td></td></tr>
    <tr>
        <td align="center">
            {gen_tr}_g("仮登録メール送信に失敗しました。本サーバーではメールシステムが有効でない可能性があります。"){/gen_tr}<br><br>
            {gen_tr}_g("システム管理者にご相談ください。"){/gen_tr}
        </td>
    </tr>
    <tr style="height:130px"><td></td></tr>
</table>

<!-- 「戻る」は「警告 : ページの有効期限切れ」が出てしまう -->
<!-- <a href="javascript:history.back()">戻る</a> -->

</div>
{include file="common_footer.tpl"}