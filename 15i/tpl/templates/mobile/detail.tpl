{include file="mobile/common_header.tpl"}

{if $form.gen_data}
    <script>
        {literal}
        // スワイプ。common_headerには記述できない
//        $(document).off('pageshow', '#gen_mobile_page').on('pageshow', '#gen_mobile_page', function(event){
//        //$('#gen_mobile_page').die('pageshow').live('pageshow',function(event){
//            $("#gen_mobile_page")
//                .die("swipeleft")
//                .die("swiperight");
//            {/literal}{if $form.gen_prevAction!=''}{literal}
//                $(document)
//                    .on("swiperight", "#gen_mobile_page", function(){
//                //$("#gen_mobile_page")
//                //    .live("swiperight", function(){
//                        $.mobile.changePage("index.php?action={/literal}{$form.gen_prevAction|escape}{literal}", { transition: "slide", reverse: "true"});
//                    });
//            {/literal}{/if}{literal}
//            {/literal}{if $form.gen_nextAction!=''}{literal}
//                $(document)
//                    .on("swipeleft", "#gen_mobile_page", function(){
//                //$("#gen_mobile_page")
//                //    .live("swipeleft", function(){
//                        $.mobile.changePage("index.php?action={/literal}{$form.gen_nextAction|escape}{literal}", { transition: "slide"});
//                    });
//            {/literal}{/if}{literal}
//        });

        function gen_detail_edit(field) {
            var valueSpan = $(".gen_value_"+field);
            if (valueSpan.html().slice(0,6)=='<input') return;
            $(".gen_value_"+field).html("<input type='textbox' name='"+field+"' value='"+$(".gen_value_"+field).html()+"'>");
            $("[name="+field+"]")
                .focus()
                .blur(function(){$(".gen_value_"+field).html($("[name="+field+"]").val())});
        }
        {/literal}
        {$form.gen_javascript_noEscape}
    </script>

    {foreach from=$form.gen_columnArray item=col name=result}
        <!-- Line -->
        <div style='height:1px;background-color:#cccccc'></div>

        {if $col.sectionHeader=='true'}
            <br>
            <b>{$col.label|escape}</b>
        {else}
            {assign var="field" value=$col.field}
            
            <!-- Label -->
            {if $col.label!=''}<span style='font-size:12px; color:#999999;{$col.labelStyle|escape}'>{$col.label|escape}</span>{/if}
            <br>

            <!-- Contents -->
            <table width="100%"><tr>
            <td width='6px'></td>
            <td>
                <span style='{if $col.fontSize!=''}font-size:{$col.fontSize|escape}px;{/if}{$col.style|escape};'>
                    {if $col.preLabel!=''}{$col.preLabel|escape}{/if}
                    {if $col.preField!=''}{assign var="preField" value=$col.preField}{$row.$preField|escape}{/if}
                    <span class='gen_value_{$field|escape}'>{if $col.numberFormat=='true'}{$form.gen_data[0].$field|number_format}{else}{$form.gen_data[0].$field|escape}{/if}</span>
                    {if $col.afterField!=''}{assign var="afterField" value=$col.afterField}{$row.$afterField|escape}{/if}
                </span>
            </td>
            <td align="right">
<!-- 編集機能は保留
                {if $col.label!=''}<img src='img/pencil.png' onclick="gen_detail_edit('{$field|escape}')">{/if}
-->
            </td>
            </tr></table>

        {/if}
    {/foreach}

    {if $form.gen_detailData}
        {foreach from=$form.gen_detailData item=row name=result}
            <br>
            <div style='width:100%; background-color:#ffcc99'>■{gen_tr}_g("明細"){/gen_tr}{$smarty.foreach.result.iteration}</div>
            <br>

            {foreach from=$form.gen_detailColumnArray item=col name=result2}
                <!-- Line -->
                <!-- <div style='height:1px;background-color:#999999'></div> -->

                {assign var="field" value=$col.field}

                <!-- Label -->
                {if $col.label!=''}<span style='font-size:12px; color:#999999;{$col.labelStyle|escape}'>{$col.label|escape}</span>{/if}
                <br>

                <!-- Contents -->
                <table><tr>
                <td width='6px'></td>
                <td>
                    <span style='{if $col.fontSize!=''}font-size:{$col.fontSize|escape}px;{/if}{$col.style|escape};'>
                        <span style='{if $col.fontSize!=''}font-size:{$col.fontSize|escape}px;{/if}{$col.style|escape}' onclick="gen_detail_content_onclick('{$field|escape}')">
                            {if $col.preLabel!=''}{$col.preLabel|escape}{/if}
                            {if $col.preField!=''}{assign var="preField" value=$col.preField}{$row.$preField|escape}{/if}
                            {if $col.numberFormat=='true'}{$row.$field|number_format}{else}{$row.$field|escape}{/if}
                            {if $col.afterField!=''}{assign var="afterField" value=$col.afterField}{$row.$afterField|escape}{/if}
                        </span>
                    </span>
                </td>
                </tr></table>

            {/foreach}
        {/foreach}
    {/if}
{else}
    <div data-role="content">{gen_tr}_g("データがありません。"){/gen_tr}</div>
{/if}

{include file="mobile/common_footer.tpl"}
