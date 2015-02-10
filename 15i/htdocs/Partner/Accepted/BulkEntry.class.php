<?php

require_once("Model.class.php");

class Partner_Accepted_BulkEntry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->errorAction = "Partner_Accepted_BulkEdit";
        $this->newRecordNextAction = "Partner_Accepted_List";
        $this->nextAction = "Partner_Accepted_List";
        $this->modelName = "Partner_Accepted_Model";
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
        $this->log1 = _g("受入");
        $this->logCategory = _g("一括登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("受入を登録しました。");

        // 通知メール
        $title = _g("注文受入登録");
        $body = _g("注文受入が新規登録（一括登録）されました。") . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("受入日") . "] " . $form['accepted_date'] . "\n"
                . (isset($form['inspection_date']) && Gen_String::isDateString($form['inspection_date']) ? "[" . _g("検収日") . "] " . $form['inspection_date'] . "\n" : "")
                . "";
        Gen_Mail::sendAlertMail('partner_accepted_new', $title, $body);
    }

}
