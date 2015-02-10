{include file="common_header.tpl"}

<center>

<table width="800">
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td width="100"></td>
        <td width="600" align="center">●{gen_tr}_g("入出庫明細と在庫推移"){/gen_tr}</td>
        <td width="100">
        </td>

    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td height="10" colspan="3" align="center"><a href="index.php?action=Stock_Stocklist_List&gen_restore_search_condition=true">在庫リストに戻る</a></td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
</table>

{gen_error errorList=$errorList}

{if $errorList == ''}
    <table border="1" cellpadding="1" cellspacing="0">
        <tr>
            <td bgcolor='#ffcc99' width=100>{gen_tr}_g("品目コード"){/gen_tr}</td><td width=100>{$form.gen_itemCode|escape}</td>
            <td bgcolor='#ffcc99' width=100>{gen_tr}_g("品目名"){/gen_tr}</td><td width=100>{$form.gen_itemName|escape}</td>
        </tr>
        <tr>
            <td bgcolor='#ffcc99'>{gen_tr}_g("製番"){/gen_tr}</td><td>{$form.gen_seiban|escape}</td>
            <td bgcolor='#ffcc99'>{gen_tr}_g("ロケーション"){/gen_tr}</td><td>{$form.gen_location|escape}&nbsp;</td>
        </tr>
        <!--
        <tr>
            <td bgcolor='#ffcc99'>{gen_tr}_g("ロット"){/gen_tr}</td><td>{$form.gen_lot|escape}</td>
            <td bgcolor='#ffcc99'>&nbsp;</td><td>&nbsp;</td>
        </tr>
        -->
        <tr>
            <td bgcolor='#ffcc99'>{gen_tr}_g("管理区分"){/gen_tr}</td><td>{$form.gen_orderClass|escape}</td>
            <td bgcolor='#ffcc99'>{gen_tr}_g("安全在庫数"){/gen_tr}</td><td>{$form.gen_safetyStock|escape}</td>
        </tr>
        </tr>
    </table>
    <BR><BR>

<BR><BR>

    <table border="1" cellpadding="1" cellspacing="0">
    <tr bgcolor="#99ffff">
        <td width="90" align="center">{gen_tr}_g("日付"){/gen_tr}</td>
        <td width="70" align="center">{gen_tr}_g("種別"){/gen_tr}</td>
        <td width="100" align="center">{gen_tr}_g("ロケーション"){/gen_tr}</td>
        <!-- <td width="70" align="center">{gen_tr}_g("ロット"){/gen_tr}</td> -->
        <td width="70" align="center">{gen_tr}_g("入庫数"){/gen_tr}</td>
        <td width="70" align="center">{gen_tr}_g("出庫数"){/gen_tr}</td>
        <td width="70" align="center">{gen_tr}_g("出庫予定数"){/gen_tr}</td>
        <td width="70" align="center">{gen_tr}_g("在庫数"){/gen_tr}</td>
        <td width="70" align="center">{gen_tr}_g("有効在庫数"){/gen_tr}</td>
        <td width="150" align="center">{gen_tr}_g("コメント"){/gen_tr}</td>
        <td width="100" align="center">{gen_tr}_g("登録ユーザー"){/gen_tr}</td>
    </tr>
    <tr bgcolor="#cccccc">
        <td colspan="7" align="center">{gen_tr}_g("前在庫（表示期間開始日時点の在庫数）"){/gen_tr}</td>
        <td align="right">{$form.gen_lastMonthStockQuantity|escape}</td>
        <td align="right">{$form.gen_lastMonthStockQuantity|escape}</td>
        <td colspan="2" align="center">&nbsp;</td>
    </tr>
    {foreach item=result from=$form.gen_data}
    <tr>
        <td align="center">{$result.date|escape}&nbsp;</td>
        <td align="center">{$result.class|escape}&nbsp;</td>
        <td align="center">{$result.location_name|escape}&nbsp;</td>
        <!-- <td align="center">{$result.lot_no|escape}&nbsp;</td> -->
        <td align="right">{$result.in_quantity|escape}</td>
        <td align="right">{$result.out_quantity|escape}</td>
        <td align="right">{$result.logical_out_quantity|escape}</td>
        <td align="right" bgcolor="#ffcc99">{$result.stock|escape}</td>
        <td align="right" bgcolor="#ffcc99">{$result.logical_stock|escape}</td>
        <td align="center">{$result.remarks|escape}&nbsp;</td>
        <td align="center">{$result.user|escape}&nbsp;</td>
    </tr>
    {/foreach}
    </table>

{/if}

</center>

{include file="common_footer.tpl"}


