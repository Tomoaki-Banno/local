<?php

require_once("Model.class.php");

class Master_Equip_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Equip_List";
        $this->errorAction = "Master_Equip_Edit";
        $this->modelName = "Master_Equip_Model";
        $this->newRecordNotKeepField = array("equip_code", "equip_name", "remarks");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("設備");
        $this->log2 = "[" . _g("設備コード") . "] {$form['equip_code']}";
        $this->afterEntryMessage = sprintf(_g("設備コード %s を登録しました。"), $form['equip_code']);
    }

}
