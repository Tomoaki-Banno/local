<?php

require_once("Model.class.php");

class Master_Rate_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Rate_List";
        $this->errorAction = "Master_Rate_Edit";
        $this->modelName = "Master_Rate_Model";
        $this->newRecordNotKeepField = array("rate", "remarks");
    }

    function setLogParam($form)
    {
        global $gen_db;

        $this->log1 = _g("為替レート");
        $currency = $gen_db->queryOneValue("select currency_name from currency_master where currency_id = '{$form['currency_id']}'");
        $this->log2 = "[" . _g("取引通貨") . "] $currency [" . _g("適用開始日") . "] {$form['rate_date']} [" . _g("レート") . "] {$form['rate']}";
        $this->afterEntryMessage = _g("為替レートを登録しました。");
    }

}
