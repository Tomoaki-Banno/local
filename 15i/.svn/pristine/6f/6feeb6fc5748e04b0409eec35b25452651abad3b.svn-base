<?php

require_once("Model.class.php");

class Manufacturing_Received_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        $this->listAction = "Manufacturing_Received_Edit";
        $this->modelName = "Manufacturing_Received_Model";
        $this->isListMode = true;   // リスト形式の明細登録
        $this->newRecordNotKeepField = array(
            "received_number",
            "estimate_header_id"
        );

        // 登録項目（ヘッダ）
        $this->headerArray = array(
            "received_header_id",
            "received_number",
            "estimate_header_id",
            "customer_received_number",
            "customer_id",
            "delivery_customer_id",
            "received_date",
            "worker_id",
            "section_id",
            "guarantee_grade",
            "remarks_header",
            "remarks_header_2",
            "remarks_header_3",
            "delivery_regist",
        );

        // 登録項目（明細）
        $this->detailArray = array(
            // Modelプロパティ名を指定。$formのキーとしては、これに「_」+行番号 がついた形となる。
            // 最初の項目が行キー。（ここに値が入っている行が登録対象になる）
            "item_id",
            "received_detail_id",
            "received_quantity",
            "product_price",
            "dead_line",
            "remarks",
            "remarks_2",
            "seiban",
            "reserve_quantity",
            "sales_base_cost",
        );
    }

    function afterLogic(&$form, $isNew)
    {
        global $gen_db;

        // 「同時に納品を登録する」および「同時に納品書を印刷する」チェックボックスがオンのときは、次画面で納品書を印刷する。
        // 登録IDを取得するため、ここ（afterLogic）でおこなう必要がある。
        if (isset($form['delivery_regist']) && $form['delivery_regist'] == "true"
                && isset($form['delivery_regist_print']) && $form['delivery_regist_print'] == "true") {
            $id = $gen_db->getSequence('delivery_header_delivery_header_id_seq');
            // 帳票印刷指定。新規のときはeditmodal.tplのonloadで、修正のときはmodalclose.tpl経由gen_modal.jsで処理される。
            //  ※この$form['gen_nextPageReport_noEscape']による帳票印刷機能は、標準で帳票印刷の機能がある画面（Editに「登録して印刷」ボタンが
            //      表示される画面。つまり Editクラスで $form['gen_reportArray'] が設定されている画面）では使えない。
            //      フレームワークもこのフラグを使用しているため。

            $form['gen_nextPageReport_noEscape'] = "Delivery_Delivery_Report&check_{$id}";
        }
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['received_header_id']) && is_numeric($form['received_header_id'])) {
            $id = $form['received_header_id'];
        } else {
            $id = $gen_db->getSequence("received_header_received_header_id_seq");
        }

        $recNum = "";
        $customer = "";
        $date = "";
        if (is_numeric($id)) {
            $query = "
            select
                received_number
                ,customer_name
                ,received_date
            from
                received_header
                left join customer_master on received_header.customer_id = customer_master.customer_id
            where
                received_header_id = '{$id}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            $recNum = $obj->received_number;
            $customer = $obj->customer_name;
            $date = $obj->received_date;
        }

        $this->log1 = _g("受注");
        $this->log2 = "[" . _g("受注番号") . "] {$recNum}";
        $this->afterEntryMessage = sprintf(_g("受注番号 %s を登録しました。"), $recNum);

        if (isset($form['delivery_regist']) && $form['delivery_regist'] == "true"
                && isset($this->model->_deliveryHeaderId) && is_numeric($this->model->_deliveryHeaderId)) {
            $query = "select delivery_no from delivery_header where delivery_header_id = '{$this->model->_deliveryHeaderId}'";
            $deliveryNo = urlencode(h($gen_db->queryOneValue($query)));
            $link = "
                <script type=\"text/javascript\">
                function showDelivery() {
                    var url = 'index.php?action=Delivery_Delivery_List';
                    url += '&gen_search_delivery_no={$deliveryNo}';
                    url += '&gen_search_delivery_date_from=';
                    window.open(url);
                }
                </script>
                <a href='javascript:showDelivery()'>{$deliveryNo}</a>
            ";
            $this->afterEntryMessage_noEscape .= " " . sprintf(_g("（納品書番号 %s を登録しました。）"), $link);
        }

        // 通知メール
        $isNew = (!isset($form['received_header_id']) || !is_numeric($form['received_header_id']));
        $title = ($isNew ? _g("受注登録") : _g("受注修正"));
        $body = ($isNew ? _g("受注が新規登録されました。") : _g("受注が修正されました。")) . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("受注番号") . "] " . $recNum . "\n"
                . "[" . _g("受注日") . "] " . $date . "\n"
                . "[" . _g("得意先") . "] " . $customer . "\n"
                . "";
        Gen_Mail::sendAlertMail('manufacturing_received_' . ($isNew ? "new" : "edit"), $title, $body);
    }

}
