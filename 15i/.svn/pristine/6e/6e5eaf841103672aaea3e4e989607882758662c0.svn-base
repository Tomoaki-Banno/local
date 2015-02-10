<?php

class Menu_Admin
{

    function execute(&$form)
    {
        $form['gen_pageTitle'] = _g("admin専用");

        // 管理者ユーザーのみ
        if ($_SESSION["user_id"] != "-1") {
            throw new Exception();
        }

        // 製品版チェック機能
        if (GEN_SERVER_INFO_CLASS == 10)
            $form['gen_isServerInfo'] = "true";

        return 'menu_admin.tpl';
    }
}