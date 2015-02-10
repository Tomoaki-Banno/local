<?php

require_once("Model.class.php");

class Manufacturing_Achievement_BulkEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Manufacturing_Achievement_BulkEdit";
        $this->newRecordNextAction = "Manufacturing_Achievement_List";
        $this->modelName = "Manufacturing_Achievement_Model";
        $this->entryMode = "bulk";

        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "achievement_id",
            "achievement_date",
            "location_id",
            "child_location_id",
            "worker_id",
            "section_id",
            "equip_id",
            "isZeroFinish",
        );
        // リスト項目（画面下部リスト）
        $this->detailArray = array(
            // 最初の項目がキー（ここに値が入っている行が登録対象になる）
            "order_process_no",
            "achievement_quantity",
            "lot_no",
            "use_lot_no",
            "order_detail_completed"
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("実績");
        $this->logCategory = _g("一括登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("製造実績を登録しました。");
    }

}