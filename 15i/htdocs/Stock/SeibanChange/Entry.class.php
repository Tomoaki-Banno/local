<?php

require_once("Model.class.php");

class Stock_SeibanChange_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Stock_SeibanChange_List";
        $this->errorAction = "Stock_SeibanChange_Edit";
        $this->modelName = "Stock_SeibanChange_Model";
        // 15iで、引当元製番・引当先製番もクリアするようにした。
        // これらが残っていると、quantityをクリアしても登録後画面のJS処理で引当数量が自動的に再セットされる。
        // 引当数量がセットされていると、引当元製番を変更しても引当数量が自動的に変わらず不便。
        // 同じ製番の引当を連続登録することは少ないと思われるため、動作を変更した。
        // ag.cgi?page=ProjectDocView&pid=1574&did=226590
        $this->newRecordNotKeepField = array("item_seiban_location_lot", "dist_item_seiban_location_lot", "quantity");

        // id を item_id, seiban, source_location_id, lot_id に変換。
        $form['item_id'] = "";
        $form['source_seiban'] = "";
        $form['dist_seiban'] = "";
        $form['location_id'] = "";
        $form['lot_id'] = "";

        $arr = explode("_", $form['item_seiban_location_lot']);
        if (count($arr) == 4) {
            if (is_numeric($arr[0]) && is_numeric($arr[2]) && is_numeric($arr[3])) {
                $form['item_id'] = $arr[0];
                $form['source_seiban'] = $arr[1];
                $form['location_id'] = $arr[2];
                $form['lot_id'] = $arr[3];
            }
        }
        $arr = explode("_", $form['dist_item_seiban_location_lot']);
        if (count($arr) == 4) {
            if (is_numeric($arr[0]) && is_numeric($arr[2]) && is_numeric($arr[3])) {
                $form['dist_seiban'] = $arr[1];
            }
        }
    }

    function setLogParam($form)
    {
        $msg = "[" . _g("引当元製番") . "] " . $form['source_seiban'] . " [" . _g("引当先製番") . "] " . $form['dist_seiban'] . " [" . _g("数量") . "] " . $form['quantity'];

        $this->log1 = _g("製番引当登録");
        $this->log2 = $msg;
        $this->afterEntryMessage = _g("製番引当を登録しました。");
    }

}
