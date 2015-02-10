<?php

require_once("Model.class.php");

class Config_PasswordChange_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        if (isset($form['need'])) {
            // ログイン時にパスワード期限切れだったとき
            $this->listAction = $_SESSION["user_home_menu"];
        } else {
            $this->listAction = "Config_PasswordChange_Edit&afterEntry";
        }
        $this->errorAction = "Config_PasswordChange_Edit" . (isset($form['need']) ? "Need" : "");
        $this->modelName = "Config_PasswordChange_Model";

        $form['user_id'] = Gen_Auth::getCurrentUserId();
    }

    function setLogParam($form)
    {
        $this->log1 = _g("パスワード変更");
        $this->log2 = "[" . _g("ユーザー名") . "] " . @$form['user_name'];
        $this->afterEntryMessage = _g("パスワードを変更しました。");
    }

}