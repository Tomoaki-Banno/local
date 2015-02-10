<?php

require_once("Model.class.php");

class Master_Customer_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Customer_List";
        $this->errorAction = "Master_Customer_Edit";
        $this->modelName = "Master_Customer_Model";
        $this->newRecordNotKeepField = array("customer_no", "customer_name", "bill_customer_id", "opening_balance", "opening_date", "credit_line", "payment_opening_balance", "payment_opening_date");

        $form['dropdown_flag'] = (@$form['gen_overlapFrame'] == "true" ? "true" : "false");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("取引先");
        $this->log2 = "[" . _g("取引先コード") . "] {$form['customer_no']}";
        $this->afterEntryMessage = sprintf(_g("取引先コード %s を登録しました。"), $form['customer_no']);
    }

}
