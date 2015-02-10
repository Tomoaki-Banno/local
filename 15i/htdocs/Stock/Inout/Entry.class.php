<?php

require_once("Model.class.php");

class Stock_Inout_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Stock_Inout_List";
        $this->errorAction = "Stock_Inout_Edit";
        $this->modelName = "Stock_Inout_Model";
        $this->newRecordNotKeepField = array("seiban", "item_in_out_quantity");
    }

    function setLogParam($form)
    {
        global $gen_db;

        $query = "select item_code from item_master where item_id = '{$form['item_id']}'";
        $itemCode = $gen_db->queryOneValue($query);
        $cat = str_replace(_g("登録"), "", Logic_Inout::classificationToTitle($form['classification']));

        $this->log1 = str_replace(_g("登録"), "", Logic_Inout::classificationToTitle($form['classification']));
        $this->log2 = "[" . _g("日付") . "] " . $form['item_in_out_date'] . " [" . _g("品目コード") . "] " . $itemCode;
        $this->afterEntryMessage = sprintf(_g("品目コード %s の %s を登録しました。"), $itemCode, $cat);
    }

}