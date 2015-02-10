<?php

class Logout
{

    function execute(&$form)
    {
        if (!$_SESSION['gen_app']) {
            if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] == -1) {
                $_SESSION["user_name"] = ADMIN_NAME;
            }
            if (isset($_SESSION["user_id"]) && isset($_SESSION["user_name"])) {
                Gen_Log::dataAccessLog(_g("ログアウト"), _g("ログアウト成功"), 'IP： ' . Gen_Auth::getRemoteIpAddress());
            }
        }

        $deviceToken = (isset($form['deviceToken']) ? $form['deviceToken'] : false);
        Gen_Auth::logout($deviceToken);

        header("Location:index.php");

        return 'login.tpl';
    }

}
