<?php

// 期限切れによるパスワード変更。
// このクラスは仮ログイン状態であってもアクセスを許可されている（Gen_Auth::sessionCheck()）。

class Config_PasswordChange_EditNeed
{
    function execute(&$form)
    {
        if (isset($form['afterEntry'])) {
            return 'action:' . $_SESSION["user_home_menu"];
        }
        return 'config_passwordchange_editneed.tpl';
    }
}
