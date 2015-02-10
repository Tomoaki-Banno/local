{include file="mobile/common_header.tpl"}

<form action="index.php?action={$form.gen_listAction|escape}" method="post">
    <div data-role="collapsible" data-collapsed="true" data-theme="d"> 
	<h1>{gen_tr}_g("表示条件"){/gen_tr}</h1>
        <table>
            <tr>
            <td nowrap style="width:70px; font-size:14px">{gen_tr}_g("ソート"){/gen_tr}</td>
            <td>
                <table><tr>
                <td>
                    <select name="gen_search_orderby_desc" data-role="slider" data-mini="true">
                        <option value=""{if $form.gen_search_orderby_desc!='true'} selected{/if}>{gen_tr}_g("昇順"){/gen_tr}</option>
                        <option value="true"{if $form.gen_search_orderby_desc=='true'} selected{/if}>{gen_tr}_g("逆順"){/gen_tr}</option>
                    </select>
                </td>
                <td>
                    <select name="gen_search_orderby" data-mini="true">
                    {html_options options=$form.gen_orderbyOptions selected=$form.gen_search_orderby|escape}
                    </select>
                </td>
                </tr></table>
            </td>
            </tr>

        {foreach from=$form.gen_searchControlArray item=row value=value name=result}
            <tr>
                <td colspan="2" style="font-size:14px">{$row.label|escape}</td>
            </tr>
            <tr>
                <td colspan="2">
                    {if $row.type=="select"}
                        <select name="gen_search_{$row.field|escape}">
                        {html_options options=$row.options selected=$row.selected|escape}
                        </select>
                    {elseif $row.type=="calendar"}
                        <input type="date" name="gen_search_{$row.field|escape}" value="{$row.value|escape}">
                    {else}
                        <input type="text" name="gen_search_{$row.field|escape}" value="{$row.value|escape}">
                    {/if}
                </td>
            </tr>
        {/foreach}
            <tr><td colspan="2"><input type="submit" value="{gen_tr}_g("再表示"){/gen_tr}" data-theme="b" data-mini="true"></td></tr>
        </table>
    </div>
</form>
<br>

<div id='gen_error'>{gen_error errorList=$errorList}</div>
{$form.gen_message_noEscape|escape}{if $form.gen_message_noEscape!=''}<br>{/if}

{if $form.gen_data}

    {$form.gen_totalCount|escape}{gen_tr}_g("件"){/gen_tr}&nbsp;&nbsp;[page {$form.gen_showPage|escape} / {$form.gen_lastPage|escape}]

    {if $form.gen_sumColumnArray!=''}
        <br>
        {assign var="sumData" value=$form.gen_sumData}
        {foreach from=$form.gen_sumColumnArray item=column key=title name=sum}
            {$title|escape}{$sumData.$column|number_format}<br>
        {/foreach}
    {/if}

    <ul data-role="listview" data-inset="true"> {* data-inset="true" をはずすと外枠がなくなる *}
    {foreach from=$form.gen_data item=row name=result}
        <li style="font-weight:normal">
            {if $form.gen_linkAction!=''}
                {assign var="idField1" value=$form.gen_idField}
                <a href='index.php?action={$form.gen_linkAction|escape}&{$form.gen_idField|escape}={$row.$idField1|escape}'{if $form.gen_linkAjaxDisabled} data-ajax='false'{/if}>
            {/if}
            <div style='width:95%; overflow:hidden;'>{* コンテンツが長いときにはみ出さないようにするためのdiv *}
                {foreach from=$form.gen_columnArray item=col name=result2}
                    {if $col.label!=''}<span style='{if $col.labelFontSize!=''}font-size:{$col.labelFontSize|escape}px;{/if}{$col.labelStyle|escape}'>{$col.label}</span>{/if}
                    {assign var="field" value=$col.field}
                    <span style='{if $col.fontSize!=''}font-size:{$col.fontSize|escape}px;{/if}{$col.style|escape}'>{if $col.numberFormat=='true'}{$row.$field|number_format}{else}{$row.$field|escape}{/if}</span>
                    {$col.after_noEscape}
                {/foreach}
            </div>
            {if $form.gen_linkAction!=''}</a>{/if}
        </li>
    {/foreach}
    </ul>

    {if $form.gen_showPage > 1}
        <font color='#000000'>
        <a href='index.php?action={$form.gen_listAction|escape}&gen_search_page=1&gen_restore_search_condition=true'>{gen_tr}_g("先頭"){/gen_tr}</a>&nbsp;&nbsp;
        <a href='index.php?action={$form.gen_listAction|escape}&gen_search_page={$form.gen_prevPage|escape}&gen_restore_search_condition=true'>&lt;&lt;{gen_tr name=$form.gen_perPage}_g("前%s"){/gen_tr}</a>&nbsp;&nbsp;
        </font>
    {else}
        <font color='#cccccc'>
        {gen_tr}_g("先頭"){/gen_tr}&nbsp;&nbsp;
        &lt;&lt; {gen_tr name=$form.gen_perPage}_g("前%s"){/gen_tr}&nbsp;&nbsp;
        </font>
    {/if}
    {if $form.gen_showPage < $form.gen_lastPage}
        <font color='#000000'>
        <a href='index.php?action={$form.gen_listAction|escape}&gen_search_page={$form.gen_nextPage|escape}&gen_restore_search_condition=true'>{gen_tr name=$form.gen_perPage}_g("次%s"){/gen_tr} &gt;&gt;</a>&nbsp;&nbsp;
        <a href='index.php?action={$form.gen_listAction|escape}&gen_search_page={$form.gen_lastPage|escape}&gen_restore_search_condition=true'>{gen_tr}_g("最後"){/gen_tr}</a>
        </font>
    {else}
        <font color='#cccccc'>
        {gen_tr name=$form.gen_perPage}_g("次%s"){/gen_tr} &gt;&gt;&nbsp;&nbsp;
        {gen_tr}_g("最後"){/gen_tr}
        </font>
    {/if}
    <br>
    [page {$form.gen_showPage|escape} / {$form.gen_lastPage|escape}]
    
{else}
    <div data-role="content">{gen_tr}_g("データがありません。"){/gen_tr}</div>
{/if}

{include file="mobile/common_footer.tpl"}
