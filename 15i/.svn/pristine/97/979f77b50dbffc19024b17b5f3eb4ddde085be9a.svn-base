<?php

require_once("Model.class.php");

class Partner_SubcontractAccepted_BulkEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Partner_SubcontractAccepted_BulkEdit";
        $this->newRecordNextAction = "Partner_SubcontractAccepted_List";
        $this->nextAction = "Partner_SubcontractAccepted_List";
        $this->modelName = "Partner_SubcontractAccepted_Model";
        $this->entryMode = "bulk";

        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "accepted_date",
            "inspection_date",
            "location_id",
            "isZeroFinish",
        );
        // リスト項目（画面下部リスト）
        $this->detailArray = array(
            // 最初の項目がキー（ここに値が入っている行が登録対象になる）
            "order_detail_id",
            "accepted_quantity",
            "lot_no",
            "order_detail_completed"
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("外製受入登録");
        $this->logCategory = _g("一括登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("外製受入を登録しました。");
    }

}
