<?php

class Manufacturing_Received_Report extends Base_PDFReportBase
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
        // ヘッダモードのときはheader_idが指定されているので、detail_idに変換しておく
        if (@$form['detail'] != "true" && count($idArr) > 0) {
            $query = "
                select
                    received_detail_id
                from
                    received_detail
                where
                    received_header_id in (" . join(",", $idArr) . ")
            ";
            $idArr2 = $gen_db->getArray($query);
            $idArr = array();
            foreach ($idArr2 as $row) {
                $idArr[] = $row['received_detail_id'];
            }
        }

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 印刷対象データの取得
        $query = "
            select
                1 as dummy

                -- details
                ,case when t_delivery_detail.delivery_quantity is null then received_detail.received_quantity
                    else (received_detail.received_quantity - t_delivery_detail.delivery_quantity)
                    end as detail_受注_残数
                ,T_lot.lot_no as detail_受注_出庫ロット

            from
                received_detail
                inner join received_header on received_header.received_header_id=received_detail.received_header_id
                left join estimate_header on received_header.estimate_header_id = estimate_header.estimate_header_id
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join customer_master as customer_master_shipping on received_header.delivery_customer_id = customer_master_shipping.customer_id
                left join item_master on received_detail.item_id = item_master.item_id
                " . self::getFromItemMasterChildren() . "
                /* " . self::getFromItemMasterProcess() . " */
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join section_master on received_header.section_id = section_master.section_id
                left join currency_master on received_detail.foreign_currency_id = currency_master.currency_id
                left join (
                    select
                        received_detail_id
                        ,SUM(delivery_quantity) as delivery_quantity
                    from
                        delivery_detail
                    group by
                        received_detail_id
                    ) as t_delivery_detail on received_detail.received_detail_id = t_delivery_detail.received_detail_id
                /* 出庫ロット（製番引当） */
                left join
                (
                    SELECT
                        seiban_change.item_id
                        ,seiban_change.dist_seiban
                        ,string_agg(t_ach_acc.lot_no || ' (' || cast(seiban_change.quantity as text) || ')', ',') as lot_no
                    FROM
                        seiban_change
                        inner join item_master on seiban_change.item_id = item_master.item_id
                        left JOIN (select lot_no, stock_seiban from achievement
                            union select lot_no, stock_seiban from accepted) as t_ach_acc 
                            on seiban_change.source_seiban = t_ach_acc.stock_seiban and seiban_change.source_seiban <> ''
                    WHERE
                        item_master.order_class = 2
                        /* ロット番号のある（実績とひもついた）製番在庫か、製番フリー在庫を出す。
                           逆に言えば、実績とひもつかない製番在庫（受注製番・計画製番）は出さない。 */
                        and (t_ach_acc.lot_no is not null or seiban_change.source_seiban = '')
                    GROUP BY
                        seiban_change.item_id, dist_seiban
                ) AS T_lot ON received_detail.seiban = T_lot.dist_seiban and received_detail.item_id = T_lot.item_id
            where
                -- 09iでは確定受注かつ未納のレコードだけ印刷できた（出荷指示書としてはその仕様が妥当）。
                -- 10iでは受注リストとしても使用できるよう、その縛りをはずした。
                --(received_detail.delivery_completed = false or received_detail.delivery_completed is null) and
                received_detail.received_detail_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                received_number
                ,line_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("出荷指示書");
        $info['report'] = "Received";
        $info['pageKeyColumn'] = "dummy";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("received_header", true, ""),
            array("received_detail", true, ""),
            array("customer_master", true, ""),
            array("customer_master_shipping", true, ""),
            array("estimate_header", true, " (" . _g("見積から転記された受注のみ") . ")"),
            array("item_master", true, ""),
            array("item_master_children", true, ""),    // 品目グループ・標準ロケ・標準手配先関連。SQL from に「self::getFromItemMasterChildren()」が必要
            // パフォーマンス上の理由でコメントアウト
            //array("item_master_process", true, ""),    // 工程関連。SQL from に「self::getFromItemMasterProcess()」が必要。工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
            array("currency_master", true, " (" . _g("取引通貨") . ")"),
            array("worker_master", true,  " (" . _g("担当者（自社）") . ")"),
            array("section_master", true,  " (" . _g("部門（自社）") . ")"),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("この帳票固有のタグ（受注明細）")),
            array("受注_残数", _g("受注残数"), 100),
            array("受注_出庫ロット", _g("出庫ロット(数量) ※ロット品目のみ"), "LOT130100001(100)"),
        );

        return $info;
    }
    
    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
        // 帳票発行済みフラグ
        Logic_Received::setReceivedPrintedFlag($form['idArr'], true);
        return;
    }

}