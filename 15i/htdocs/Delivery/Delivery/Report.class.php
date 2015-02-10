<?php

class Delivery_Delivery_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        global $gen_db;

        // 印刷対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 6) == "check_") {
                $idArr[] = substr($name, 6, strlen($name) - 6);
            }
        }
        // 明細モードのときはdetail_idが指定されているので、header_idに変換しておく
        if (@$form['detail'] == "true" && count($idArr) > 0) {
            $query = "select delivery_header_id from delivery_detail
                where delivery_detail_id in (" . join(",", $idArr) . ")
                group by delivery_header_id";
            $idArr2 = $gen_db->getArray($query);
            $idArr = array();
            foreach ($idArr2 as $row) {
                $idArr[] = $row['delivery_header_id'];
            }
        }

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 印刷対象データの取得
        $idCsv = join(',', $idArr);
        $query = "
            select
                -- headers

                delivery_header.delivery_header_id

                ,case when delivery_header.foreign_currency_id is null then delivery_header.delivery_note_amount else delivery_header.foreign_currency_delivery_note_amount end as 納品_総合計金額
                ,(case when delivery_header.tax_category in (1,2) then
                    coalesce(case when delivery_header.foreign_currency_id is null then delivery_header.delivery_note_tax
                        else delivery_header.foreign_currency_delivery_note_tax end,0) end) as 納品_総合計消費税
                ,(case when delivery_header.tax_category in (1,2) then
                    coalesce(case when delivery_header.foreign_currency_id is null then delivery_header.delivery_note_amount + delivery_header.delivery_note_tax
                        else foreign_currency_delivery_note_amount + delivery_header.foreign_currency_delivery_note_tax end,0) end) as 納品_総税込金額
               
                /* 「gen_template」というカラムが存在すると、そのカラムで指定されているテンプレートが使用される */
                ,customer_master.template_delivery as gen_template

            from
                delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                inner join received_header on received_detail.received_header_id = received_header.received_header_id
                left join estimate_header on received_header.estimate_header_id = estimate_header.estimate_header_id
                left join item_master on received_detail.item_id = item_master.item_id
                " . self::getFromItemMasterChildren() . "
                /* " . self::getFromItemMasterProcess() . " */
                left join customer_master on delivery_header.customer_id = customer_master.customer_id
                left join customer_master as customer_master_shipping on delivery_header.delivery_customer_id = customer_master_shipping.customer_id
                left join customer_master as customer_master_bill on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = customer_master_bill.customer_id
                left join currency_master on delivery_header.foreign_currency_id = currency_master.currency_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join section_master on received_header.section_id = section_master.section_id
            where
                delivery_header.delivery_header_id in ({$idCsv})
            order by
                -- テンプレート内の指定が優先されることに注意
                delivery_header_id, delivery_detail.line_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        global $gen_db;

        $info = array();
        $info['reportTitle'] = _g("納品書");
        $info['report'] = "Delivery";
        $info['pageKeyColumn'] = "delivery_header_id";
        
        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("delivery_header", false, ""),
            array("delivery_detail", true, ""),

            array("customer_master", false, ""),
            array("customer_master_shipping", false, ""),
            array("customer_master_bill", false, ""),   // 納品明細の消費税タグ用
            
            array("received_header", true, ""),
            array("received_detail", true, ""),
            array("estimate_header", true, " (" . _g("受注が見積から転記された場合") . ")"),
            array("item_master", true, ""),
            array("item_master_children", true, ""),    // 品目グループ・標準ロケ・標準手配先関連。SQL from に「self::getFromItemMasterChildren()」が必要
            // パフォーマンス上の理由でコメントアウト
            //array("item_master_process", true, ""),    // 工程関連。SQL from に「self::getFromItemMasterProcess()」が必要。工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
            array("currency_master", true, " (" . _g("受注取引通貨") . ")"),
            array("worker_master", true, " (" . _g("受注担当者") . ")"),
            array("section_master", true, " (" . _g("受注部門") . ")"),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("納品ヘッダ（合計金額）")),
            array("納品_総合計金額", _g("金額の合計"), "200000"),
            array("納品_総合計消費税", _g("税額の合計"), "10000"),
            array("納品_総税込金額", _g("税込金額の合計"), "210000"),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
        // 帳票発行済みフラグ
        Logic_Delivery::setDeliveryPrintedFlag($form['idArr'], true);
        return;
    }

}