<?php

require_once("Model.class.php");

class Manufacturing_Estimate_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Manufacturing_Estimate_Edit";
        $this->modelName = "Manufacturing_Estimate_Model";
        $this->isListMode = true;   // リスト形式の明細登録
        $this->newRecordNotKeepField = array("estimate_header_id");

        // 以下は EditListがない場合は不要
        // 登録項目（ヘッダ）
        $this->headerArray = array(
            "estimate_header_id",
            "estimate_number",
            "estimate_date",
            "customer_id",
            "customer_name",
            "person_in_charge",
            "customer_zip",
            "customer_address1",
            "customer_address2",
            "customer_tel",
            "customer_fax",
            "subject",
            "delivery_date",
            "delivery_place",
            "mode_of_dealing",
            "expire_date",
            "worker_id",
            "section_id",
            "estimate_rank",
            "remarks",
        );

        // 登録項目（明細）
        $this->detailArray = array(
            // Modelプロパティ名を指定。$formのキーとしては、これに「_」+行番号 がついた形となる。
            // 最初の項目が行キー。（ここに値が入っている行が登録対象になる）
            "item_name",
            "estimate_detail_id",
            "item_id", // 登録はされないが、項目消去のために
            "item_code",
            "measure",
            "quantity",
            "sale_price",
            "base_cost",
            "tax_class",
            "remarks_detail",
            "remarks_detail_2",
        );
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['estimate_header_id']) && is_numeric(($form['estimate_header_id']))) {
            $id = $form['estimate_header_id'];
        } else {
            global $gen_db;
            $id = $gen_db->getSequence("estimate_header_estimate_header_id_seq");
        }

        if (is_numeric($id)) {
            $query = "
            select
                estimate_number
                ,customer_name
                ,estimate_date
            from
                estimate_header
            where
                estimate_header_id = '{$id}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            $num = $obj->estimate_number;
            $customer = $obj->customer_name;
            $date = $obj->estimate_date;
        }

        $this->log1 = _g("見積書");
        $this->log2 = "[" . _g("見積番号") . "] {$num}";
        $this->afterEntryMessage = sprintf(_g("見積番号 %s の見積書を登録しました。"), $num);

        // 通知メール（新規登録時のみ）
        $isNew = (!isset($form['estimate_header_id']) || !is_numeric($form['estimate_header_id']));
        $title = ($isNew ? _g("見積書登録") : _g("見積書修正"));
        $body = ($isNew ? _g("見積書が新規登録されました。") : _g("見積書が修正されました。")) . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("見積番号") . "] " . $num . "\n"
                . "[" . _g("発行日") . "] " . $date . "\n"
                . "[" . _g("得意先") . "] " . $customer . "\n"
                . "";
        Gen_Mail::sendAlertMail('manufacturing_estimate_' . ($isNew ? "new" : "edit"), $title, $body);
    }

}
