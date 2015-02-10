{include file="common_header.tpl"}

{*************** Include Javascript and CSS ***************}
<link rel="stylesheet" type="text/css" href="scripts/jquery.ui/css/jquery-ui.min.css">
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.core.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.widget.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.mouse.min.js"></script>
<script type="text/javascript" src="scripts/jquery.ui/jquery.ui.sortable.min.js"></script>

<link rel="stylesheet" type="text/css" href="scripts/inettuts/inettuts.css">
<script type="text/javascript" src="scripts/inettuts/inettuts.js"></script>
    
<link href="scripts/visualize/visualize.css" type="text/css" rel="stylesheet" />
<link href="scripts/visualize/visualize-light.css" type="text/css" rel="stylesheet" />
<script type="text/javascript" src="scripts/visualize/excanvas.js"></script>
<script type="text/javascript" src="scripts/visualize/visualize.jQuery.js"></script>

{*************** Javascript ***************}

<script>    
{literal}
$(function(){
    gen.chart.init();
    {/literal}{*F1キーで画面更新。F5キーと同じだが、他リスト画面と操作を統一するため*}{literal}
    gen.shortcut.add("F1", function() {
        location.reload();
    });
    if (gen.util.isIE) {
        window.onhelp = function() {
            return false;
        }
    }
});
function widgetReset() {
    if (confirm('{/literal}{gen_tr}_g("パーツをリセットします。よろしいですか？"){/gen_tr}{literal}')) {
        location.href = '{/literal}index.php?action={$form.gen_listAction|escape}&widgetReset{literal}';
    }
}

function showWidgetSelectDialog() {
    var obj = {};
    {/literal}{foreach from=$form.widgetArrForDialog item=item}obj['{$item.id|escape}']="{if $item.hide==true}@{/if}{$item.title|escape}";{/foreach}{literal}

    var btn = $('#showWidgetSelectDialogLink');
    var pos = btn.offset();
    pos.top += 20;
    if (this.widgetSelectDialog == null) {
        var html = "";
        html += "<div class='yui-skin-sam'><div id='gen_widgetSelectDialog' style='background-color:#ffffff;'>";
        html += "<div class='hd' style='background-color:#999999'>{/literal}{gen_tr}_g("パーツの選択"){/gen_tr}{literal}</div>";
        html += "<div style='width:170px; height:300px; overflow-y:scroll'>";
        html += "<table cellpadding='0' cellspacing='0'>";
        jQuery.each(obj, function(key, val) {
            checked = true;
            if (val.substr(0,1)=='@') {    // 非表示列
                checked = false;
                val = val.substr(1);
            }
            html += "<tr id='gen_widgettr_"+key+"'"+(checked ? "" : " bgcolor='#cccccc'")+"><td><input type='checkbox' id='gen_widgetselect_"+key+"'"+(checked ? " checked" : "")+"></td><td width='5px'></td><td align='left'>"+val+"</td></tr>";
        });
        html += "</table></div><br>";
        html += "<input type='checkbox' id='gen_widgetSelectDialogAlterCheck' value='true' onchange='alterCheckShowWidgetDialog();'>{/literal}{gen_tr}_g("全チェック/全解除"){/gen_tr}{literal}<br>";
        html += "<input type='button' value='{/literal}{gen_tr}_g("登録"){/gen_tr}{literal}' onclick=\"saveWidgetSelectDialog();\">";
        html += "<input type='button' value='{/literal}{gen_tr}_g("キャンセル"){/gen_tr}{literal}' onclick='closeShowWidgetDialog();'>";
        html += "</div></div>";
        $('#gen_body').append(html);

        this.widgetSelectDialog = new YAHOO.widget.Dialog("gen_widgetSelectDialog",
            {xy : [pos.left, pos.top],
              close: false,
              constraintoviewport : true
             } );
        this.widgetSelectDialog.render();
    }
    this.widgetSelectDialog.show();
}
function saveWidgetSelectDialog() {
    var o = {};
    var f = false;
    $('[id^=gen_widgetselect_]').each(function(){o[this.id] = (this.checked ? 1 : 0);if (this.checked) f = true;});
    if (!f) {
        alert('{/literal}{gen_tr}_g("すべてのチェックボックスがオフになっています。1つ以上のチェックボックスをオンにしてください。"){/gen_tr}{literal}');
        return;
    }
    gen.ajax.connect('Config_Setting_AjaxDashboardInfo', o, 
        function(){
            location.href = 'index.php?action=Menu_Home2'
        });
}
function closeShowWidgetDialog() {
    this.widgetSelectDialog.destroy();
    this.widgetSelectDialog = null;
}
function alterCheckShowWidgetDialog() {
    var checked = ($('#gen_widgetSelectDialogAlterCheck').is(':checked'));
    $('[id^=gen_widgetselect_]').attr('checked', checked);
}
function openWindow(url) {
    window.open(url);
}    
{/literal}
</script>

<script type="text/javascript">
    {$form.gen_javascript_noEscape}
</script>

{*************** CSS ***************}

<style type="text/css">
{literal}
<!--
table .dataTable { width:400px; height:100px; border-spacing:1px; background:#999; border:0px; table-layout:fixed; overflow:scroll }
th, td .dataTableCell { padding: 1px; background: #fff; word-wrap:break-word }
tr .headerRow { height: 10px }
-->
{/literal}
</style>

{*************** Contents ***************}
<input type='hidden' id='gen_dashboardFlag'>

<div style="min-height:1000px">
<center>    

<div style='height:12px'></div>

<table align="center" cellspacing="0" cellpadding="0">
    <tr valign="middle" style="height:20px">
        <td>
            <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
        </td>
    </tr>
</table>

<div id='gen_page_div'>
     
{* リンクを右端に置くため、親divを position:relative にし、子spanをabsoluteで追加している *}
<div style='position:relative; top:0px; left:0px;'>
    <span style='position:absolute; top:0px; right:10px;'>
    <a href="javascript:location.reload()" style="color:#000000">{gen_tr}_g("情報の更新 (F1)"){/gen_tr}</a>
    &nbsp;
    <a id="showWidgetSelectDialogLink" href="javascript:showWidgetSelectDialog()" style="color:#000000">{gen_tr}_g("パーツの選択"){/gen_tr}</a>
    &nbsp;
    <a href="javascript:widgetReset()" style="color:#000000">{gen_tr}_g("パーツをリセット"){/gen_tr}</a>
    </span>
</div>

<div id='gen_dataTable'> {* edit dialog 表示用 *}
<form action="index.php?action={$form.gen_listAction|escape}&gen_restore_search_condition=true" id="form1" name="form1" method="post" AUTOCOMPLETE="OFF" onSubmit="return false;">

    <div id="columns">
	{foreach from=$form.widgetArr key=widgetKey item=widget name=widgetLoop}
            {assign var="rowSpanCount" value=""}
            {if $widget.data == 'ul'}
                {if $smarty.foreach.widgetLoop.iteration > 1}</ul>{/if}
                <ul class="column" style="min-width:600px" id="ul_{$widgetKey|escape}">
            {else}
                <li class="widget color-blue" id="cell{$widget.id|escape}">
                    <div class="widget-head" style='text-align:left'>
                        <table style='height:100%'><tr><td><h3>{$widget.title|escape}</h3></td></tr></table>
                    </div>

                    <div class="widget-content"{if $widget.close==true} style="display:none;"{else}{/if}>
                        {if $widget.permissionError==true}
                            {gen_tr}_g("このパーツを表示する権限がありません。"){/gen_tr}
                        {else}
                            {if $widget.buttons!=''}
                                {foreach from=$widget.buttons item=button}
                                    {if $button.window=='dialog'}
                                        <input type='button' onclick="gen.modal.open('index.php?action={$button.action|escape}')" value="{$button.label|escape}">
                                    {else}
                                        <a href="javascript:openWindow('index.php?action={$button.action|escape}')" style="color:black">{$button.label|escape}</a>
                                    {/if}
                                {/foreach}
                            {/if}
                            {if $widget.chart!=''}<div id="chart{$smarty.foreach.widgetLoop.iteration}"></div>{/if}

                            <table{if $widget.chart!=''} class="gen_chartSource dataTable" data-charttype="{$widget.chart|escape}" data-chartwidth="350" data-chartheight="200"{if $widget.appendkey=='true'} data-appendkey="true"{/if}{else} class="dataTable"{/if} id="chartSource{$smarty.foreach.widgetLoop.iteration}">
                                <thead>
                                <tr class="headerRow">
                                {foreach from=$widget.cols item=col name=colLoop}
                                    <td
                                    {if $col.width != ''} width="{$col.width|escape}px"{/if} 
                                    class="dataTableCell" align="center" >
                                        {$col.label|escape}
                                    </td>
                                {/foreach}
                                </tr>
                                </thead>

                                {assign var="dataArr" value=$widget.data}
                                {foreach from=$widget.data item=dataRow name=rowLoop}
                                    {assign var="rowIndex" value=$smarty.foreach.rowLoop.iteration}
                                    <tr>
                                    {foreach from=$widget.cols item=col name=colLoop}
                                        {assign var="field" value=$col.field}
                                        {assign var="parentColumn" value=$col.parentColumn}
                                        {if isset($rowSpanCount.$field) && $rowSpanCount.$field > 1}
                                            {php}
                                                $rowSpanCount = $this->get_template_vars('rowSpanCount');
                                                $field = $this->get_template_vars('field');
                                                $rowSpanCount[$field]--;
                                                $this->assign('rowSpanCount', $rowSpanCount);
                                            {/php}
                                        {else}
                                            {if $smarty.foreach.colLoop.iteration==1 && $widget.noth != true}<th scope="row"{else}<td{/if}
                                            {if $col.align != ''} align="{$col.align|escape}"{/if}
                                            {if $col.sameCellJoin == 'true'}  
                                                {assign var="data" value=$dataRow.$field}
                                                {if $parentColumn != ""}
                                                    {assign var="parentColumnData" value=$dataRow.$parentColumn}
                                                {/if}
                                                {php}
                                                    $rowSpanCount = $this->get_template_vars('rowSpanCount');
                                                    $parentColumn = $this->get_template_vars('parentColumn');
                                                    $field = $this->get_template_vars('field');
                                                    if ($parentColumn == "") {
                                                        $checkField = $field;
                                                        $data = $this->get_template_vars('data');
                                                    } else {
                                                        $checkField = $this->get_template_vars('parentColumn');
                                                        $data = $this->get_template_vars('parentColumnData');
                                                    }
                                                    $dataArr = $this->get_template_vars('dataArr');
                                                    $idx = $this->get_template_vars('rowIndex') - 1;
                                                    $rowCnt = count($dataArr);
                                                    for ($i = $idx + 1; $i < $rowCnt; $i++ ) {
                                                        if ($dataArr[$i][$checkField] != $data) break;
                                                    }
                                                    $rowSpanCount[$field] = $i - $idx;
                                                    $this->assign('rowSpanCount', $rowSpanCount);
                                                {/php}
                                                rowspan="{$rowSpanCount.$field|escape}"
                                            {/if}
                                            class="dataTableCell"
                                            style="{$col.style}"
                                            >
                                            {if $col.link_noEscape != ''}
                                                {assign var="link_noEscape" value=$col.link_noEscape}
                                                {php}
                                                 $link_noEscape = $this->get_template_vars('link_noEscape');
                                                 $dataRow = $this->get_template_vars('dataRow');
                                                 $matches = "";
                                                 if (preg_match_all("(\[[^\]]*\])", $link_noEscape, $matches) > 0) {
                                                     foreach ($matches[0] as $match) {
                                                         $matchStr = $match;
                                                         $matchStr = str_replace('[', '', $matchStr);
                                                         $matchStr = str_replace(']', '', $matchStr);
                                                         $val = $dataRow[$matchStr];
                                                         $link_noEscape = str_replace($match, $val, $link_noEscape);
                                                     }
                                                 }
                                                 $this->assign('link_noEscape', $link_noEscape);
                                                {/php}         
                                                 <a href="{$link_noEscape}" style="color:#000">
                                            {/if}
                                            {if $col.numberFormat=='true'}{$dataRow.$field|number_format}{else}{$dataRow.$field|escape|replace:"&lt;br&gt;":"<br><div style='height:4px'></div>"}{/if}
                                            {if $col.link_noEscape != ''}</a>{/if}

                                            {if $smarty.foreach.colLoop.iteration==1 && $widget.noth != true}</th>{else}</td>{/if}
                                        {/if}
                                    {/foreach}
                                    </tr>
                               {/foreach}
                            </table>
                        {/if}
                    </div>
                </li>
            {/if}
        {/foreach}
        </ul>
    </div>

</form>
</div>        
</div>
</center>    
</div>

{include file="common_footer.tpl"}
