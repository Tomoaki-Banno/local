<?php

require_once("Model.class.php");

class Stock_Move_BulkEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Stock_Move_BulkEdit";
        $this->newRecordNextAction = "Stock_Move_List";
        $this->nextAction = "Stock_Move_List";
        $this->modelName = "Stock_Move_Model";
        $this->entryMode = "bulk";

        // リストidから各データを生成
        $key = "item_seiban_location_lot_";
        foreach ($form as $name => $value) {
            if (preg_match("/{$key}/", $name) > 0) {
                $arr = explode("_", $value);
                if (count($arr) == 4) {
                    if (is_numeric($arr[0]) && is_numeric($arr[2]) && is_numeric($arr[3])) {
                        $form["item_id_{$value}"] = $arr[0];
                        $form["seiban_{$value}"] = $arr[1];
                        $form["source_location_id_{$value}"] = $arr[2];
                        $form["lot_id_{$value}"] = $arr[3];
                    }
                }
            }
        }

        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "order_detail_id",
            "move_date",
            "dist_location_id",
        );
        // リスト項目（画面下部リスト）
        $this->detailArray = array(
            // 最初の項目がキー（ここに値が入っている行が登録対象になる）
            "item_seiban_location_lot",
            "item_id",
            "seiban",
            "source_location_id",
            "lot",
            "quantity",
            "remarks",
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("ロケーション間移動登録");
        $this->logCategory = _g("一括登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("ロケーション間移動を登録しました。");
    }

}
