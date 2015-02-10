<?php

class Manufacturing_Order_Report2 extends Base_PDFReportBase
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

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 印刷対象データの取得
        $query = "
            select
                -- headers

                1 as dummy

                -- details
                ,order_header_manufacturing.order_header_id as order_header_id
                
                ,coalesce(item_master.lead_time, t_lt.lt) as 製造指示_リードタイム
                ,t_received_item.item_code as 製造指示_受注品目コード
                ,t_received_item.item_name as 製造指示_受注品目名

                ,machining_sequence as detail_製造指示_工程番号（ソート用）
                ,order_process_no as detail_製造指示_実績登録コード
                ,process_code as detail_製造指示_工程コード
                ,process_name as detail_製造指示_工程名
                ,default_work_minute as detail_製造指示_工程標準加工時間
                ,process_dead_line as detail_製造指示_工程納期
                ,process_remarks_1 as 製造指示_工程メモ1
                ,process_remarks_2 as 製造指示_工程メモ2
                ,process_remarks_3 as 製造指示_工程メモ3

            from
                order_header as order_header_manufacturing
                inner join order_detail as order_detail_manufacturing on order_header_manufacturing.order_header_id = order_detail_manufacturing.order_header_id

                -- 親品目関係
                left join item_master on order_detail_manufacturing.item_id = item_master.item_id
                " . self::getFromItemMasterChildren() . "
                /* " . self::getFromItemMasterProcess() . " */
                left join (
                    -- 可変LT
                    select
                        order_detail_id
                        ,max(order_detail.item_id) as item_id
                        ,sum(coalesce(process_lt, trunc(order_detail_quantity / coalesce(case when pcs_per_day=0 then 1 else pcs_per_day end,1) + 0.9999999999)-1)) as lt
                    from
                        order_detail
                        left join item_process_master on order_detail.item_id = item_process_master.item_id
                    group by
                        order_detail_id
                    ) as t_lt on order_detail_manufacturing.order_detail_id = t_lt.order_detail_id
                left join received_detail on order_detail_manufacturing.seiban = received_detail.seiban
                left join received_header on received_detail.received_header_id = received_header.received_header_id
                left join item_master as t_received_item on received_detail.item_id = t_received_item.item_id
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join customer_master as customer_master_shipping on received_header.delivery_customer_id = customer_master_shipping.customer_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join section_master on received_header.section_id = section_master.section_id

                -- 工程関係
                left join (
                    select
                        order_detail_id
                        ,machining_sequence
                        ,order_process_no
                        ,process_code
                        ,process_name
                        ,process_dead_line
                        ,default_work_minute
                        ,pcs_per_day
                        ,process_remarks_1
                        ,process_remarks_2
                        ,process_remarks_3
                     from
                        order_process
                        inner join process_master on order_process.process_id = process_master.process_id
                   ) as t_process2
                   on order_detail_manufacturing.order_detail_id = t_process2.order_detail_id

            where
                order_header_manufacturing.order_header_id in (" . join(",", $idArr) . ")
                and order_header_manufacturing.classification = 0
            order by
                -- テンプレート内の指定が優先されることに注意
                order_detail_manufacturing.order_no, t_process2.machining_sequence, order_detail_manufacturing.item_code
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("製造指示書（リスト）");
        $info['report'] = "ManufacturingOrder2";
        $info['pageKeyColumn'] = "dummy"; // リスト形式にするため

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("order_header_manufacturing", true, ""),
            array("order_detail_manufacturing", true, ""),
            array("item_master", false, " (" . _g("製造品目。品目コード・品目名は上の「製造指示_」を使用することを推奨") . ")"),
            array("item_master_children", false, ""),    // 品目グループ・標準ロケ・標準手配先関連。SQL from に「self::getFromItemMasterChildren()」が必要
            // これ（とSQLのFROM句のgetFromItemMasterProcess）を有効にすれば品目マスタの工程関連のタグが有効になるが、
            // 製造指示書では登録時点の工程情報が order_process に記録され、そちらが優先されるため、ここは無効とする。
            //array("item_master_process", true, ""),    // 工程関連。SQL from に「self::getFromItemMasterProcess()」が必要。工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
            array("received_header", false, " (" . _g("製造品目が製番品目の場合のみ") . ")"),
            array("received_detail", false, " (" . _g("製造品目が製番品目の場合のみ") . ")"),
            array("customer_master", false, " (" . _g("製造品目が製番品目の場合のみ。受注得意先") . ")"),
            array("customer_master_shipping", false, " (" . _g("製造品目が製番品目の場合のみ。受注発送先") . ")"),
            array("worker_master", false, " (" . _g("製造品目が製番品目の場合のみ。受注担当者") . ")"),
            array("section_master", false, " (" . _g("製造品目が製番品目の場合のみ。受注部門") . ")"),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("この帳票固有のタグ")),
            array("製造指示_リードタイム", _g("品目マスタ [リードタイム]。省略時は[工程リードタイム]と[製造能力]から計算"), "1"),
            array("製造指示_受注品目コード", _g("受注登録画面 明細行 [品目]（製番品目のみ）"), "item1"),
            array("製造指示_受注品目名", _g("受注登録画面 明細行 [品目]（製番品目のみ）"), _g("受注品目")),
            array("●" . _g("製造指示書（工程関連）")),
            array("製造指示_実績登録コード", _g("実績バーコード登録用のコード"), "1000-1"),
            array("製造指示_工程コード", _g("製造指示登録時点の品目マスタ [工程]"), _g("P001")),
            array("製造指示_工程名", _g("製造指示登録時点の品目マスタ [工程]"), _g("テスト工程")),
            array("製造指示_工程標準加工時間", _g("製造指示登録時点の品目マスタ [標準加工時間]"), "10"),
            array("製造指示_工程納期", _g("製造納期と 品目マスタ[工程リードタイム]から計算される"), "2014-01-01"),
            array("製造指示_工程メモ1", _g("製造指示登録時点の品目マスタ [工程メモ1]"), _g("工程メモ1")),
            array("製造指示_工程メモ2", _g("製造指示登録時点の品目マスタ [工程メモ2]"), _g("工程メモ2")),
            array("製造指示_工程メモ3", _g("製造指示登録時点の品目マスタ [工程メモ3]"), _g("工程メモ3")),
            array("●" . _g("ソート（orderbyタグ）用")),
            array("製造指示_工程番号（ソート用）", "", "10"),
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
