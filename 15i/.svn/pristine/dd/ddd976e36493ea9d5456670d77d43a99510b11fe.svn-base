{include file="mobile/common_header.tpl"}

{*************** Include Javascript and CSS ***************}

<script type="text/javascript" src="yui/yahoo-dom-event/yahoo-dom-event.js"></script>
<script type="text/javascript" src="yui/connection/connection-min.js"></script>
<script type="text/javascript" src="yui/container/container-min.js"></script>
<link rel="stylesheet" type="text/css" href="yui/container/assets/skins/sam/container.css">

<script type="text/javascript" src="scripts/gen_script.js"></script>
<script type="text/javascript" src="scripts/gen_shortcut.js"></script>
<script type="text/javascript" src="scripts/gen_waitdialog.js"></script>
<script type="text/javascript" src="scripts/gen_chat.js"></script>
<link rel="stylesheet" type="text/css" href="css/common.css">

{*************** Javascript ***************}

<script type="text/javascript">
{literal}
$(function(){
    gen.chat.init('s',{/literal}'{$form.chat_header_id|escape}','{$form.chat_detail_id|escape}'{literal}); // smartphone mode
});
{/literal}
{$form.gen_javascript_noEscape}
</script>

{*************** Contents ***************}
{* common_header.tpl内 のヘッダは無効化されている（このtpl冒頭のinclude部分で「gen_noHeader」を指定） *}
<div id="gen_chat_header" data-role="header" data-position="fixed" data-tap-toggle="false" style="display: none;background: #B1C0D3;font-weight: normal;text-shadow: none"></div>
<div id="gen_chat_panel" style='text-align:left; height: 100%'></div>
<div id="gen_chat_footer" data-role="footer" data-position="fixed" data-tap-toggle="false" style="display: none;background: #fff"></div>

</div>{*common_header.tpl data-role="content"*}
</div>{*common_header.tpl gen_mobile_page*}
</body>
</html>