<!DOCTYPE html>
<html lang="ja" style='height:100%'>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="Content-Style-Type" content="text/css">

<link rel="shortcut icon" href="img/15i_favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="img/15i_favicon.ico" type="image/vnd.microsoft.icon">
<link rel="stylesheet" type="text/css" href="css/login.css">

<!--[if lt IE 9]>
   <script type="text/javascript" src="scripts/jquery/jquery-1.9.0.min.js"></script>
<![endif]-->
<!-[if gte IE 9]><!->
    <script type="text/javascript" src="scripts/jquery/jquery-2.0.1.min.js"></script>
<!-[endif]->
<script type="text/javascript" src="scripts/gen_script.js"></script>

{*************** Javascript ***************}
<script type="text/javascript">
{literal}
$(function() {
    $('#form1').css('display', 'none').fadeTo(1500,1);
    if ($('#gen_modal_frame', parent.document).length > 0) {
        parent.location.href='index.php?action=Login{/literal}{if $form.gen_concurrent_error}&gen_concurrent_error=true{/if}{literal}';
    }
    $('#background').attr('src', '{/literal}{$form.gen_login_image}{literal}');

    $('#form1').attr('action','index.php?action=Login');
    $('#loginUserId').focus();
});
{/literal}
</script>

<title>GENESISS Login</title>
{*このコメントはエクセルで使用しているので変えないこと*}<!-- Login Failure Flag for Excel modXmlHttp module -->{if $form.gen_concurrent_error}<!-- Concurrent Error Flag for Excel modXmlHttp module -->{/if}
{*ここはlist.tpl の listUpdate()で使用しているので変えないこと*}<div id='gen_loginDisplayFlag' style='visibility: hidden'>{$form.gen_concurrent_error|escape}</div>

<style type="text/css">
{literal}
* {
  font-family: "ヒラギノ角ゴ Pro W3", "Hiragino Kaku Gothic Pro", "メイリオ", "Meiryo", "ＭＳ Ｐゴシック", "MS P Gothic", sans-serif;
  font-size: 12px;
  line-height: 1.2;
}
body {
    margin-top: 0px;
    margin-right: 0px;
    margin-bottom: 0px;
    margin-left: 0px;
    padding-top: 0px;
    padding-right: 0px;
    padding-bottom: 0px;
    padding-left: 0px;
    background-color: #FFFFFF;
    position: relative; {/literal}{* 背景画像のフルスクリーン表示のために必要 *}{literal}
}

{/literal}{* 下の2つは背景画像のフルスクリーン表示のために必要 *}{literal}
#background {
    min-height: 100%;
    min-width: 1024px;
    width: 100%;
    height: auto;
    position: fixed;
    top: 0;
    left: 0;
}
@media screen and (max-width: 1024px){
    #background {
        left: 50%;
        margin-left: -512px;
    }
}

p {
    text-align: center;
    font-size: 12px;
}
div.login {
    text-align: center;
    background-image: url(img/15i_login.png);
    background-repeat: no-repeat;
    background-position: center center;
    margin: 0px auto;
    width: 500px;
    height: 400px;
    opacity: 0.9;
}
div.loginUserId{
    position: relative;
    top: 180px;
    left: 15px;
}
div.password{
    position: relative;
    top: 200px;
    left: 15px;
}
div.button{
    position:relative;
    top: 230px;
    left: 0px;
}
input.loginUserId {
    width: 140px;
    height: 20px;
    border: 0px;
    /* border: solid 1px #CCCCCC; */
    padding-left: 4px;
    padding-top: 3px;
    ime-mode: inactive;
    background-color: transparent;
    /* border: 0px; */
}
input.password {
    width: 140px;
    height: 20px;
    border: 0px;
    /* border: solid 1px #CCCCCC; */
    padding-left: 4px;
    padding-top: 8px;
    ime-mode: inactive;
    background-color: transparent;
}
input.button {
    border: 0px;
    width: 60px;
    height: 28px;
    cursor: pointer;
    opacity: 0;
}
{/literal}
</style>
</head>

<body style='height:100%'>
<img id="background"/>

<div style='position:absolute; top:60px; width:100%; font-size:14px'>
    {if isset($form.error)}<p><span style="color:red; background: white; padding:5px">{$form.error|escape}</span></p>{/if}
    {if isset($form.login_msg)}<p><span style="color:blue; background: white; padding:5px">{$form.login_msg|escape}</span></p>{/if}
</div>

<div align="center" style="height: 85px;"></div>

<form id="form1" method="POST">
    <input type="hidden" id="imageId" name="imageId" value="{$form.gen_image_id|escape}">
    <div align="center" class="login">
        <div class="loginUserId">
            <input type="text" id="loginUserId" name="loginUserId" class="loginUserId">
        </div>
        <div class="password">
            <input type="password" id="password" name="password" class="password">
        </div>
        <div class="button">
            <input type="submit" name="submit" id="submit" value="" class="button">
        </div>
    </div>
</form>

{if $form.gen_trust_code!=''}
<div id='secom_sticker' style='position:absolute; bottom:10px; left:20px;'>
    <img src='img/secom_icon_base.png'>
    <div style='position:absolute; bottom:5px; left:15px'>
        <form action="https://www.login.secomtrust.net/customer/customer/pfw/CertificationPage.do" name="CertificationPageForm" method="POST" target="_blank">
        <input type="image" border="0" name="Sticker" src="img/secom_trust.gif" style="width:28px; height:45px" alt="クリックして証明書の内容をご確認ください。" oncontextmenu="return false">
        <input type="hidden" name="Req_ID" VALUE="{$form.gen_trust_code|escape}"/>
        </form>
    </div>
</div>
{/if}

<div style='position:absolute; bottom:10px; right:20px; font-size:11px; color:white'>
    Genesiss (C) 2001-2014 e-Commode Corporation All rights reserved.&nbsp;&nbsp;
    <a href="http://www.e-commode.co.jp/function/" target="_blank" tabindex="-1" style='color:white; font-size:11px;'>{gen_tr}_g("ご利用環境"){/gen_tr}</a>
</div>

<noscript>
    <center>
    <table border=1 bgcolor="#ccffff" style='color:white'>
    <tr><td><font size=2>
    {gen_tr}_g("JavaScriptが有効になっていません。"){/gen_tr}<BR>
    {gen_tr}_g("このままではアプリケーションを正常に利用することができません。"){/gen_tr}<BR>
    {gen_tr}_g("JavaScriptを有効にしてください。"){/gen_tr}
    </td></tr></font></table>
    </center>
</noscript>

</body>
</HTML>