{include file="mobile/common_header.tpl"}

{if $form.result}
    <span style="font-size:14px">{$form.itemParam->item_code|escape}</span><br>
    <span style="font-size:14px">{$form.itemParam->item_name|escape}</span><br><br>

    <table border=1 class='formtable1'>
    <tr>
        <td>{gen_tr}_g("日付"){/gen_tr}</td>
        <td>{gen_tr}_g("入庫"){/gen_tr}</td>
        <td>{gen_tr}_g("出庫"){/gen_tr}</td>
        <td>{gen_tr}_g("入予"){/gen_tr}</td>
        <td>{gen_tr}_g("出予"){/gen_tr}</td>
        <td>{gen_tr}_g("理論"){/gen_tr}</td>
        <td>{gen_tr}_g("有効"){/gen_tr}</td>
    </tr>
    {foreach from=$form.result item=row value=value name=result}
    <tr style='height:30px' onclick="javascript:alert('{$row.description|escape}')">
        <td>{$row.date|escape}</td>
        <td align="right">{$row.in_qty|number_format}</td>
        <td align="right">{$row.out_qty|number_format}</td>
        <td align="right">{$row.in_plan_qty|number_format}</td>
        <td align="right">{$row.out_plan_qty|number_format}</td>
        <td align="right">{$row.logical_stock_quantity|number_format}</td>
        <td align="right">{$row.available_stock_quantity|number_format}</td>
    </tr>
    {/foreach}
    </table>
{else}
    <div data-role="content">{gen_tr}_g("データがありません。"){/gen_tr}</div>
{/if}

{include file="mobile/common_footer.tpl"}
