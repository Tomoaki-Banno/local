<?php

require_once("Model.class.php");

class Master_Currency_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Currency_List";
        $this->errorAction = "Master_Currency_Edit";
        $this->modelName = "Master_Currency_Model";
        $this->newRecordNotKeepField = array("currency_name");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("取引通貨");
        $this->log2 = "[" . _g("取引通貨") . "] {$form['currency_name']}";
        $this->afterEntryMessage = _g("取引通貨を登録しました。");
    }

}
