<?php

require_once("Model.class.php");

class Master_TaxRate_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_TaxRate_List";
        $this->errorAction = "Master_TaxRate_Edit";
        $this->modelName = "Master_TaxRate_Model";
        $this->newRecordNotKeepField = array("apply_date", "tax_rate");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("消費税率");
        $this->log2 = "[" . _g("適用開始日") . "] {$form['apply_date']} [" . _g("税率") . "(％)" . "] {$form['tax_rate']}";
        $this->afterEntryMessage = _g("消費税率を登録しました。");
    }

}
