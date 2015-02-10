<?php

require_once("Model.class.php");

class Master_User_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_User_List";
        $this->errorAction = "Master_User_Edit";
        $this->modelName = "Master_User_Model";
        $this->newRecordNotKeepField = array("login_user_id", "user_name", "password", "password2", "customer_id");

        // POSTされた内容（$form）を、Modelのformプロパティにそのまま入れるための処理。
        // どんなクラスグループがPOSTされるかわからないため、POSTの内容をそのままModelに渡し、
        // Model側で処理するようにしている。
        $form['form'] = $form;
    }

    function setLogParam($form)
    {
        $this->log1 = _g("ユーザー");
        $this->log2 = "[" . _g("ユーザー") . "] {$form['user_name']}";
        $this->afterEntryMessage = sprintf(_g("ユーザー %s を登録しました。"), $form['user_name']);
    }

}
