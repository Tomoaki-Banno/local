<?php

require_once("Model.class.php");

class Partner_Subcontract_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Partner_Subcontract_List";
        $this->errorAction = "Partner_Subcontract_Edit";
        $this->modelName = "Partner_Subcontract_Model";
        $this->newRecordNotKeepField = array("order_no", "order_detail_quantity", "item_price", "cost", "multiple_of_order_measure",
            "subcontract_parent_order_no", "subcontract_order_process_no", "subcontract_process_name", "subcontract_process_remarks_1",
            "subcontract_process_remarks_2", "subcontract_process_remarks_3", "subcontract_ship_to");
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['order_header_id']) && is_numeric($form['order_header_id'])) {
            $id = $form['order_header_id'];
        } else {
            $id = $gen_db->getSequence("order_header_order_header_id_seq");
        }

        if (is_numeric($id)) {
            $orderNo = $gen_db->queryOneValue("select order_no from order_detail where order_header_id = '{$id}'");
        }
        $this->log1 = _g("外製指示登録");
        $this->log2 = "[" . _g("オーダー番号") . "] {$orderNo}";
        $this->afterEntryMessage = sprintf(_g("オーダー番号 %s の外製指示を登録しました。"), $orderNo);
    }

}