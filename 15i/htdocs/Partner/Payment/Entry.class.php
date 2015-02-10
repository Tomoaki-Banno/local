<?php

require_once("Model.class.php");

class Partner_Payment_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Partner_Payment_List";
        $this->errorAction = "Partner_Payment_Edit";
        $this->modelName = "Partner_Payment_Model";
        if (!isset($form['payment_id'])) {
            $this->newRecordNotKeepField = array("customer_id");
            for ($i = 1; $i <= ROW_NUM; $i++) {
                $this->newRecordNotKeepField[] = "amount_{$i}";
                $this->newRecordNotKeepField[] = "adjust_amount_{$i}";
                $this->newRecordNotKeepField[] = "remarks_{$i}";
            }
        } else {
            $this->newRecordNotKeepField = array("customer_id", "amount", "adjust_amount", "remarks");
        }
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['customer_id'])) {
            $query = "select customer_no from customer_master where customer_id = '{$form['customer_id']}'";
            $no = $gen_db->queryOneValue($query);
        }

        $this->log1 = _g("支払");
        $this->log2 = "[" . _g("支払日") . "] {$form['payment_date']} [" . _g("発注先コード") . "] {$no}";
        $this->afterEntryMessage = _g("支払を登録しました。");
    }

}