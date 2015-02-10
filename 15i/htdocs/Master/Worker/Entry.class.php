<?php

require_once("Model.class.php");

class Master_Worker_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Worker_List";
        $this->errorAction = "Master_Worker_Edit";
        $this->modelName = "Master_Worker_Model";
        $this->newRecordNotKeepField = array("worker_code", "worker_name", "section_id", "remarks");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("従業員");
        $this->log2 = "[" . _g("従業員コード") . "] {$form['worker_code']}";
        $this->afterEntryMessage = sprintf(_g("従業員コード %s を登録しました。"), $form['worker_code']);
    }

}
