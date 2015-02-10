<?php

require_once("Model.class.php");

define("LINE_COUNT_ENTRY", 20);   // BarcodeEditクラスと揃えること

class Delivery_Delivery_BarcodeEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Delivery_Delivery_BarcodeEdit";
        $this->newRecordNextAction = "Delivery_Delivery_BarcodeEdit";
        $this->modelName = "Delivery_Delivery_Model";
        $this->entryMode = "barcode";
        $this->lineCount = LINE_COUNT_ENTRY;    // 1画面の行数
        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "delivery_header_id",
            "delivery_date",
            "inspection_date",
            "location_id",
            "delivery_completed",
            "delivery_note_group",
        );
        // リスト項目
        $this->detailArray = array(
            // 最初の項目がキー（ここに値が入っている行が登録対象になる）
            "seiban",
            "use_lot_no",
            "delivery_quantity",
        );

        for ($i = 1; $i <= LINE_COUNT_ENTRY; $i++) {
            $this->newRecordNotKeepField[] = "item_code_{$i}";
            $this->newRecordNotKeepField[] = "item_name_{$i}";
        }
    }

    function setLogParam($form)
    {
        $this->log1 = _g("納品");
        $this->logCategory = _g("バーコード登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("納品を登録しました。");

        // 通知メール
        $title = _g("納品登録");
        $body = _g("納品が新規登録（バーコード登録）されました。") . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("納品日") . "] " . $form['delivery_date'] . "\n"
                . "";
        Gen_Mail::sendAlertMail('delivery_delivery_new', $title, $body);
    }

}
