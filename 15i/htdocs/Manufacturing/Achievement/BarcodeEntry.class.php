<?php

require_once("Model.class.php");

define("LINE_COUNT_ENTRY", 20);   // BarcodeEditクラスと揃えること

class Manufacturing_Achievement_BarcodeEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Manufacturing_Achievement_BarcodeEdit";
        $this->newRecordNextAction = "Manufacturing_Achievement_BarcodeEdit";
        $this->modelName = "Manufacturing_Achievement_Model";
        $this->entryMode = "barcode";
        $this->lineCount = LINE_COUNT_ENTRY;    // 1画面の行数
        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "achievement_id",
            "achievement_date",
            "location_id",
            "child_location_id",
            "worker_id",
            "section_id",
            "equip_id",
            "accept_completed",
        );
        // リスト項目（画面下部リスト）
        $this->detailArray = array(
            // 最初の項目がキー（ここに値が入っている行が登録対象になる）
            "order_process_no",
            "achievement_quantity",
            "work_minute",
            "waster_id_1",
            "waster_quantity_1",
            "lot_no",
            "use_lot_no",
            "remarks",
        );

        for ($i = 1; $i <= LINE_COUNT_ENTRY; $i++) {
            $this->newRecordNotKeepField[] = "item_code_{$i}";
            $this->newRecordNotKeepField[] = "item_name_{$i}";
            $this->newRecordNotKeepField[] = "process_name_{$i}";
        }
    }

    function setLogParam($form)
    {
        $this->log1 = _g("実績");
        $this->logCategory = _g("バーコード登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("製造実績を登録しました。");
    }

}