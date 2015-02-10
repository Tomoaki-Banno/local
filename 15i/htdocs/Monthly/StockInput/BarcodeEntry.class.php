<?php

require_once("Model.class.php");

define("LINE_COUNT_ENTRY", 20);   // BarcodeEditクラスと揃えること

class Monthly_StockInput_BarcodeEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Monthly_StockInput_BarcodeEdit";
        $this->newRecordNextAction = "Monthly_StockInput_BarcodeEdit";
        $this->modelName = "Monthly_StockInput_Model";
        $this->entryMode = "barcode";
        $this->lineCount = LINE_COUNT_ENTRY;    // 1画面の行数
        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "inventory_date",
            "location_id",
        );
        // リスト項目（画面下部リスト）
        $this->detailArray = array(
            // 最初の項目がキー（ここに値が入っている行が登録対象になる）
            "item_code",
            "inventory_quantity",
            "seiban",
            "remarks",
            // ちなみに lot_id はModelの_setDefaultで設定
        );

        for ($i = 1; $i <= LINE_COUNT_ENTRY; $i++) {
            $this->newRecordNotKeepField[] = "item_code_{$i}";
            $this->newRecordNotKeepField[] = "item_name_{$i}";
            $this->newRecordNotKeepField[] = "lot_no_{$i}";
            $this->newRecordNotKeepField[] = "logical_stock_quantity_{$i}";
        }
    }

    function setLogParam($form)
    {
        $this->log1 = _g("棚卸");
        $this->logCategory = _g("バーコード登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("棚卸を登録しました。");
    }

}