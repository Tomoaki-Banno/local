<?php
class Mobile_ItemMaster_Detail
{
    function execute(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("品目マスタ");
        $form['gen_headerLeftButtonURL'] = "index.php?action=Mobile_ItemMaster_List";
        $form['gen_headerLeftButtonIcon'] = "arrow-l";
        $form['gen_headerLeftButtonText'] = _g("戻る");

        if (!isset($form['item_id'])) {
            return "action:Mobile_ItemMaster_List";
        }

        // カスタム項目
        $customSelectList = "";
        if (isset($form['gen_customColumnArray'])) {
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $customSelectList .= ",{$form['gen_customColumnTable']}.{$customCol} as gen_custom_{$customCol}";
            }
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $query = "
            select
                -- end_itemの置き換えを行っているので * は使えない
                item_master.item_id
                ,item_master.item_code
                ,item_master.item_name
                ,case item_master.order_class when 0 then '" . _g("製番") . "' when 2 then '" . _g("ロット") . "' else '" . _g("MRP") . "' end as order_class
                ,item_master.lead_time
                ,item_master.safety_lead_time
                ,t_item_group1.item_group_name as item_group_name_1
                ,t_item_group2.item_group_name as item_group_name_2
                ,t_item_group3.item_group_name as item_group_name_3
                ,item_master.stock_price
                ,item_master.safety_stock
                ,case received_object when 0 then '" . _g("受注対象") . "' else '" . _g("対象外") . "' end as received_object
                ,item_master.maker_name
                ,item_master.spec
                ,case without_mrp when 0 then '" . _g("含める") . "' else '" . _g("含めない") . "' end as without_mrp
                ,item_master.comment
                ,item_master.comment_2
                ,item_master.comment_3
                ,item_master.comment_4
                ,item_master.comment_5
                ,item_master.llc
                ,item_master.default_selling_price
                ,item_master.default_selling_price_2
                ,item_master.default_selling_price_3
                ,item_master.selling_price_limit_qty_1
                ,item_master.selling_price_limit_qty_2
                ,case item_master.tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
                ,case when end_item then '"._g("非表示")."' else '' end as end_item
                ,case when dummy_item then '"._g("ダミー品目")."' else '' end as dummy_item
                ,item_master.drawing_file_oid
                ,item_master.drawing_file_name
                ,item_master.measure
                ,item_master.payout_price
                ,item_master.rack_no
                ,t_default_location1.location_name as default_location_name_1
                ,t_default_location2.location_name as default_location_name_2
                ,t_default_location3.location_name as default_location_name_3
                ,item_master.dropdown_flag

                ,t0.*
                ,t1.*
                ,t2.*
                {$customSelectList}

                ,coalesce(item_master.record_update_date, item_master.record_create_date) as gen_last_update
                ,coalesce(item_master.record_updater, item_master.record_creator) as gen_last_updater
            from item_master
            left join (select item_id as iid, max(case when classification in ('in','manufacturing') then item_in_out_date end) as last_in_date, max(case when classification in ('out','payout','use','delivery') then item_in_out_date end) as last_out_date  from item_in_out group by item_id) as t0 on item_master.item_id = t0.iid
            left join item_group_master as t_item_group1 on item_master.item_group_id = t_item_group1.item_group_id
            left join item_group_master as t_item_group2 on item_master.item_group_id_2 = t_item_group2.item_group_id
            left join item_group_master as t_item_group3 on item_master.item_group_id_3 = t_item_group3.item_group_id
            left join location_master as t_default_location1 on item_master.default_location_id = t_default_location1.location_id
            left join location_master as t_default_location2 on item_master.default_location_id_2 = t_default_location2.location_id
            left join location_master as t_default_location3 on item_master.default_location_id_3 = t_default_location3.location_id
            left join
                (select item_id as iid ";
                    // 手配先（item_order_master）
                    for ($i=0; $i<GEN_ITEM_ORDER_COUNT; $i++) {
                        $query .= "
                            ,max(case when line_number = $i then order_user_id else null end) as order_user_id_$i
                            ,max(case when line_number = $i then customer_name else null end) as customer_name_$i

                            ,max(case when line_number = $i then default_order_price else null end) as default_order_price_$i
                            ,max(case when line_number = $i then default_order_price_2 else null end) as default_order_price_2_$i
                            ,max(case when line_number = $i then default_order_price_3 else null end) as default_order_price_3_$i

                            ,max(case when line_number = $i then order_price_limit_qty_1 else null end) as order_price_limit_qty_1_$i
                            ,max(case when line_number = $i then order_price_limit_qty_2 else null end) as order_price_limit_qty_2_$i

                            ,max(case when line_number = $i then default_lot_unit else null end) as default_lot_unit_$i
                            ,max(case when line_number = $i then default_lot_unit_2 else null end) as default_lot_unit_2_$i

                            ,max(case when line_number = $i then item_sub_code else null end) as item_sub_code_$i
                            ,max(case when line_number = $i then case partner_class when 0 then '" . _g("発注") . "' when 1 then '" . _g("外注(支給なし)") . "' when 2 then '" . _g("外注(支給あり)") . "' else '" . _g("内製") . "' end else null end) as partner_class_$i
                            ,max(case when line_number = $i then order_measure else null end) as order_measure_$i
                            ,max(case when line_number = $i then multiple_of_order_measure else null end) as multiple_of_order_measure_$i
                        ";
                    }
                    $query .= "
                 from item_order_master
                 left join customer_master on item_order_master.order_user_id = customer_master.customer_id
                 group by item_id
                 ) as t1
                    on item_master.item_id = t1.iid
            left join
                (select item_id as iid2 ";
                    // 工程（item_process_master）
                    for ($i=0; $i<GEN_ITEM_PROCESS_COUNT; $i++) {
                        $query .= "
                            ,max(case when machining_sequence = {$i} then process_id else null end) as process_id_{$i}
                            ,max(case when machining_sequence = {$i} then default_work_minute else null end) as default_work_minute_{$i}
                            ,max(case when machining_sequence = {$i} then pcs_per_day else null end) as pcs_per_day_{$i}
                            ,max(case when machining_sequence = {$i} then charge_price else null end) as charge_price_{$i}
                            ,max(case when machining_sequence = {$i} then overhead_cost else null end) as overhead_cost_{$i}
                            ,max(case when machining_sequence = {$i} then process_lt else null end) as process_lt_{$i}
                            ,max(case when machining_sequence = {$i} then subcontract_partner_id else null end) as subcontract_partner_id_{$i}
                            ,max(case when machining_sequence = {$i} then subcontract_unit_price else null end) as subcontract_unit_price_{$i}
                            ,max(case when machining_sequence = {$i} then process_remarks_1 else null end) as process_remarks_1_{$i}
                        ";
                    }
                    $query .= "
                 from item_process_master
                 group by item_id
                 ) as t2
                    on item_master.item_id = t2.iid2
            Where
                item_id = '".$form['item_id']."'
        ";
        $form['gen_data'] = $gen_db->getArray($query);

        // フリックによるレコード遷移
        $listAction = "Mobile_ItemMaster_List";
        $detailAction = "Mobile_ItemMaster_Detail";
        $tableName = "item_master";
        $where = "";    // ListのSQLとあわせておく。本来は表示条件も読みだして反映すべき
        $idColumn = "item_id";  // このDetailページが呼ばれるときのキーパラメータ。DBカラム名でもある必要がある
        $defaultSortColumn = "item_code";

        // 以下はフリック用共通コード。いずれどこかに切り出す
        $userId = Gen_Auth::getCurrentUserId();
        $query = "select orderby from page_info where user_id = '{$userId}' and action = '{$listAction}'";
        $sortColumn = $gen_db->queryOneValue($query);
        if ($sortColumn=='') $sortColumn = $defaultSortColumn;
        $query = "select prev_id, next_id from (select {$idColumn} as id, lag({$idColumn},1) over(order by {$sortColumn}) as prev_id, lead({$idColumn},1) over(order by {$sortColumn}) as next_id
            from {$tableName} where 1=1 {$where}) as t_temp where id = '".$form[$idColumn]."'";
        $obj = $gen_db->queryOneRowObject($query);
        if ($obj->prev_id) $form['gen_prevAction'] = $detailAction . "&{$idColumn}=".$obj->prev_id;
        if ($obj->next_id) $form['gen_nextAction'] = $detailAction . "&{$idColumn}=".$obj->next_id;

        // カラム
        $form['gen_columnArray'] =
            array(
                array(
                    'label'=>_g('品目コード'),
                    'field'=>'item_code',
                ),
                array(
                    'label'=>_g('品目名'),
                    'field'=>'item_name',
                ),
                array(
                    'label'=>_g('最終入庫日'),
                    'field'=>'last_in_date',
                ),
                array(
                    'label'=>_g('最終出庫日'),
                    'field'=>'last_out_date',
                ),
                array(
                    'label'=>_g('管理区分'),
                    'field'=>'order_class',
                ),
                array(
                    'label'=>_g('非表示'),
                    'field'=>'end_item',
                ),
                array(
                    'label'=>_g('手配区分'),
                    'field'=>'partner_class_0',
                ),
                array(
                    'label'=>_g('標準手配先'),
                    'field'=>'customer_name_0',
                ),

                array(
                    'label'=>_g("■詳細項目"),
                    'sectionHeader'=>true,
                ),
                array(
                    'label'=>_g('品目グループ1'),
                    'field'=>'item_group_name_1',
                ),
                array(
                    'label'=>_g('品目グループ2'),
                    'field'=>'item_group_name_2',
                ),
                array(
                    'label'=>_g('品目グループ3'),
                    'field'=>'item_group_name_3',
                ),
                array(
                    'label'=>_g('標準販売単価1'),
                    'field'=>'default_selling_price',
                    'number_format'=>true,
                ),
                array(
                    'label'=>_g('販売単価1適用数'),
                    'field'=>'selling_price_limit_qty_1',
                    'number_format'=>true,
                ),
                array(
                    'label'=>_g('標準販売単価2'),
                    'field'=>'default_selling_price_2',
                    'number_format'=>true,
                ),
                array(
                    'label'=>_g('販売単価2適用数'),
                    'field'=>'selling_price_limit_qty_2',
                    'number_format'=>true,
                ),
                array(
                    'label'=>_g('標準販売単価3'),
                    'field'=>'default_selling_price_3',
                    'number_format'=>true,
                ),
                array(
                    'label' => _g('在庫評価単価') . '(' . $keyCurrency . ')',
                    'field'=>'stock_price',
                    'number_format'=>true,
                ),
                array(
                    'label' => _g('支給単価') . '(' . $keyCurrency . ')',
                    'field'=>'payout_price',
                    'number_format'=>true,
                ),
                array(
                    'label'=>_g('課税区分'),
                    'field'=>'tax_class',
                ),
                array(
                    'label'=>_g('管理単位') . _g('(個, kg, m 等)'),
                    'field'=>'measure',
                ),
                array(
                    'label'=>_g('受注対象'),
                    'field'=>'received_object',
                ),
                array(
                    'label'=>_g('メーカー'),
                    'field'=>'maker_name',
                ),
                array(
                    'label'=>_g('仕様'),
                    'field'=>'spec',
                ),
                array(
                    'label'=>_g('棚番'),
                    'field'=>'rack_no',
                ),
                array(
                    'label'=>_g('標準ﾛｹｰｼｮﾝ（受入）'),
                    'field'=>'default_location_name_1',
                ),
                array(
                    'label'=>_g('標準ﾛｹｰｼｮﾝ（使用）'),
                    'field'=>'default_location_name_2',
                ),
                array(
                    'label'=>_g('標準ﾛｹｰｼｮﾝ（完成）'),
                    'field'=>'default_location_name_3',
                ),
                array(
                    'label'=>_g('ダミー品目'),
                    'field'=>'dummy_item',
                ),
                array(
                    'label'=>_g('品目備考1'),
                    'field'=>'comment',
                ),
                array(
                    'label'=>_g('品目備考2'),
                    'field'=>'comment_2',
                ),
                array(
                    'label'=>_g('品目備考3'),
                    'field'=>'comment_3',
                ),
                array(
                    'label'=>_g('品目備考4'),
                    'field'=>'comment_4',
                ),
                array(
                    'label'=>_g('品目備考5'),
                    'field'=>'comment_5',
                ),

                array(
                    'label'=>_g("■所要量計算"),
                    'sectionHeader'=>true,
                ),
                array(
                    'label'=>_g('所要量計算に含める（製番品目を除く）'),
                    'field'=>'without_mrp',
                ),
                array(
                    'label'=>_g('安全在庫数'),
                    'field'=>'safety_stock',
                    'number_format'=>true,
                ),
                array(
                    'label' => _g('リードタイム') . '(' . _g('日') . ')',
                    'field'=>'lead_time',
                ),
                array(
                    'label' => _g('安全リードタイム') . '(' . _g('日') . ')',
                    'field'=>'safety_lead_time',
                ),
                array(
                    'label'=>_g('最低ロット数'),
                    'field'=>'default_lot_unit_0',
                    'number_format'=>true,
                ),
                array(
                    'label'=>_g('手配ロット数'),
                    'field'=>'comment_5',
                    'number_format'=>true,
                ),
          );

        for ($i=0; $i<GEN_ITEM_ORDER_COUNT; $i++) {
                $form['gen_columnArray'][] =
                array(
                    'label'=>"■" . ($i==0 ? _g("標準手配先") : _g("代替手配先") . $i),
                    'sectionHeader'=>true,
                );

                if ($i!=0) {
                    $form['gen_columnArray'][] =
                    array(
                        'label'=>_g("手配区分").($i),
                        'field'=>"partner_class_$i",
                    );
                    $form['gen_columnArray'][] =
                    array(
                        'label'=>_g("代替手配先").($i),
                        'field'=>"order_user_id_$i",
                    );
                }
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("メーカー型番") ,
                    'field'=>"item_sub_code_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("手配単位（個, kg, m 等）"),
                    'field'=>"order_measure_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("手配単位倍数"),
                    'field'=>"multiple_of_order_measure_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("購買取引通貨"),
                    'field'=>"currency_name_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("購入単価1"),
                    'field'=>"default_order_price_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("購入単価1適用数"),
                    'field'=>"order_price_limit_qty_1_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("購入単価2"),
                    'field'=>"default_order_price_2_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("購入単価2適用数"),
                    'field'=>"order_price_limit_qty_2_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("購入単価3"),
                    'field'=>"default_order_price_3_$i",
                );
            }

            for ($i=0; $i<GEN_ITEM_PROCESS_COUNT; $i++) {
                // 工程の挿入/削除/入れ替えボタン
                $form['gen_columnArray'][] =
                array(
                    'label'=>"■" . _g("工程") . ($i+1),
                    'sectionHeader'=>true,
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g('工程') . ($i+1),
                    'field'=>"process_id_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("標準加工時間(分)"),
                    'field'=>"default_work_minute_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("製造能力(1日あたり)"),
                    'field'=>"pcs_per_day_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("工賃(1分あたり)"),
                    'field'=>"charge_price_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g('外製先'),
                    'field'=>"subcontract_partner_id_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>sprintf(_g('外製単価(%s)'),$keyCurrency),
                    'field'=>"subcontract_unit_price_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label' => _g('工程リードタイム') . '(' . _g('日') . ')',
                    'field'=>"process_lt_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>sprintf(_g('固定経費(%s)'),$keyCurrency),
                    'field'=>"overhead_cost_$i",
                );
                $form['gen_columnArray'][] =
                array(
                    'label'=>_g("工程メモ1"),
                    'field'=>"process_remarks_1_$i",
                );
            }
            
        // カスタム項目
        if (isset($form['gen_customColumnArray'])) {
            $form['gen_columnArray'][] =
                array(
                    'label' => "■" . _g("フィールド・クリエイター"),
                    'sectionHeader'=>true,
                );
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $form['gen_columnArray'][] =
                    array(
                        'label' => $customArr[1],
                        'field' => "gen_custom_{$customCol}",
                    );
            }
        }

        return 'mobile_detail.tpl';
    }
}