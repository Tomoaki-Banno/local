{include file="mobile/common_header.tpl"}

<script>
    {literal}
    function gen_check_and_submit() {
        if ($('#mobile_received_customer_code').val()=='') {
            alert('{/literal}{gen_tr}_g('発注先を入力してください。'){/gen_tr}{literal}');
            return;
        }
        $('#mobile_received_frame').get(0).submit();
    }

    function gen_isNumeric(val) {
        if (val===null || val==undefined) return false;
        return val.toString().match(/^[-]?([1-9]|0\.|0$)[0-9]*(\.[0-9]+)?$/);
    }
    {/literal}
</script>

{gen_error errorList=$errorList}
{if $form.gen_afterEntryMessage!=''}<span style="color:blue">{$form.gen_afterEntryMessage|escape}</span><br><br>{/if}

<form action="index.php?action=Mobile_Received_Entry" id="mobile_received_frame" method="post">
<input type='hidden' name='gen_page_request_id' value='{$form.gen_page_request_id|escape}'>
<table>
    <tr><td>{gen_tr}_g("部門"){/gen_tr}</td><td>
        <select id="mobile_received_section_id" name="section_id">
        {html_options options=$form.sectionOptions selected=$form.section_id|escape}
        </select>
    </td></tr>
    
    <tr><td>{gen_tr}_g("担当者"){/gen_tr}</td><td>
        <select id="mobile_received_worker_id" name="worker_id">
        {html_options options=$form.workerOptions selected=$form.worker_id|escape}
        </select>
    </td></tr>

    <tr><td>{gen_tr}_g("得意先コード"){/gen_tr}</td>
        <td><input type="text" class="gen_autocomplete ac_received_customer" id="mobile_received_customer_no" name="customer_no" value="{$form.customer_no|escape}" autofocus /></td>
    </tr>
    
    {foreach from=$form.gen_columnArray key="key" item="value" name="customColumnLoop"}
    <tr><td>{$value.label}</td>
        {assign var="field" value=$value.field}
        <td>
            {if $value.type=='select'}
            <select id="mobile_received_{$field|escape}" name="{$field|escape}">
            {html_options options=$value.options selected=$value.selected|escape}
            </select>
            {else}
            <input type="{$value.type|escape}" id="mobile_received_{$field|escape}" name="{$field|escape}" value="{$form.$field|escape}" />
            {/if}
        </td>
    </tr>
    {/foreach}
    
    {section name=detailLoop loop=8}
        {assign var="index" value=$smarty.section.detailLoop.iteration}
        <tr><td colspan='2' style='background-color:#ffcc99'>■{$index}{gen_tr}_g("行目"){/gen_tr}</td></tr>

        <tr><td>{gen_tr}_g("品目コード"){/gen_tr}</td>
            {assign var="valName" value="item_code_"|cat:$index}
            <td><input type="text" class="gen_autocomplete ac_received_item" id="mobile_received_item_code_{$index}" name="item_code_{$index}" value="{$form.$valName|escape}"/></td>
        </tr>
        
        <tr><td>{gen_tr}_g("数量"){/gen_tr}</td>
            {assign var="valName" value="received_quantity_"|cat:$index}
            <td><input type="number" id="mobile_received_received_quantity_{$index}" name="received_quantity_{$index}" value="{$form.$valName|escape}"  /></td>
        </tr>
        
        <tr><td>{gen_tr}_g("受注単価(省略可)"){/gen_tr}</td>
            {assign var="valName" value="product_price_"|cat:$index}
            <td><input type="number" id="mobile_received_product_price_{$index}" name="product_price_{$index}" value="{$form.$valName|escape}"  /></td>
        </tr>
     {/section}
</table>
<input type="button" value="{gen_tr}_g("登録"){/gen_tr}" data-theme="b" onclick="gen_check_and_submit()"/>
</form>

{include file="mobile/common_footer.tpl"}
