<?php

require_once("Model.class.php");

class Master_Waster_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Waster_List";
        $this->errorAction = "Master_Waster_Edit";
        $this->modelName = "Master_Waster_Model";
        $this->newRecordNotKeepField = array("waster_code", "waster_name", "remarks");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("不適合理由");
        $this->log2 = "[" . _g("不適合理由コード") . "] {$form['waster_code']}";
        $this->afterEntryMessage = sprintf(_g("不適合理由コード %s を登録しました。"), $form['waster_code']);
    }

}
