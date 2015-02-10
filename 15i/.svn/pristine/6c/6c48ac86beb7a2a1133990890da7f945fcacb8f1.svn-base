{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{$form.gen_javascript_noEscape}
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

<table width="800">
    <tr style="height: 7px"><td></td></tr>
    <tr><td align="center">
        <div class="listTitle">
            {gen_tr}_g("パラメータ一覧（admin専用）"){/gen_tr}
        </div>
    </td></tr>
    <tr style="height: 30px"><td></td></tr>
</table>

{$form.gen_dataMessage_noEscape}
<table border="1" cellspacing="0" cellpadding="2" style="border-style: solid; border-color: #696969; border-collapse: collapse;">
<tr class="dataListTitle" bgcolor="#cccccc">
    <th width="100px" nowrap style="{$form.gen_style|escape}">{gen_tr}_g("情報"){/gen_tr}</th>
    <th width="200px" nowrap style="{$form.gen_style|escape}">{gen_tr}_g("項目"){/gen_tr}</th>
    <th width="100px" nowrap style="{$form.gen_style|escape}">{gen_tr}_g("設定値"){/gen_tr}</th>
    <th width="100px" nowrap style="{$form.gen_style|escape}">{gen_tr}_g("デフォルト値"){/gen_tr}</th>
    <th width="450px" nowrap style="{$form.gen_style|escape}">{gen_tr}_g("備考"){/gen_tr}</th>
</tr>
{foreach item=info from=$form.paramInfo}
<tr>
    {if $info.title!=""}<td rowspan="{$info.count|escape}" nowrap style="{$form.gen_style|escape}" align="left">{$info.title|escape}&nbsp;</td>{/if}
    <td nowrap style="{$form.gen_style|escape} {$info.style|escape}" align="left">{$info.name|escape}&nbsp;</td>
    <td nowrap style="{$form.gen_style|escape} {$info.styleParam|escape}" align="center">{$info.data|escape}&nbsp;</td>
    <td nowrap style="{$form.gen_style|escape}" align="center">{$info.default|escape}&nbsp;</td>
    <td nowrap style="{$form.gen_style|escape}" align="left">{$info.remarks|escape}&nbsp;</td>
</tr>
{/foreach}
</table>
<br><br>

</div>
{include file="common_footer.tpl"}

