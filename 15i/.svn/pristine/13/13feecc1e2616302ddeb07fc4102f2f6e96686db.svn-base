<?php

require_once(BASE_DIR . "EntryBase.class.php");
require_once("Model.class.php");

class Master_CustomerGroup_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_CustomerGroup_List";
        $this->errorAction = "Master_CustomerGroup_Edit";
        $this->modelName = "Master_CustomerGroup_Model";
        $this->newRecordNotKeepField = array("customer_group_code", "customer_group_name");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("取引先グループ");
        $this->log2 = "[" . _g("取引先グループコード") . "] {$form['customer_group_code']}";
        $this->afterEntryMessage = sprintf(_g("取引先グループコード %s を登録しました。"), $form['customer_group_code']);
    }

}