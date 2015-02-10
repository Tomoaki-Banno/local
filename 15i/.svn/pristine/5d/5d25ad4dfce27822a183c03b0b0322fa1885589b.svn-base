<?php

require_once("Model.class.php");

define("LINE_COUNT_ENTRY", 20);   // BarcodeEditクラスと揃えること

class Partner_PartnerEdi_BarcodeEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Partner_PartnerEdi_BarcodeEdit";
        $this->newRecordNextAction = "Partner_PartnerEdi_BarcodeEdit";
        $this->modelName = "Partner_PartnerEdi_Model";
        $this->entryMode = "barcode";
        $this->lineCount = LINE_COUNT_ENTRY;    // 1画面の行数

        // 共通項目
        $this->headerArray = array(
            "accepted_date",
            "inspection_date",
            "location_id",
            "lot_id",
            "order_detail_completed",
        );
        // リスト項目
        $this->detailArray = array(
            // 最初の項目がキー（ここに値が入っている行が登録対象になる）
            "order_no",
            "accepted_quantity",
            "lot_no",
            "item_code",
            "item_name",
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("出荷");
        $this->logCategory = _g("バーコード登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("出荷を登録しました。");
    }

}
