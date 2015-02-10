<!DOCTYPE html>
<html lang="ja">
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

<link rel="stylesheet" type="text/css" href="css/common.css">
<link rel="stylesheet" type="text/css" href="css/common{if $form.gen_iPad}_ipad{else}_pc{/if}.css">

{*************** Javascript ***************}
<script type="text/javascript">
{literal}    
$(function() {
    $('#loginUserId').focus();
});    
{/literal}    
</script>

<title>GENESISS Login</title>
</head>

<body>
<center>
<form METHOD="POST" ACTION="index.php?action=Config_PasswordChange_Entry&need">
    { gen_reload }
    <div style='height:50px'></div>
    Genesiss 15i
    <div style='height:30px'></div>
    <span style='color:blue'>{gen_tr}_g("パスワードの有効期限が切れています。新しいパスワードを設定してください。"){/gen_tr}</span>
    <div style='height:30px'></div>
    { gen_error errorList=$errorList }
    <table>
        <tr>
            <td>{gen_tr}_g("現在のパスワード"){/gen_tr}</td>
            <td><input type='password' size='12' name='now_password'></td>
            <td width="20px"></td>
            <td>{gen_tr}_g("新しいパスワード"){/gen_tr}</td>
            <td><input type='password' size='12' name='password'></td>
            <td width="20px"></td>
            <td>{gen_tr}_g("新しいパスワード（確認入力）"){/gen_tr}</td>
            <td><input type='password' size='12' name='password2'></td>
        </tr>
    </table>
    <div style='height:30px'></div>
    <input type='submit' style='width:150px' value='{gen_tr}_g("登録"){/gen_tr}'>
</form>
</center>
</body>
</HTML>