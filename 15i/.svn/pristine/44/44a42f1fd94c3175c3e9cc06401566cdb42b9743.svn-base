<?php

require_once("Model.class.php");

class Monthly_StockInput_BulkEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Monthly_StockInput_List";
        $this->newRecordNextAction = "Monthly_StockInput_List";
        $this->modelName = "Monthly_StockInput_Model";

        // 品目・製番・ロケ・ロットの各カラムを作る。
        // POSTされる実在庫数のidは「inventory_quantity_[itemId]_[locationId]_[lotId]_[seiban]」。
        // （seibanはtextなので_が入るかもしれないことに注意）
        // それを分解して取り出す。
        foreach ($form as $name => $value) {
            if (substr($name, 0, 19) == "inventory_quantity_") {
                $id = substr($name, 19);
                $arr = explode("_", $id);
                $form["item_id_$id"] = $arr[0];
                $form["location_id_$id"] = $arr[1];
                $form["lot_id_$id"] = $arr[2];
                unset($arr[0]);
                unset($arr[1]);
                unset($arr[2]);
                $form["seiban_$id"] = join('_', $arr);
            }
        }

        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "inventory_date",
        );
        // リスト項目（画面下部リスト）
        $this->detailArray = array(
            // 最初の項目がキー（ここに値が入っている行が登録対象になる）
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
        $this->log2 = "[" . _g("棚卸日") . "] " . $form['inventory_date'];
        $this->afterEntryMessage = _g("棚卸を登録しました。");
    }

}