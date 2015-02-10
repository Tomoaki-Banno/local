<?php

class Partner_SubcontractAccepted_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        global $gen_db;

        // 印刷対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 12) == "print_check_") {
                $idArr[] = substr($name, 12, strlen($name) - 12);
            }
        }

        // 印刷対象データの取得
        $query = "
            select
                1 as dummy
                ,accepted.accepted_id
                ,coalesce(t_received_customer.customer_name,'') as detail_外製指示_受注得意先名
                ,coalesce(t_received_customer_shipping.customer_name,'') as detail_外製指示_受注発送先名
                ,coalesce(t_received_item.item_code,'') as detail_注文_受注品目コード
                ,coalesce(t_received_item.item_name,'') as detail_注文_受注品目名

             from
                accepted
                inner join order_detail as order_detail_partner on accepted.order_detail_id = order_detail_partner.order_detail_id
                inner join order_header as order_header_partner on order_detail_partner.order_header_id = order_header_partner.order_header_id
                left join customer_master as customer_master_partner on order_header_partner.partner_id = customer_master_partner.customer_id
                left join customer_master as customer_master_shipping on order_header_partner.delivery_partner_id = customer_master_shipping.customer_id
                left join worker_master on order_header_partner.worker_id = worker_master.worker_id
                left join section_master on order_header_partner.section_id = section_master.section_id
            	left join currency_master on order_detail_partner.foreign_currency_id = currency_master.currency_id
                left join item_master on order_detail_partner.item_id = item_master.item_id
                " . self::getFromItemMasterChildren() . "
                /* " . self::getFromItemMasterProcess() . " */
                left join received_detail on order_detail_partner.seiban = received_detail.seiban
                left join received_header on received_detail.received_header_id = received_header.received_header_id
                left join customer_master as t_received_customer on received_header.customer_id = t_received_customer.customer_id
                left join customer_master as t_received_customer_shipping on received_header.delivery_customer_id = t_received_customer_shipping.customer_id
                left join item_master as t_received_item on received_detail.item_id = t_received_item.item_id
            where
                accepted.accepted_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                accepted.accepted_date, customer_master_partner.customer_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        global $gen_db;

        $info = array();
        $info['reportTitle'] = _g("外製受入");
        $info['report'] = "PartnerSubcontractAccepted";
        $info['pageKeyColumn'] = "dummy";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("accepted", false, ""),
            array("order_header_partner", false, ""),
            array("order_detail_partner", true, ""),
            array("customer_master_partner", false, ""),
            array("customer_master_shipping", false, ""),
            array("worker_master", false, " (" . _g("自社担当者") . ")"),
            array("section_master", false, " (" . _g("自社部門") . ")"),
            array("currency_master", false, " (" . _g("取引通貨") . ")"),
            array("item_master", false, " (" . _g("注文品目。品目コード・品目名は上の「注文_」を使用することを推奨") . ")"),
            array("item_master_children", true, ""),    // 品目グループ・標準ロケ・標準手配先関連。SQL from に「self::getFromItemMasterChildren()」が必要
            // これ（とSQLのFROM句のgetFromItemMasterProcess）を有効にすれば品目マスタの工程関連のタグが有効になるが、
            // 製造指示書では登録時点の工程情報が order_process に記録され、そちらが優先されるため、ここは無効とする。
            //array("item_master_process", true, ""),    // 工程関連。SQL from に「self::getFromItemMasterProcess()」が必要。工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
            array("received_header", true, " (" . _g("注文品目が製番品目の場合のみ") . ")"),
            array("received_detail", true, " (" . _g("注文品目が製番品目の場合のみ") . ")"),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("受注明細（注文品目が製番品目の場合のみ）")),
            array("注文_受注品目コード", _g("受注品目コード（製番品目のみ）"), _g("code01")),
            array("注文_受注品目名", _g("受注品目名（製番品目のみ）"), _g("テスト受注品目")),
            array("注文_受注得意先名", _g("受注登録画面 [得意先]（製番品目のみ）"), _g("得意先株式会社")),
            array("注文_受注発送先名", _g("受注登録画面 [発送先]（製番品目のみ）"), _g("発送先株式会社")),
        );

        return $info;
    }
    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }
}
