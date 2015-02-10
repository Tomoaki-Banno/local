<?php

class Partner_Subcontract_Report extends Base_PDFReportBase
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

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 消費税率（旧verデータ用）
        // 本来はSQL内でオーダー日に応じた税率を取得すべきだが、税額を保持していない旧verでのみ使用される値のため、
        // 手を抜いて本日時点の税額を取得している。
        $taxRate = Logic_Tax::getTaxRate(date('Y-m-d'));

        // 印刷対象データの取得
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $query = "
            select
                -- headers

                order_header_subcontract.order_header_id
                
                /* 「gen_template」というカラムが存在すると、そのカラムで指定されているテンプレートが使用される */
                ,customer_master_partner.template_subcontract as gen_template

                -- details
                
                ,coalesce(t_received_customer.customer_name,'') as detail_外製指示_受注得意先名
                ,coalesce(t_received_customer_shipping.customer_name,'') as detail_外製指示_受注発送先名
                ,coalesce(t_received_item.item_code,'') as detail_外製指示_受注品目コード
                ,coalesce(t_received_item.item_name,'') as detail_外製指示_受注品目名

             from
                order_header as order_header_subcontract
                inner join order_detail as order_detail_subcontract on order_header_subcontract.order_header_id = order_detail_subcontract.order_header_id
                left join customer_master as customer_master_partner on order_header_subcontract.partner_id = customer_master_partner.customer_id
                left join worker_master on order_header_subcontract.worker_id = worker_master.worker_id
                left join section_master on order_header_subcontract.section_id = section_master.section_id
            	left join currency_master on order_detail_subcontract.foreign_currency_id = currency_master.currency_id
                left join item_master on order_detail_subcontract.item_id = item_master.item_id
                " . self::getFromItemMasterChildren() . "
                /* " . self::getFromItemMasterProcess() . " */
                left join received_detail on order_detail_subcontract.seiban = received_detail.seiban
                left join received_header on received_detail.received_header_id = received_header.received_header_id
                left join customer_master as t_received_customer on received_header.customer_id = t_received_customer.customer_id
                left join customer_master as t_received_customer_shipping on received_header.delivery_customer_id = t_received_customer_shipping.customer_id
                left join item_master as t_received_item on received_detail.item_id = t_received_item.item_id

            where
                order_header_subcontract.order_header_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                order_header_subcontract.order_id_for_user, order_detail_subcontract.line_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("外製指示書");
        $info['report'] = "PartnerSubcontract";
        $info['pageKeyColumn'] = "order_header_id";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("order_header_subcontract", false, ""),
            array("order_detail_subcontract", true, ""),
            array("customer_master_partner", false, ""),
            array("worker_master", false, " (" . _g("自社担当者") . ")"),
            array("section_master", false, " (" . _g("自社部門") . ")"),
            array("currency_master", false, " (" . _g("取引通貨") . ")"),
            array("item_master", false, " (" . _g("外製指示品目。品目コード・品目名は上の「注文_」を使用することを推奨") . ")"),
            array("item_master_children", true, ""),    // 品目グループ・標準ロケ・標準手配先関連。SQL from に「self::getFromItemMasterChildren()」が必要
            // これ（とSQLのFROM句のgetFromItemMasterProcess）を有効にすれば品目マスタの工程関連のタグが有効になるが、
            // 製造指示書では登録時点の工程情報が order_process に記録され、そちらが優先されるため、ここは無効とする。
            //array("item_master_process", true, ""),    // 工程関連。SQL from に「self::getFromItemMasterProcess()」が必要。工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
            array("received_header", true, " (" . _g("外製指示品目が製番品目の場合のみ") . ")"),
            array("received_detail", true, " (" . _g("外製指示品目が製番品目の場合のみ") . ")"),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("受注明細（外製指示品目が製番品目の場合のみ）")),
            array("外製指示_受注品目コード", _g("受注品目コード（製番品目のみ）"), _g("code01")),
            array("外製指示_受注品目名", _g("受注品目名（製番品目のみ）"), _g("テスト受注品目")),
            array("外製指示_受注得意先名", _g("受注登録画面 [得意先]（製番品目のみ）"), _g("得意先株式会社")),
            array("外製指示_受注発送先名", _g("受注登録画面 [発送先]（製番品目のみ）"), _g("発送先株式会社")),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
        // 帳票発行済みフラグ
        Logic_Order::setOrderPrintedFlag($form['idArr'], true);
        return;
    }

}