<?php

require_once("Model.class.php");

class Delivery_PayingIn_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Delivery_PayingIn_List";
        $this->errorAction = "Delivery_PayingIn_Edit";
        $this->modelName = "Delivery_PayingIn_Model";
        if (!isset($form['paying_in_id'])) {
            $this->newRecordNotKeepField = array("customer_id");
            for ($i = 1; $i <= ROW_NUM; $i++) {
                $this->newRecordNotKeepField[] = "bill_header_id_{$i}";
                $this->newRecordNotKeepField[] = "bill_header_id_{$i}_show";
                $this->newRecordNotKeepField[] = "amount_{$i}";
                $this->newRecordNotKeepField[] = "remarks_{$i}";
            }
        } else {
            $this->newRecordNotKeepField = array("bill_header_id", "customer_id", "amount", "remarks");
        }
    }

    function setLogParam($form)
    {
        global $gen_db;

        $no = "";
        $name = "";
        if (isset($form['customer_id'])) {
            $query = "select customer_no, customer_name from customer_master where customer_id = '{$form['customer_id']}'";
            $obj = $gen_db->queryOneRowObject($query);
            $no = $obj->customer_no;
            $name = $obj->customer_name;
        }

        $this->log1 = _g("入金");
        $this->log2 = "[" . _g("入金日") . "] {$form['paying_in_date']} [" . _g("得意先コード") . "] $no";
        $this->afterEntryMessage = _g("入金を登録しました。");

        // 通知メール
        $isNew = (!isset($form['paying_in_id']) || !is_numeric($form['paying_in_id']));
        $title = ($isNew ? _g("入金登録") : _g("入金修正"));
        $body = ($isNew ? _g("入金が新規登録されました。") : _g("入金が修正されました。")) . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("得意先") . "] " . $name . "\n"
                . "";
        Gen_Mail::sendAlertMail('delivery_payingin_' . ($isNew ? "new" : "edit"), $title, $body);
    }

}