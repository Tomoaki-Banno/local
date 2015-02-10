<?php

class Partner_Order_Report extends Base_PDFReportBase
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

        // 明細モードのときはorder_detail_idが指定されているので、order_header_idに変換しておく
        if (@$form['detail'] == "true" && count($idArr) > 0) {
            $query = "select order_header_id from order_detail
                where order_detail_id in (" . join(",", $idArr) . ")
                group by order_header_id";
            $idArr2 = $gen_db->getArray($query);
            $idArr = array();
            foreach ($idArr2 as $row) {
                $idArr[] = $row['order_header_id'];
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

                order_header_partner.order_header_id

                ,t_total.total_amount as 注文_総合計金額
                ,t_total.total_tax as 注文_総合計消費税
                ,coalesce(t_total.total_amount,0) + coalesce(t_total.total_tax,0) as 注文_総税込金額
                
                /* 「gen_template」というカラムが存在すると、そのカラムで指定されているテンプレートが使用される */
                ,customer_master_partner.template_partner_order as gen_template
                
                -- details
                
                ,coalesce(t_received_customer.customer_name,'') as detail_注文_受注得意先名
                ,coalesce(t_received_customer_shipping.customer_name,'') as detail_注文_受注発送先名
                ,coalesce(t_received_item.item_code,'') as detail_注文_受注品目コード
                ,coalesce(t_received_item.item_name,'') as detail_注文_受注品目名

             from
                order_header as order_header_partner
                inner join order_detail as order_detail_partner on order_header_partner.order_header_id = order_detail_partner.order_header_id
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

                /* 総合計 計算用 */
                inner join (
                    select
                        order_header.order_header_id
                        ,sum(coalesce(case when order_detail.foreign_currency_id is null then order_detail.order_amount else order_detail.foreign_currency_order_amount end
                        -- 旧verデータ用
                        ,round((case when order_detail.foreign_currency_id is null then order_detail.item_price else order_detail.foreign_currency_item_price end)
                            * order_detail_quantity)
                            )) as total_amount
                        ,sum(coalesce(case when order_detail.foreign_currency_id is null then order_detail.order_tax else 0 end
                        -- 旧verデータ用
                        ,case when tax_class in (1) then 0 else round(round((case when order_detail.foreign_currency_id is null then order_detail.item_price else order_detail.foreign_currency_item_price end)
                            * order_detail_quantity) * " . Gen_Math::div($taxRate, 100) . ") end
                            )) as total_tax
                    from
                        order_header
                        inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                    where
                        order_header.order_header_id in (" . join(",", $idArr) . ")
                    group by
                        order_header.order_header_id
                    ) as t_total on order_header_partner.order_header_id = t_total.order_header_id

            where
                order_header_partner.order_header_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                order_header_partner.order_id_for_user, order_detail_partner.line_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        global $gen_db;

        $info = array();
        $info['reportTitle'] = _g("注文書");
        $info['report'] = "PartnerOrder";
        $info['pageKeyColumn'] = "order_header_id";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
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
            array("注文_受注品目コード", _g("受注品目コード"), _g("code01")),
            array("注文_受注品目名", _g("受注品目名"), _g("テスト受注品目")),
            array("注文_受注得意先名", _g("受注登録画面 [得意先]"), _g("得意先株式会社")),
            array("注文_受注発送先名", _g("受注登録画面 [発送先]"), _g("発送先株式会社")),
            array("●" . _g("注文書 ヘッダ（合計金額）")),
            array("注文_総合計金額", _g("金額の合計"), "200000"),
            array("注文_総合計消費税", _g("税額の合計"), "10000"),
            array("注文_総税込金額", _g("税込金額の合計"), "210000"),
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
