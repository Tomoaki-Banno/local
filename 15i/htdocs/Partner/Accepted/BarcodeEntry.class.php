<?php

require_once("Model.class.php");

define("LINE_COUNT_ENTRY", 20);   // BarcodeEditクラスと揃えること

class Partner_Accepted_BarcodeEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Partner_Accepted_BarcodeEdit";
        $this->newRecordNextAction = "Partner_Accepted_BarcodeEdit";
        $this->modelName = "Partner_Accepted_Model";
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

        for ($i = 1; $i <= LINE_COUNT_ENTRY; $i++) {
            $this->newRecordNotKeepField[] = "item_code_{$i}";
            $this->newRecordNotKeepField[] = "item_name_{$i}";
        }
    }

    function setLogParam($form)
    {
        $this->log1 = _g("受入");
        $this->logCategory = _g("バーコード登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("受入を登録しました。");

        // 通知メール
        $title = _g("注文受入登録");
        $body = _g("注文受入が新規登録（バーコード登録）されました。") . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("受入日") . "] " . $form['accepted_date'] . "\n"
                . (isset($form['inspection_date']) && Gen_String::isDateString($form['inspection_date']) ? "[" . _g("検収日") . "] " . $form['inspection_date'] . "\n" : "")
                . "";
        Gen_Mail::sendAlertMail('partner_accepted_new', $title, $body);
    }

}
