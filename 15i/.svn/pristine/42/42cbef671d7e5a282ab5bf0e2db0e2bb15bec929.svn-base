<?php

require_once("Model.class.php");

class Master_Section_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Section_List";
        $this->errorAction = "Master_Section_Edit";
        $this->modelName = "Master_Section_Model";
        $this->newRecordNotKeepField = array("section_code", "section_name", "remarks");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("部門");
        $this->log2 = "[" . _g("部門コード") . "] {$form['section_code']}";
        $this->afterEntryMessage = sprintf(_g("部門コード %s を登録しました。"), $form['section_code']);
    }

}
