{include file="mobile/common_header.tpl"}

{* 棚卸登録は高い操作性が求められるため、クライアントサイドチェックを行う *}
<script>
    {literal}
    function gen_check_and_submit() {
        if (!gen_isDate($('#mobile_stockinput_inventory_date').val())) {
            alert('{/literal}{gen_tr}_g('日付が正しくありません。'){/gen_tr}{literal}');
            return;
        }
        if ($('#mobile_stockinput_item_code').val()=='') {
            alert('{/literal}{gen_tr}_g('品目を入力してください。'){/gen_tr}{literal}');
            return;
        }
        {/literal}{* 空文字チェックはサーバー側で行われない（正常登録されたように見えてしまう）*}{literal}
        if (!gen_isNumeric($('#mobile_stockinput_inventory_quantity').val())) {  
            alert('{/literal}{gen_tr}_g('実在庫が正しくありません。'){/gen_tr}{literal}');
            return;
        }
        $('#mobile_stockinput_frame').get(0).submit();
    }

    function gen_isNumeric(val) {
        if (val===null || val==undefined) return false;
        return val.toString().match(/^[-]?([1-9]|0\.|0$)[0-9]*(\.[0-9]+)?$/);
    }

    function gen_isDate(str) {
        if (!str) return false;

        var arr = str.split('-');
        if (arr.length!=3) {
            arr = str.split('/');
            if (arr.length!=3) return false;
        }

        if (isNaN(arr[0]) || isNaN(arr[1]) || isNaN(arr[2])) return false;
        if (arr[0].length!=4 || arr[1].length>2 || arr[2].length>2) return false;

        if (arr[0] < 1900 || arr[0] > 3000) return false;
        if (arr[1] < 1 || arr[1] > 12) return false;

        var maxDay = 31;
        if (arr[1] == 2) {
            if (((arr[0]%4)==0 && (arr[0]%100)!=0) || (arr[0]%400)==0) {
                maxDay = 29;
            } else {
                maxDay = 28;
            }
        }
        if (arr[1] == 4 || arr[1] == 6 || arr[1] == 9 || arr[1] == 11)
            maxDay = 30;
        if (arr[2] < 1 || arr[2] > maxDay)
            return false;

        return true;
    }    
    {/literal}
</script>

{gen_error errorList=$errorList}
{if $form.gen_afterEntryMessage!=''}<span style="color:blue">{$form.gen_afterEntryMessage|escape}</span><br><br>{/if}

<form action="index.php?action=Mobile_StockInput_Entry" id="mobile_stockinput_frame" method="post">
<input type='hidden' name='gen_page_request_id' value='{$form.gen_page_request_id|escape}'>
<table>
    <tr><td>{gen_tr}_g("日付"){/gen_tr}</td><td><input type="date" id="mobile_stockinput_inventory_date" name="inventory_date" value="{$form.inventory_date|escape}"/></td></tr>
    <tr><td>{gen_tr}_g("ロケ"){/gen_tr}</td><td>
        <select id="mobile_stockinput_location_id" name="location_id">
        {html_options options=$form.locationOptions selected=$form.location_id|escape}
        </select>
    </td></tr>

    <tr><td>{gen_tr}_g("品目コード"){/gen_tr}</td> {*このclassを追加することでautocomplete対象になる*}
        <td><input type="text" class="gen_autocomplete ac_item" id="mobile_stockinput_item_code" name="item_code" value="{$form.item_code|escape}" autofocus />
    </td></tr>
<!-- 品目を選択すると数値がアップデートされる部分が未実装
    <tr><td>{gen_tr}_g("理論在庫"){/gen_tr}</td><td>{$form.logical_stock_quantity|number_format|escape}</td></tr>
-->
    <tr><td>{gen_tr}_g("実在庫"){/gen_tr}</td><td><input type="number" id="mobile_stockinput_inventory_quantity" name="inventory_quantity" value="{$form.inventory_quantity|escape}"  /></td></tr>
<!-- ヘッダから登録ボタンまで1画面で表示されるようにするため、とりあえずコメントアウト
    <tr><td>{gen_tr}_g("棚卸備考"){/gen_tr}</td><td><input type="text" id="mobile_stockinput_remarks" name="remarks" value="{$form.remarks|escape}"  /></td></tr>
-->
</table>
<input type="button" value="{gen_tr}_g("登録"){/gen_tr}" data-theme="b" onclick="gen_check_and_submit()"/>
</form>

{include file="mobile/common_footer.tpl"}
