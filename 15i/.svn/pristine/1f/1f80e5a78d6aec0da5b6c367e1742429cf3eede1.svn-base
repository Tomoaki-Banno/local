<?php

class Partner_Subcontract_Report2 extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 印刷対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 6) == "check_") {
                $idArr[] = substr($name, 6, strlen($name) - 6);
            }
        }

        // 印刷対象データの取得
        $query = "
            select
                -- headers

                order_detail_subcontract.order_header_id as order_header_id

                -- details

                ,coalesce(t_received_customer.customer_name,'') as detail_外製指示_受注得意先名
                ,coalesce(t_received_customer_shipping.customer_name,'') as detail_外製指示_受注発送先名
                ,coalesce(t_received_item.item_code,'') as detail_外製指示_受注品目コード
                ,coalesce(t_received_item.item_name,'') as detail_外製指示_受注品目名

                ,t_child_item_master.item_code as detail_外製指示_子品目コード
                ,t_child_item_master.item_name as detail_外製指示_子品目名
                ,t_order_child_item.quantity as detail_外製指示_員数
                ,order_detail_subcontract.order_detail_quantity * t_order_child_item.quantity as detail_外製指示_子品目数
                ,t_child_item_master.payout_price as detail_外製指示_子品目支給単価
                ,t_child_item_master.measure as detail_外製指示_子品目単位
                ,t_child_item_master.spec as detail_外製指示_子品目仕様
                ,t_child_item_master.maker_name as detail_外製指示_子品目メーカー
                ,t_child_item_master.rack_no as detail_外製指示_子品目棚番
                ,t_child_item_group_master.item_group_code as detail_外製指示_子品目グループコード
                ,t_child_item_group_master.item_group_name as detail_外製指示_子品目グループ名
                ,t_child_item_master.comment as detail_外製指示_子品目備考1
                ,t_child_item_master.comment_2 as detail_外製指示_子品目備考2
                ,t_child_item_master.comment_3 as detail_外製指示_子品目備考3
                ,t_child_item_master.comment_4 as detail_外製指示_子品目備考4
                ,t_child_item_master.comment_5 as detail_外製指示_子品目備考5

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

                -- 子品目関係
                left join order_child_item as t_order_child_item on order_detail_subcontract.order_detail_id = t_order_child_item.order_detail_id
                left join item_master as t_child_item_master on t_order_child_item.child_item_id = t_child_item_master.item_id
                left join item_group_master as t_child_item_group_master on t_child_item_master.item_group_id = t_child_item_group_master.item_group_id
                left join (
                    select order_detail_id as oid, count(*) as order_child_count from order_child_item group by order_detail_id
                    ) as t_child_count on order_detail_subcontract.order_detail_id = t_child_count.oid

             where
                 order_header_subcontract.order_header_id in (" . join(",", $idArr) . ")
             order by
                -- テンプレート内の指定が優先されることに注意
                order_no, t_child_item_master.item_group_id, t_child_item_master.item_code
        ";

//echo($query);
        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("払出表");
        $info['report'] = "PartnerSubcontract2";
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
            array("●" . _g("払出表 明細（使用子品目関連）")),
            array("外製指示_子品目コード", _g("子品目の品目コード"), "code001"),
            array("外製指示_子品目名", _g("子品目の品目名"), _g("テスト品目")),
            array("外製指示_員数", _g("構成表マスタ [員数]"), "10"),
            array("外製指示_子品目数", _g("数量 × 員数"), "20000"),
            array("外製指示_子品目支給単価", _g("品目マスタ [支給単価]"), "100"),
            array("外製指示_子品目単位", _g("品目マスタ [単位]"), _g("個")),
            array("外製指示_子品目仕様", _g("品目マスタ [仕様]"), _g("テスト仕様")),
            array("外製指示_子品目メーカー", _g("品目マスタ [メーカー]"), _g("テストメーカー")),
            array("外製指示_子品目棚番", _g("品目マスタ [棚番]"), _g("テスト棚番")),
            array("外製指示_子品目グループコード", _g("品目グループマスタ [品目グループコード]"), "G001"),
            array("外製指示_子品目グループ名", _g("品目グループマスタ [品目グループ名]"), _g("品目グループ1")),
            array("外製指示_子品目備考1", _g("品目マスタ [備考1]"), _g("テスト子品目備考1")),
            array("外製指示_子品目備考2", _g("品目マスタ [備考2]"), _g("テスト子品目備考2")),
            array("外製指示_子品目備考3", _g("品目マスタ [備考3]"), _g("テスト子品目備考3")),
            array("外製指示_子品目備考4", _g("品目マスタ [備考4]"), _g("テスト子品目備考4")),
            array("外製指示_子品目備考5", _g("品目マスタ [備考5]"), _g("テスト子品目備考5")),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}