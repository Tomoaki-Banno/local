<!DOCTYPE html>
<html lang="ja" style="overflow:visible">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="Content-Style-Type" content="text/css">
<title>Genesiss15i</title>

{*** jQuery ***}
<!--[if lt IE 9]>
   <script type="text/javascript" src="scripts/jquery/jquery-1.9.0.min.js"></script>
<![endif]-->
<!--[if gte IE 9]><!-->
    <script type="text/javascript" src="scripts/jquery/jquery-2.0.1.min.js"></script>
<!--<![endif]-->

{*** YUI ***}
<script type="text/javascript" src="yui/yahoo-dom-event/yahoo-dom-event.js"></script>{*最初に*}
<script type="text/javascript" src="yui/connection/connection-min.js"></script>
<script type="text/javascript" src="yui/container/container-min.js"></script>
<link rel="stylesheet" type="text/css" href="yui/container/assets/skins/sam/container.css">

{*** JS Gettext/wordConvert ***}
{assign var="getTextLastModPropertyName" value="jsGetText"|cat:$smarty.session.gen_language|cat:"LastMod"}
<link rel="gettext" type="application/json" href="index.php?action=download&cat=jsgettext&{$smarty.session.gen_setting_company->$getTextLastModPropertyName}{*.json*}">{*GetText.jsに読ませるために最後に.jsonが必要*}
<script type="text/javascript" src="scripts/gettext/Gettext.js"></script>

<script type="text/javascript" src="scripts/gen_shortcut.js"></script>
<script type="text/javascript" src="scripts/gen_script.js"></script>
<script type="text/javascript" src="scripts/gen_waitdialog.js"></script>

<script type="text/javascript">
<!--
  var focusRow;
  var initDone = false;

  window.onload =init;
  
  var gen_iPad = {if $form.gen_iPad}true{else}false{/if};
  {* getText/wordConvert *}{literal}
  var gen_gettext = new Gettext({"domain": "messages"});
  function _g(msgid) { return gen_gettext.gettext(msgid); }    
  {/literal}

  {literal}
  function init() {
    var dd = parent.window.document.getElementById('gen_dropdown');
    {/literal}{* width, height を調整するときははいくつものDDで確認すること *}{literal}
    var ddWidth = {/literal}{$form.gen_dropdown_width|escape}{literal};
    dd.width = ddWidth + 'px';
    var ddHeight = {/literal}{$form.gen_dropdown_height|escape}{literal} + (gen.util.isIE ? 0 : (gen.util.isGecko ? 4 : 10));
    dd.height = ddHeight + 'px';
    document.getElementById('scrollArea').style.width = ({/literal}{$form.gen_dropdown_width|escape}{literal}) + 'px';
    document.getElementById('titleTable').style.width = ({/literal}{$form.gen_dropdown_width|escape}{literal}) + 'px';

    var frame = parent.parent.window.document.getElementById('gen_modal_frame');
    var ddRight = parseInt(dd.style.left) + ddWidth;
    var scrRight = (frame == null ? gen.window.getBrowserWidth() + (parent.window.document.body.scrollLeft || parent.window.document.documentElement.scrollLeft)
    	    : frame.clientWidth + frame.scrollLeft - 35);
    if (scrRight <= ddRight) {
        dd.style.left = scrRight - ddWidth - 20 + 'px';
    }
    var ddBottom = parseInt(dd.style.top) + ddHeight;
    var scrBottom = (frame == null ? gen.window.getBrowserHeight() + (parent.window.document.body.scrollTop || parent.window.document.documentElement.scrollTop)
            : frame.clientHeight + frame.scrollTop);
    if (scrBottom <= ddBottom) {
        dd.style.top = scrBottom - ddHeight - 20 + 'px';
    }
    dd.style.visibility = "visible";
    parent.window.document.getElementById('gen_dropdown').focus();

    focusRow = 0;
    if (document.getElementById('gen_dropdown_rows_0') != 'undefined') {
      focusColor('gen_dropdown_rows_0', true);
    } else {
      focusColor('gen_dropdown_rows_1', true);
    }
    document.getElementById('search').focus();

    gen.shortcut.add("Esc", function() {
      closeWindow();
    });
    gen.shortcut.add("Up", function() {
      focusMove(focusRow, focusRow-1);
    });
    gen.shortcut.add("Down", function() {
      focusMove(focusRow, focusRow+1);
    });
    gen.shortcut.add("Enter", function() {
      var activeElm = document.activeElement;
      if (activeElm.name == 'search') {
        document.getElementById('searchSubmit').click();
      } else {
        var elm = document.getElementById('gen_dropdown_rows_' + focusRow);
        if (elm != 'undefined') elm.onclick();
      }
    });
    initDone = true;
  }

  function focusMove(beforeRow, afterRow) {
    if (document.getElementById('gen_dropdown_rows_' + afterRow) == 'undefined') return;
    if (document.getElementById('gen_dropdown_rows_' + beforeRow) != 'undefined') {
      focusColor('gen_dropdown_rows_' + beforeRow, false);
    }
    focusColor('gen_dropdown_rows_' + afterRow, true);
    focusRow = afterRow;
    document.getElementById('search').blur();
  }

  function focusColor(rowId, isOnFocus) {
    if (!initDone) return;
    var color = '#cccccc';
    if (isOnFocus == false) {
      color = '#ffffff';
      if (rowId == 'gen_dropdown_rows_0') color = '#ccffff';
    }
    {/literal}
    {if !$form.gen_dropdown_hasnothingrow} {*「なし」行が存在しないときのエラー回避 *}
    if (rowId == 'gen_dropdown_rows_0') return;
    {/if}
    {literal}
    var elm = document.getElementById(rowId);
    if (elm != 'undefined') elm.style.background = color;
  }

  function clickFunc(id, show, subtext)
  {
   var elmName = "{/literal}{$form.source_control|escape}{literal}";
   var showElm = parent.window.document.getElementById(elmName);
   showElm.value = show;
   var phElm = parent.window.document.getElementById(elmName + '_placeholder');
   if (phElm != undefined) phElm.style.display = 'none';
   if (elmName.substr(elmName.length-5,5)=='_show') {
        var hiddenName = elmName.substr(0,elmName.length-5);
        var hiddenElm = parent.window.document.getElementById(hiddenName);
        hiddenElm.value = id;
        var subElm = parent.window.document.getElementById(hiddenName + '_sub');
        if (subElm != undefined) subElm.value = (subtext==undefined ? '' : subtext);
   }
   parent.gen.dropdown.close(true);
   //showControl.focus();
  }

  function regNewRecord()
  {
   var elmName = "{/literal}{$form.source_control|escape}{literal}";
   var showControl = parent.window.document.getElementById(elmName);
   showControl.value = 'gen_dropdownNewRecordButton';   // 新規登録をキックするためのダミー文字列
   showControl.onchange();
   showControl.value = '';
   closeWindow();
  }

  function closeWindow()
  {
   var elmName = "{/literal}{$form.source_control|escape}{literal}";
   var showControl = parent.window.document.getElementById(elmName);
   parent.gen.dropdown.close(false);
   showControl.focus();
  }
 {/literal}
 -->
 </script>
 <LINK rel="stylesheet" type="text/css" href="css/dropdown.css">
 <LINK rel="stylesheet" type="text/css" href="css/common.css">
 <link rel="stylesheet" type="text/css" href="css/common{if $form.gen_iPad}_ipad{else}_pc{/if}.css">
 </head>
 
 <body>
 <input type='hidden' id='gen_ajax_token' value='{$smarty.session.gen_ajax_token|escape}'>
{if count($form.gen_dropdown_data) > 0}
  {* このテーブルの幅は、IEの場合のみJavascriptで調整される（function init） *}
  <table id='titleTable' border=0 cellspacing=0 cellpadding=2 style="font-size:12px; overflow:hidden;">
   <form method="POST" action="index.php?action=Dropdown_Dropdown&category={$form.category|escape}&where={$form.where|escape}&matchMode={$form.matchMode|escape}&param={$form.param|escape}&source_control={$form.source_control|escape}{if $form.hide_new}&hide_new=true{/if}">

   {* 絞込み・閉じるボタン行 *}
   <tr bgcolor="#cccccc" style='height:{$form.gen_dropdown_headerheight|escape}px'>
       <td colspan={$form.gen_dropdown_colcount+1} nowrap>
          <input type="textbox" name="search" id="search" value="{$form.search|escape}" style="width:100px" autocomplete="off">
          {* マッチモード *}
          {if $form.gen_dropdown_matchBox}
          <select name="matchBox" id="matchBox">
          <option value="0" {if $form.matchBox==0}selected{/if}>{gen_tr}_g("を含む"){/gen_tr}</option>
          <option value="1" {if $form.matchBox==1}selected{/if}>{gen_tr}_g("で始まる"){/gen_tr}</option>
          <option value="2" {if $form.matchBox==2}selected{/if}>{gen_tr}_g("で終わる"){/gen_tr}</option>
          <option value="3" {if $form.matchBox==3}selected{/if}>{gen_tr}_g("と一致"){/gen_tr}</option>
          <option value="4" {if $form.matchBox==4}selected{/if}>{gen_tr}_g("を含まない"){/gen_tr}</option>
          <option value="5" {if $form.matchBox==5}selected{/if}>{gen_tr}_g("で始まらない"){/gen_tr}</option>
          <option value="6" {if $form.matchBox==6}selected{/if}>{gen_tr}_g("で終わらない"){/gen_tr}</option>
          </select>
          {/if}
          {$form.gen_pin_html_search}
          <input type="submit" id="searchSubmit" value="{gen_tr}_g("絞込み"){/gen_tr}" style="width:60px">
          {* 新規ボタン *}
          {if $form.gen_dropdown_hasnewbutton}
          <img src="img/space.gif" style="width:10px">
          <input type="button" id="newRecord" onClick="regNewRecord()" value="{gen_tr}_g("新規登録"){/gen_tr}" style="width:70px">
          {/if}
          {* 絞込みセレクタ *}
          {if $form.gen_dropdown_hasselecter}
           <br><span style="font-size:10px">{$form.gen_dropdown_selecterTitle|escape}</span><select name="selecterSearch" id="selecterSearch" onChange="javascript:document.getElementById('searchSubmit').click()" style="width:250px">{$form.gen_dropdown_selectoptiontags_noEscape}</select>
           {$form.gen_pin_html_selecter}
          {/if}
          {* 絞込みセレクタ2 *}
          {if $form.gen_dropdown_hasselecter2}
           <br><span style="font-size:10px">{$form.gen_dropdown_selecterTitle2|escape}</span><select name="selecterSearch2" id="selecterSearch2" onChange="javascript:document.getElementById('searchSubmit').click()" style="width:250px">{$form.gen_dropdown_selectoptiontags_noEscape2}</select>
           {$form.gen_pin_html_selecter2}
          {/if}
          {* 閉じるボタン *}
          <div id='closeButton' style='position:absolute; left:{$form.gen_dropdown_width-30}px; top:4px'><input type="button" class="mini_button" onClick="closeWindow()" value="×"></div>
       </td>
   </tr>

   </form>

   {* 見出し行 *}
   <tr bgcolor="#cccccc" style='height:24px'>
      {foreach item=col key=key from=$form.gen_dropdown_title name=colloop}
      {if $key != 'id'}
       <td style='width:{$form.gen_dropdown_columnwidth[$smarty.foreach.colloop.iteration]}px; text-align:{$form.gen_dropdown_align[$smarty.foreach.colloop.iteration]};white-space: nowrap;overflow:hidden'>
          {$col|escape}{$form.gen_dropdown_order_noEscape[$col]}
       </td>
      {/if}
      {/foreach}
      <td id='adjustCell' style='width:17px'></td>
   </tr>

</table>

 {* スクロールエリア。横幅は、IEの場合のみJavascriptで調整される（function init） *}
 <div id="scrollArea" style="overflow-y:scroll; width:{$form.gen_dropdown_width-6}px; height:{$form.gen_dropdown_scrollareaheight|escape}px;">
  <table border=0 cellspacing=0 cellpadding=2 style="font-size:12px;">

   {* 「なし」行 *}
   {if $form.gen_dropdown_hasnothingrow}
    <tr onClick="clickFunc('', '');" bgcolor="#ccffff"
    id="gen_dropdown_rows_0"
    onmouseover="focusColor('gen_dropdown_rows_0', true)"
    onmouseout="focusColor('gen_dropdown_rows_0', false)"
    height="14">
        <td colspan={$form.gen_dropdown_colcount|escape}>
           {gen_tr}_g("（なし）"){/gen_tr}
        </td>
    </tr>
   {/if}

   {* データ行 *}
   {foreach item=row from=$form.gen_dropdown_data name=rowloop}
    <tr onClick="clickFunc('{ $row.id|escape }', '{ $row.show|strtr:"'":"\""|escape }', '{ $row.subtext|strtr:"'":"\""|escape }');"
    id="gen_dropdown_rows_{$smarty.foreach.rowloop.iteration}"
    onmouseover="focusColor('gen_dropdown_rows_' + focusRow,false);focusRow={$smarty.foreach.rowloop.iteration};focusColor('gen_dropdown_rows_' + focusRow,true)"
    onmouseout="focusColor('gen_dropdown_rows_' + {$smarty.foreach.rowloop.iteration},false)"
    style="cursor: default;"
    height="{if $form.gen_iPad}28{else}14{/if}"
    >
     {foreach item=col key=key from=$row name=colloop}
       {if $key != 'id'}
       <td>
          <div style="width:{$form.gen_dropdown_columnwidth[$smarty.foreach.colloop.iteration]-2}px; text-align:{$form.gen_dropdown_align[$smarty.foreach.colloop.iteration]|escape}; overflow:hidden">{$col|escape}</div>
       </td>
       {/if}
     {/foreach}
    </tr>
   {/foreach}

   {* 前のx件行 *}
   {if $form.gen_dropdown_prevpageurl != ''}
    <tr onClick="location.href='{$form.gen_dropdown_prevpageurl|escape}'" bgcolor="#ccccff" height="15"
    id="gen_dropdown_prevLine"
    onmouseover="document.getElementById('gen_dropdown_prevLine').style.background='#cccccc'"
    onmouseout="document.getElementById('gen_dropdown_prevLine').style.background='#ccccff'"
    >
     <td colspan={$form.gen_dropdown_colcount|escape} >（&lt;&lt; {gen_tr name=$form.gen_dropdown_perpage|escape}_g("前の %s 件"){/gen_tr}）</td>
    </tr>
   {/if}

   {* 次のx件行 *}
   {if $form.gen_dropdown_nextpageurl != ''}
    <tr onClick="location.href='{$form.gen_dropdown_nextpageurl|escape}'" bgcolor="#ccccff" height="15"
    id="gen_dropdown_nextLine"
    onmouseover="document.getElementById('gen_dropdown_nextLine').style.background='#cccccc'"
    onmouseout="document.getElementById('gen_dropdown_nextLine').style.background='#ccccff'"
    >
     <td colspan={$form.gen_dropdown_colcount|escape} >（{gen_tr name=$form.gen_dropdown_perpage|escape}_g("次の %s 件"){/gen_tr} &gt;&gt;）</td>
    </tr>
   {/if}
  </table>
  </div>
 {/if}

 </body>
</html>
