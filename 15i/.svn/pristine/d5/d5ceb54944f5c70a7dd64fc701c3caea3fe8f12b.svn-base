<?php

require_once("Model.class.php");

class Partner_SubcontractAccepted_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $isEasyMode = @$form['easy_mode'];
        $this->listAction = $isEasyMode ? "Partner_Subcontract_List" : "Partner_SubcontractAccepted_List";
        $this->errorAction = $isEasyMode ? "Partner_Subcontract_List" : "Partner_SubcontractAccepted_Edit";
        $this->modelName = "Partner_SubcontractAccepted_Model";
        $this->entryMode = $isEasyMode ? "easy" : "";
        $this->newRecordNextAction = $isEasyMode ? $this->listAction : null;    // 簡易登録後はnewRecordNextActionに遷移
        $this->newRecordNotKeepField = array("order_no", "order_detail_id", "lot_no", "use_by", "accepted_quantity", "accepted_price", "accepted_amount",
            "tax_rate", "foreign_currency_rate", "inspection_date", "payment_date", "subcontract_parent_order_no", "subcontract_process_name",
            "subcontract_process_remarks_1", "subcontract_process_remarks_2", "subcontract_process_remarks_3",
            "subcontract_ship_to", "remarks", "order_detail_completed");
    }

    function setLogParam($form)
    {
        global $gen_db;

        $orderNo = "";
        if (is_numeric($form['order_detail_id'])) {
            $orderNo = $gen_db->queryOneValue("select order_no from order_detail where order_detail_id = '{$form['order_detail_id']}'");
        }

        $this->log1 = _g("外製受入登録");
        $this->log2 = "[" . _g("オーダー番号") . "] {$orderNo}";
        $this->afterEntryMessage = sprintf(_g("オーダー番号 %s の外製受入を登録しました。"), $orderNo);
    }

}