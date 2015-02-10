<?php

require_once("Model.class.php");

class Partner_Accepted_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $isEasyMode = @$form['easy_mode'];
        $this->listAction = $isEasyMode ? "Partner_Order_" . (@$form['return_to_list'] == "true" ? "List" : "Edit") : "Partner_Accepted_List";
        $this->errorAction = $isEasyMode ? "Partner_Order_List" : "Partner_Accepted_Edit";
        $this->modelName = "Partner_Accepted_Model";
        $this->entryMode = $isEasyMode ? "easy" : "";
        $this->newRecordNextAction = $isEasyMode ? $this->listAction : null;    // 簡易登録後はnewRecordNextActionに遷移
        $this->newRecordNotKeepField = array("order_number", "lot_no", "order_detail_id", "accepted_quantity", "accepted_price", "accepted_amount",
            "tax_rate", "foreign_currency_rate", "inspection_date", "payment_date", "use_by", "remarks", "order_detail_completed");
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['accepted_id']) && is_numeric($form['accepted_id'])) {
            $id = $form['accepted_id'];
        } else {
            $id = $gen_db->getSequence("accepted_accepted_id_seq");
        }

        $orderNo = "";
        $customer = "";
        $acceptedDate = "";
        $inspectionDate = "";
        if (is_numeric($id)) {
            $query = "
            select
                order_detail.order_no
                ,customer_master.customer_name
                ,accepted.accepted_date
                ,accepted.inspection_date
            from
                accepted
                left join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                left join order_header on order_detail.order_header_id = order_header.order_header_id
                left join customer_master on order_header.partner_id = customer_master.customer_id
            where
                accepted_id = '{$id}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            $orderNo = $obj->order_no;
            $customer = $obj->customer_name;
            $acceptedDate = $obj->accepted_date;
            $inspectionDate = $obj->inspection_date;
        }

        $this->log1 = _g("受入");
        $this->log2 = "[" . _g("オーダー番号") . "] {$orderNo}";
        $this->afterEntryMessage = sprintf(_g("オーダー番号 %s の受入を登録しました。"), $orderNo);

        // 通知メール
        $isNew = (!isset($form['accepted_id']) || !is_numeric($form['accepted_id']));
        $title = ($isNew ? _g("注文受入登録") : _g("注文受入修正"));
        $body = ($isNew ? _g("注文受入が新規登録されました。") : _g("注文受入が修正されました。")) . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("オーダー番号") . "] " . $orderNo . "\n"
                . "[" . _g("受入日") . "] " . $acceptedDate . "\n"
                . (isset($inspectionDate) && Gen_String::isDateString($inspectionDate) ? "[" . _g("検収日") . "] " . $inspectionDate . "\n" : "")
                . "[" . _g("発注先") . "] " . $customer . "\n"
                . "";
        Gen_Mail::sendAlertMail('partner_accepted_' . ($isNew ? "new" : "edit"), $title, $body);
    }

}