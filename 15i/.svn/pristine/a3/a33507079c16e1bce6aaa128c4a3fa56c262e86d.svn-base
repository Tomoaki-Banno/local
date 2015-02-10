{include file="common_header.tpl"}

{*************** Include Javascript and CSS ***************}

{*************** Javascript ***************}

<script>    
    
{literal}
$(function(){
    {/literal}{if !$form.gen_iPad}{literal}
        window.onresize =
            function () {
                gen.chat.adjustPageHeight();
            };
    {/literal}{/if}{literal}
    gen.chat.init('{/literal}{if $form.gen_iPad}t{else}p{/if}','{$form.chat_header_id|escape}','{$form.chat_detail_id|escape}'{literal});    // page or tablet mode
});
{/literal}
</script>

<script type="text/javascript">
    {$form.gen_javascript_noEscape}
</script>

{*************** Contents ***************}

<center>

<table id='gen_chat_page_panel' width='100%' height='100%'>
    <tr>
        <td id='gen_chat_page_listPanelParent' style='vertical-align: top; background-color: #F5F5F5;'>
            <div id='gen_chat_page_listPanel' style='height:0px'>
            </div>
        </td>
        <td id='gen_chat_page_chatPanelParent' style='vertical-align: top;'>
            <div id='gen_chat_page_chatPanel' style='height:100%'>
            </div>
        </td>
    </tr>
</table>

</center>

{include file="common_footer.tpl" gen_nofooter="true"}
