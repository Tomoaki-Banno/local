{include file="mobile/common_header.tpl"}

{if isset($form.error)}<p><font color="red">{$form.error|escape}</font></p>{else}<p></p>{/if}

<form action="index.php?action=Login" method="post">
<table>
    <tr><td>{gen_tr}_g("ユーザー名"){/gen_tr}</td><td><input type="text" name="loginUserId" id="mobile_login_userid" value=""  /></td></tr>
    <tr><td>{gen_tr}_g("パスワード"){/gen_tr}</td><td><input type="password" name="password" id="mobile_login_password" value=""  /></td></tr>
</table>
<input type="submit" value="Login"  />
</form>
<br>
Genesiss (C) 2001-2014 e-Commode Corporation All rights reserved.

{*この部分は未テスト*}
{if $form.gen_trust_code!=''}
<center>
<form action="https://www.login.secomtrust.net/customer/customer/pfw/CertificationPage.do" name="CertificationPageForm" method="POST" target="_blank">
<input type="image" border="0" name="Sticker" src="img/secom_trust.gif" alt="クリックして証明書の内容をご確認ください。" oncontextmenu="return false">
<input type="hidden" name="Req_ID" VALUE="{$form.gen_trust_code|escape}"/> </form>
</center>
{/if}

            </div>
         </div>
    </body>
</html>
