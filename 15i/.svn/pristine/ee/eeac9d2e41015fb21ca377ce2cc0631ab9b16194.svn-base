<?php

require_once("Model.class.php");

class Master_Location_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Location_List";
        $this->errorAction = "Master_Location_Edit";
        $this->modelName = "Master_Location_Model";
        $this->newRecordNotKeepField = array("location_code", "location_name", "customer_id");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("ロケーション");
        $this->log2 = "[" . _g("ロケーションコード") . "] {$form['location_code']}";
        $this->afterEntryMessage = sprintf(_g("ロケーションコード %s を登録しました。"), $form['location_code']);
    }

}
