<?php

require_once("Model.class.php");

class Master_Process_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Process_List";
        $this->errorAction = "Master_Process_Edit";
        $this->modelName = "Master_Process_Model";
        $this->newRecordNotKeepField = array("process_code", "process_name", "equipment_name", "default_lead_time");

        // 登録項目
        $this->columnArray = array(
            'process_id',
            'process_code',
            'process_name',
            'equipment_name',
            'default_lead_time',
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("工程");
        $this->log2 = "[" . _g("工程コード") . "] {$form['process_code']}";
        $this->afterEntryMessage = sprintf(_g("工程コード %s を登録しました。"), $form['process_code']);
    }

}
