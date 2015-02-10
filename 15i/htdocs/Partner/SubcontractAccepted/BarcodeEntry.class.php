<?php

require_once("Model.class.php");

define("LINE_COUNT_ENTRY", 20);   // BarcodeEditクラスと揃えること

class Partner_SubcontractAccepted_BarcodeEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Partner_SubcontractAccepted_BarcodeEdit";
        $this->newRecordNextAction = "Partner_SubcontractAccepted_BarcodeEdit";
        $this->modelName = "Partner_SubcontractAccepted_Model";
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
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("外製受入登録");
        $this->logCategory = _g("バーコード登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("外製受入を登録しました。");
    }

}
