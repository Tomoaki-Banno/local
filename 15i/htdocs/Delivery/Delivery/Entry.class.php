<?php

require_once("Model.class.php");

class Delivery_Delivery_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Delivery_Delivery_List";
        $this->errorAction = "Delivery_Delivery_Edit";
        $this->modelName = "Delivery_Delivery_Model";
        $this->isListMode = true;   // リスト形式の明細登録
        $this->newRecordNotKeepField = array(
            "delivery_header_id",
            "received_header_id", // 受注コピー
            "delivery_no",
            "customer_id", // 取引先。この画面では自動セットされるので解除する必要がある
            "delivery_customer_id", // 発送先。この画面では自動セットされるので解除する必要がある
            "currency_name", // 取引通貨
            "foreign_currency_rate", // レート
            "remarks_header", // 備考1
            "remarks_header_2", // 備考2
            "remarks_header_3", // 備考3
        );

        // 以下は EditListがない場合は不要
        // 登録項目（ヘッダ部）
        $this->headerArray = array(
            "delivery_header_id",
            "delivery_no",
            "delivery_date",
            "inspection_date",
            "customer_id",
            "delivery_customer_id",
            "foreign_currency_rate",
            "person_in_charge",
            "remarks_header",
            "remarks_header_2",
            "remarks_header_3",
        );
        // 登録項目（EditList部）
        $this->detailArray = array(
            // 最初の項目が行キー。（ここに値が入っている行が登録対象になる）
            "received_detail_id",
            "delivery_detail_id",
            "delivery_quantity",
            "delivery_price",
            "tax_rate",
            "sales_base_cost",
            "location_id",
            "use_lot_no",
            "remarks",
            "delivery_completed",
        );
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['delivery_header_id']) && is_numeric($form['delivery_header_id'])) {
            $id = $form['delivery_header_id'];
        } else {
            $id = $gen_db->getSequence("delivery_header_delivery_header_id_seq");
        }

        $customer = "";
        $date = "";
        $deliveryNo = "";
        if (is_numeric($id)) {
            $query = "
            select
                customer_name
                ,delivery_date
                ,delivery_header.delivery_no as delivery_no
            from
                delivery_header
                left join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                left join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                left join received_header on received_detail.received_header_id = received_header.received_header_id
                left join customer_master on received_header.customer_id = customer_master.customer_id
            where
                delivery_header.delivery_header_id = '{$id}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            $customer = $obj->customer_name;
            $date = $obj->delivery_date;
            $deliveryNo = $obj->delivery_no;
        }

        $this->log1 = _g("納品");
        $this->log2 = "[" . _g("納品書番号") . "] " . $deliveryNo;
        $this->afterEntryMessage = sprintf(_g("納品書番号 %s を登録しました。"), $deliveryNo);

        // 通知メール
        $isNew = (!isset($form['delivery_header_id']) || !is_numeric($form['delivery_header_id']));
        $title = ($isNew ? _g("納品登録") : _g("納品修正"));
        $body = ($isNew ? _g("納品が新規登録されました。") : _g("納品が修正されました。")) . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("納品書番号") . "] " . $deliveryNo . "\n"
                . "[" . _g("納品日") . "] " . $date . "\n"
                . "[" . _g("得意先") . "] " . $customer . "\n"
                . "";
        Gen_Mail::sendAlertMail('delivery_delivery_' . ($isNew ? "new" : "edit"), $title, $body);
    }

}