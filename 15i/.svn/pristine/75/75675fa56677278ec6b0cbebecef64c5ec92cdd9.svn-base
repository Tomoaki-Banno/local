<?php

require_once("Model.class.php");

class Master_CustomerPrice_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_CustomerPrice_List";
        $this->errorAction = "Master_CustomerPrice_Edit";
        $this->modelName = "Master_CustomerPrice_Model";
        $this->newRecordNotKeepField = array("item_id", "selling_price");
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['customer_id'])) {
            $customer_no = $gen_db->queryOneValue("select customer_no from customer_master where customer_id = '{$form['customer_id']}'");
        }
        if (isset($form['item_id'])) {
            $item_code = $gen_db->queryOneValue("select item_code from item_master where item_id = '{$form['item_id']}'");
        }

        $this->log1 = _g("得意先販売価格");
        $this->log2 = "[" . _g("得意先コード") . "] $customer_no [" . _g("品目コード") . "] $item_code [" . _g("販売価格") . "] " . $form['selling_price'];
        $this->afterEntryMessage = _g("得意先販売価格 を登録しました。");
    }

}
