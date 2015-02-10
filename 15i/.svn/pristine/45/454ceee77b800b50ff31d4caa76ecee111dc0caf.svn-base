<?php

class Manufacturing_Order_Report extends Base_PDFReportBase
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

        // 指定されたidが外製工程の外製指示書のものだった場合、その親となる製造指示書を印刷対象とする。
        // 　製造指示登録画面で「登録して印刷」した場合に印刷対象のidを getSequence で読み取るが（EntryBase）、
        // 　第一工程が外製だった場合、読み取られるidは外製のものになってしまう。その状況に対処するための措置。
        $query = "
            select
                parent_order_detail.order_header_id
            from
                order_detail
                inner join order_process on order_detail.subcontract_order_process_no = order_process.order_process_no
                inner join order_detail as parent_order_detail on order_process.order_detail_id = parent_order_detail.order_detail_id
            where
                order_detail.order_header_id in (" . join(",", $idArr) . ")
        ";
        $arr = $gen_db->getArray($query);
        if (is_array($arr)) {
            foreach ($arr as $row) {
                $idArr[] = $row['order_header_id'];
            }
        }

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 印刷対象データの取得
        $query = "
            select
                -- headers (order_header & order_detail)

                order_header_manufacturing.order_header_id as order_header_id
                
                ,coalesce(item_master.lead_time, t_lt.lt) as 製造指示_リードタイム
                ,t_received_item.item_code as 製造指示_受注品目コード
                ,t_received_item.item_name as 製造指示_受注品目名
                
                ";
                for ($i = 1; $i <= GEN_ITEM_PROCESS_COUNT; $i++) {
                    $query .= "
                        /* 15iから、外製工程には実績登録コードが表示されないようにした。無意味であり、紛らわしいため。ag.cgi?page=ProjectDocView&pPID=1574&pbid=217911 */
                        ,case when subcontract_partner_name_{$i} is null then order_process_no_{$i} else null end as 製造指示_工程{$i}_実績登録コード
                        ,process_code_{$i} as 製造指示_工程{$i}_工程コード
                        ,process_name_{$i} as 製造指示_工程{$i}_工程名
                        ,process_dead_line_{$i} as 製造指示_工程{$i}_工程納期
                        ,default_work_minute_{$i} as 製造指示_工程{$i}_標準加工時間
                        ,subcontract_partner_name_{$i} as 製造指示_工程{$i}_外製先
                        ,process_remarks_1_{$i} as 製造指示_工程{$i}_工程メモ1
                        ,process_remarks_2_{$i} as 製造指示_工程{$i}_工程メモ2
                        ,process_remarks_3_{$i} as 製造指示_工程{$i}_工程メモ3
                    ";
                }
                $query .= "

                -- details (child_item)

                ,child_item_master.item_code as detail_製造指示_子品目コード
                ,child_item_master.item_name as detail_製造指示_子品目名
                ,order_child_item.quantity as detail_製造指示_員数
                ,order_detail_manufacturing.order_detail_quantity * order_child_item.quantity as detail_製造指示_子品目数
                ,child_item_master.measure as detail_製造指示_子品目単位
                ,child_item_master.spec as detail_製造指示_子品目仕様
                ,child_item_master.maker_name as detail_製造指示_子品目メーカー
                ,child_item_master.rack_no as detail_製造指示_子品目棚番
                ,child_item_master.comment as detail_製造指示_子品目備考1
                ,child_item_master.comment_2 as detail_製造指示_子品目備考2
                ,child_item_master.comment_3 as detail_製造指示_子品目備考3
                ,child_item_master.comment_4 as detail_製造指示_子品目備考4
                ,child_item_master.comment_5 as detail_製造指示_子品目備考5
                ,child_default_location_master_use.location_name as detail_製造指示_子品目標準ロケ使用
                ,child_item_group_master.item_group_code as detail_製造指示_子品目グループコード
                ,child_item_group_master.item_group_name as detail_製造指示_子品目グループ名
                ,coalesce(t_bom.bom_seq,0) as detail_製造指示_子品目構成表ソート番号

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
                    from order_detail
                        left join item_process_master on order_detail.item_id = item_process_master.item_id
                    group by order_detail_id
                    ) as t_lt on order_detail_manufacturing.order_detail_id = t_lt.order_detail_id
                left join received_detail on order_detail_manufacturing.seiban = received_detail.seiban
                left join received_header on received_detail.received_header_id = received_header.received_header_id
                left join item_master as t_received_item on received_detail.item_id = t_received_item.item_id
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join customer_master as customer_master_shipping on received_header.delivery_customer_id = customer_master_shipping.customer_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join section_master on received_header.section_id = section_master.section_id
                -- 子品目関係
                left join order_child_item on order_detail_manufacturing.order_detail_id = order_child_item.order_detail_id
                left join item_master as child_item_master on order_child_item.child_item_id = child_item_master.item_id
                left join location_master as child_default_location_master_use on child_item_master.default_location_id_2 = child_default_location_master_use.location_id
                left join item_group_master as child_item_group_master on child_item_master.item_group_id = child_item_group_master.item_group_id
                left join (select order_detail_id as oid, count(*) as order_child_count
                    from order_child_item group by order_detail_id) as t_child_count on order_detail_manufacturing.order_detail_id = t_child_count.oid
                left join (select item_id as bom_item_id, child_item_id as bom_child_item_id, (seq + 1) as bom_seq
                    from bom_master) as t_bom on order_detail_manufacturing.item_id = t_bom.bom_item_id and order_child_item.child_item_id = t_bom.bom_child_item_id

                -- 工程関係
                left join (
                    select
                        order_detail_id
                        ";
                        for ($i = 1; $i <= GEN_ITEM_PROCESS_COUNT; $i++) {
                            $query .= "
                                ,max(case when machining_sequence = " . ($i - 1) . " then order_process_no end) as order_process_no_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then process_code end) as process_code_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then process_name end) as process_name_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then process_dead_line end) as process_dead_line_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then default_work_minute end) as default_work_minute_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then pcs_per_day end) as pcs_per_day_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then customer_name end) as subcontract_partner_name_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then process_remarks_1 end) as process_remarks_1_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then process_remarks_2 end) as process_remarks_2_{$i}
                                ,max(case when machining_sequence = " . ($i - 1) . " then process_remarks_3 end) as process_remarks_3_{$i}
                            ";
                        }
                        $query .= "
                    from
                        order_process
                        inner join process_master on order_process.process_id = process_master.process_id
                        -- 外製先。ここでは内製登録時点のマスタ上の外製先を取得していることに注意。
                        -- 内製登録（そして外製工程発行）後、外製工程オーダーの外製先を直接変更してもここには反映されない。
                        left join customer_master on order_process.subcontract_partner_id = customer_master.customer_id
                    group by
                        order_detail_id
                    ) as t_process
                    on order_detail_manufacturing.order_detail_id = t_process.order_detail_id
            where
                order_header_manufacturing.order_header_id in (" . join(",", $idArr) . ")
                and order_header_manufacturing.classification = 0
            order by
                -- テンプレート内の指定が優先されることに注意
                order_detail_manufacturing.order_no, t_bom.bom_seq, child_item_master.item_group_id, child_item_master.item_code
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("製造指示書");
        $info['report'] = "ManufacturingOrder";
        $info['pageKeyColumn'] = "order_header_id";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("order_header_manufacturing", false, ""),
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
            array("●" . _g("子品目関連")),
            array("製造指示_子品目コード", _g("子品目： 子品目の品目コード"), "code001"),
            array("製造指示_子品目名", _g("子品目： 子品目の品目名"), _g("テスト品目")),
            array("製造指示_員数", _g("子品目： 構成表マスタ [員数]"), "10"),
            array("製造指示_子品目数", _g("子品目： 数量 × 員数"), "20000"),
            array("製造指示_子品目単位", _g("子品目： 品目マスタ [単位]"), _g("個")),
            array("製造指示_子品目仕様", _g("子品目： 品目マスタ [仕様]"), _g("テスト仕様")),
            array("製造指示_子品目メーカー", _g("子品目： 品目マスタ [メーカー]"), _g("テストメーカー")),
            array("製造指示_子品目棚番", _g("子品目： 品目マスタ [棚番]"), _g("テスト型番")),
            array("製造指示_子品目標準ロケ使用", _g("子品目： 品目マスタ [標準ﾛｹｰｼｮﾝ（使用）]"), _g("テスト標準ロケ")),
            array("製造指示_子品目備考1", _g("子品目： 品目マスタ [備考1]"), _g("テスト子品目備考1")),
            array("製造指示_子品目備考2", _g("子品目： 品目マスタ [備考2]"), _g("テスト子品目備考2")),
            array("製造指示_子品目備考3", _g("子品目： 品目マスタ [備考3]"), _g("テスト子品目備考3")),
            array("製造指示_子品目備考4", _g("子品目： 品目マスタ [備考4]"), _g("テスト子品目備考4")),
            array("製造指示_子品目備考5", _g("子品目： 品目マスタ [備考5]"), _g("テスト子品目備考5")),
            array("製造指示_子品目グループコード", _g("子品目： 品目グループマスタ [品目グループコード]"), "G001"),
            array("製造指示_子品目グループ名", _g("子品目： 品目グループマスタ [品目グループ名]"), _g("品目グループ1")),
            array("製造指示_子品目構成表ソート番号", _g("子品目： 構成表マスタ [登録されている品目] ソート順"), _g("1")),
            array("●" . _g("工程関連")),
        );
        for ($i = 1; $i <= GEN_ITEM_PROCESS_COUNT; $i++) {
            $info['tagList'][] = array("製造指示_工程{$i}_実績登録コード", _g("実績バーコード登録用のコード"), "1000-" . $i);
            $info['tagList'][] = array("製造指示_工程{$i}_工程コード", sprintf(_g("製造指示登録時点の品目マスタ [工程%s]"), $i), _g("P001") . $i);
            $info['tagList'][] = array("製造指示_工程{$i}_工程名", sprintf(_g("製造指示登録時点の品目マスタ [工程%s]"), $i), _g("テスト工程") . $i);
            $info['tagList'][] = array("製造指示_工程{$i}_標準加工時間", sprintf(_g("製造指示登録時点の品目マスタ [標準加工時間%s]"), $i), "10");
            $info['tagList'][] = array("製造指示_工程{$i}_外製先", sprintf(_g("製造指示登録時点の品目マスタ 工程%s [外製先]"), $i), _g("外製先株式会社"));
            $info['tagList'][] = array("製造指示_工程{$i}_工程納期", _g("製造納期と 品目マスタ[工程リードタイム]から計算される"), "2014-01-0" . $i);
            $info['tagList'][] = array("製造指示_工程{$i}_工程メモ1", sprintf(_g("製造指示登録時点の品目マスタ 工程%s [工程メモ1]"), $i), _g("工程メモ1_") . $i);
            $info['tagList'][] = array("製造指示_工程{$i}_工程メモ2", sprintf(_g("製造指示登録時点の品目マスタ 工程%s [工程メモ2]"), $i), _g("工程メモ2_") . $i);
            $info['tagList'][] = array("製造指示_工程{$i}_工程メモ3", sprintf(_g("製造指示登録時点の品目マスタ 工程%s [工程メモ3]"), $i), _g("工程メモ3_") . $i);
        }

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
