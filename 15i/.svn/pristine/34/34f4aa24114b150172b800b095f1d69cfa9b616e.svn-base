<?php
require_once(APP_DIR . "/Monthly/StockInput/Model.class.php");

class Mobile_StockInput_Entry extends Base_EntryBase
{
    function setParam(&$form)
    {
        global $gen_db;

        // 基本パラメータ
        $this->errorAction = "Mobile_StockInput_Edit";
        $this->newRecordNextAction = "Mobile_StockInput_Edit";
        $this->modelName = "Monthly_StockInput_Model";  // 流用
        $this->newRecordNotKeepField = array(
            "item_code",
        );

        if (isset($form['item_code'])) {
            $form['item_id'] = $gen_db->queryOneValue("select item_id from item_master where item_code = '{$form['item_code']}'");
        } else {
            $form['item_id'] = '';
        }
        $form['lot_id'] = "0";
        $form['seiban'] = "";

        // 登録項目
        $this->headerArray = array(
            "inventory_date",
            "item_id",
            "seiban",
            "location_id",
            "lot_id",
            "inventory_quantity",
            "remarks",
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("棚卸登録");
        $this->logCategory = _g("登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("棚卸を登録しました。");
    }
}