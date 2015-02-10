<?php

require_once("Model.class.php");

class Partner_Order_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Partner_Order_List";
        $this->errorAction = "Partner_Order_Edit";
        $this->modelName = "Partner_Order_Model";
        $this->isListMode = true;   // リスト形式の明細登録
        //
        // オーダー区分番号を取得（「注文書」を示す番号が返ってくる）
        $form['classification'] = Logic_Order::getOrderClass(true, null, null);
        $form['display'] = true;    // 画面からの登録であることを示すフラグ

        // 登録項目（ヘッダ）
        $this->headerArray = array(
            "classification",
            "order_header_id",
            "order_id_for_user",
            "order_date",
            "partner_id",
            "worker_id",
            "section_id",
            "delivery_partner_id",
            "remarks_header",
            "accepted_regist",
            "display", // 上で設定しているフラグ
        );

        // 登録項目（明細）
        $this->detailArray = array(
            // Modelプロパティ名を指定。$formのキーとしては、これに「_」+行番号 がついた形となる。
            // 最初の項目が行キー。（ここに値が入っている行が登録対象になる）
            "item_id",
            "order_detail_id",
            "order_no",
            "seiban",
            "item_id",
            "item_price",
            "item_sub_code",
            "order_detail_quantity",
            "order_detail_dead_line",
            "order_measure",
            "multiple_of_order_measure",
            "cost",
            "remarks",
        );
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['order_header_id']) && is_numeric($form['order_header_id'])) {
            $id = $form['order_header_id'];
        } else {
            $id = $gen_db->getSequence("order_header_order_header_id_seq");
        }

        $orderIdForUser = "";
        $date = "";
        $customer = "";
        if (is_numeric($id)) {
            $query = "
            select
                order_id_for_user
                ,order_date
                ,customer_name
            from
                order_header
                left join customer_master on order_header.partner_id = customer_master.customer_id
            where
                order_header_id = '{$id}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            $orderIdForUser = $obj->order_id_for_user;
            $date = $obj->order_date;
            $customer = $obj->customer_name;
        }

        $this->log1 = _g("注文書");
        $this->log2 = "[" . _g("注文書番号") . "] " . $orderIdForUser;
        $this->afterEntryMessage = sprintf(_g("注文書番号 %s を登録しました。"), $orderIdForUser);

        // 通知メール
        $isNew = (!isset($form['order_header_id']) || !is_numeric($form['order_header_id']));
        $title = ($isNew ? _g("注文書登録") : _g("注文書修正"));
        $body = ($isNew ? _g("注文書が新規登録されました。") : _g("注文書が修正されました。")) . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("注文書番号") . "] " . $orderIdForUser . "\n"
                . "[" . _g("注文日") . "] " . $date . "\n"
                . "[" . _g("発注先") . "] " . $customer . "\n"
                . "";
        Gen_Mail::sendAlertMail('partner_order_' . ($isNew ? "new" : "edit"), $title, $body);
    }

}