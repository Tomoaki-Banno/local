<?php

require_once("Model.class.php");

class Partner_PartnerEdi_BulkEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Partner_PartnerEdi_BulkEdit";
        $this->newRecordNextAction = "Partner_PartnerEdi_List";
        $this->nextAction = "Partner_PartnerEdi_List";
        $this->modelName = "Partner_PartnerEdi_Model";
        $this->entryMode = "bulk";

        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "accepted_date",
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
        $this->log1 = _g("出荷");
        $this->logCategory = _g("一括出荷登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("出荷を登録しました。");
    }

}
