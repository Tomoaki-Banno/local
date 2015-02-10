<?php

require_once("Model.class.php");

class Stock_Move_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Stock_Move_List";
        $this->errorAction = "Stock_Move_Edit";
        $this->modelName = "Stock_Move_Model";
        $this->newRecordNotKeepField = array("item_seiban_location_lot", "seiban", "dist_location_id", "quantity", "order_detail_id", "remarks");

        // id を item_id, seiban, source_location_id, lot_id に変換。
        $form['item_id'] = "";
        $form['seiban'] = "";
        $form['source_location_id'] = "";
        $form['lot_id'] = "";

        $arr = explode("_", $form['item_seiban_location_lot']);
        if (count($arr) == 4) {
            if (is_numeric($arr[0]) && is_numeric($arr[2]) && is_numeric($arr[3])) {
                $form['item_id'] = $arr[0];
                $form['seiban'] = $arr[1];
                $form['source_location_id'] = $arr[2];
                $form['lot_id'] = $arr[3];
            }
        }
    }

    function setLogParam($form)
    {
        global $gen_db;

        $item_name = $gen_db->queryOneValue("select item_name from item_master where item_id = '{$form['item_id']}'");
        $source_name = $gen_db->queryOneValue("select location_name from location_master where location_id = '{$form['source_location_id']}'");
        $dist_name = $gen_db->queryOneValue("select location_name from location_master where location_id = '{$form['dist_location_id']}'");
        $msg = "[" . _g("品目") . "] {$item_name} [" . _g("製番") . "] {$form['seiban']} [" . _g("移動元") . "] {$source_name} [" . _g("移動先") . "] {$dist_name} [" . _g("数量") . "] {$form['quantity']}";
        if (isset($form['order_detail_id']) && is_numeric($form['order_detail_id'])) {
            $order_no = $gen_db->queryOneValue("select order_no from order_detail where order_detail_id = '{$form['order_detail_id']}'");
            $msg .= " [" . _g("オーダー番号") . "] {$order_no}";
        }

        $this->log1 = _g("ロケーション間移動登録");
        $this->log2 = $msg;
        $this->afterEntryMessage = _g("ロケーション間移動を登録しました。");
    }

}