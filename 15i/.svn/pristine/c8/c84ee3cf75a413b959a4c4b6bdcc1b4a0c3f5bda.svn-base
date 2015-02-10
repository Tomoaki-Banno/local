<?php

class Master_Item_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
            ),
            array(
                'label' => _g('品目グループ'),
                'type' => 'select',
                'field' => 'item_group_id',
                'options' => $option_item_group,
            ),
            array(
                'label' => _g('管理区分'),
                'type' => 'select',
                'field' => 'order_class',
                'options' => Gen_Option::getOrderClass('search'),
                'hide' => true,
            ),
            array(
                'label' => _g('手配区分'),
                'type' => 'select',
                'field' => 'partner_class',
                'options' => Gen_Option::getPartnerClass('search'),
                'hide' => true,
            ),
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'hide' => true,
            ),
            array(
                'label' => _g('親品目'),
                'type' => 'select',
                'type' => 'dropdown',
                'field' => 'parent_item_id',
                'size' => '150',
                'dropdownCategory' => 'item',
                'rowSpan' => 2,
                'nosql' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'comment',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'comment_2',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'comment_3',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'comment_4',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'comment_5',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('非表示品目'),
                'type' => 'select',
                'field' => 'end_item',
                'options' => array('' => "(" . _g('すべて') . ")", '0' => _g('通常のみ'), '1' => _g('非表示のみ')),
                'nosql' => 'true',
                'default' => 'false',
                'hide' => true,
            ),
            array(
                'label' => _g('登録方法'),
                'type' => 'select',
                'field' => 'item_master___dropdown_flag',
                //'nosql'=>true,
                'options' => array('' => _g("(すべて)"), true => _g('マスタ以外から登録された品目')),
                'helpText_noEscape' => _g("「マスタ以外から登録された品目」とは、各画面の品目選択の拡張ドロップダウンから登録された品目のことです。") . "<br><br>"
                    . _g("たとえば受注登録画面や注文登録画面などで、品目選択のドロップダウンに目的の品目がない場合、その画面からジャンプしてマスタに新規登録することができます。そのようにして登録された品目が「マスタ以外から登録された品目」になります。") . "<br><br>"
                    . _g("受注登録や注文登録の時点では仮に品目登録しておき、あとから品目マスタで項目を編集する、という場合にこの項目での絞り込みを利用すると便利です。"),
                'hide' => true,
            ),
        );

        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("品目グループ別 品目数") => self::_getPreset("gen_all", "item_group_name", "order by field1 desc"),
                _g("標準手配先別 品目数") => self::_getPreset("gen_all", "customer_name", "order by field1 desc"),
            );
    }
    
    function _getPreset($horiz, $vert, $orderby = "", $value = "item_code", $method = "count")
    {
        return
            array(
                "data" => array(
                    array("f" => "gen_crossTableHorizontal", "v" => $horiz),
                    array("f" => "gen_crossTableVertical", "v" => $vert),
                    array("f" => "gen_crossTableValue", "v" => $value),
                    array("f" => "gen_crossTableMethod", "v" => $method),
                ),
                "orderby" => $orderby,
            );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        global $gen_db;
        
        // 親品目が指定されている場合はtemp_bom_expandテーブルを準備
        if (is_numeric(@$form['gen_search_parent_item_id'])) {
            Logic_Bom::expandBom($form['gen_search_parent_item_id'], 0, false, false, false);
        }

        $classQuery = Gen_Option::getPartnerClass('list-query');

        // 下記をテンポラリテーブル化することで劇的に速度が上がる場合がある
        $query = "
            create temp table temp_item_process as 
            select 
                item_id as iid
                ,sum(default_work_minute) as default_work_minute
                ,sum(default_work_minute * charge_price) as default_work_price
                ,sum(overhead_cost) as overhead_cost 
            from 
                item_process_master 
            group by item_id;
            create index temp_item_process_index1 on temp_item_process (iid);

            create temp table temp_item_in_out as 
            select 
                item_id as iid
                ,max(case when classification in ('in','manufacturing') then item_in_out_date end) as last_in_date 
                ,max(case when classification in ('out','payout','use','delivery') then item_in_out_date end) as last_out_date 
            from 
                item_in_out 
            group by item_id;
            create index temp_item_in_out_index1 on temp_item_in_out (iid);
        ";
        $gen_db->query($query);
        
        $this->selectQuery = "
            select
                *
                ,case when coalesce(image_file_name,'') = '' then '' else '" . _g("有") . "' end as is_image_exist
                ,case item_master.order_class when 0 then '" . _g("製番") . "' when 2 then '" . _g("ロット") . "' else '" . _g("MRP") . "' end as order_class_show
                ,case partner_class {$classQuery} end as partner_class_show
                ,case without_mrp when 0 then '" . _g("含める") . "' else '" . _g("含めない") . "' end as without_mrp_show
                ,case received_object when 0 then '" . _g("受注対象") . "' else '" . _g("対象外") . "' end as received_object_show
                ,case item_master.tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as show_tax_class
                ,case when end_item then '" . _g("非表示") . "' else '' end as show_end_item
                ,case when dummy_item then '" . _g("ダミー品目") . "' else '' end as show_dummy_item
                ,case when end_item then 1 else null end as end_item_csv
                ,case when dummy_item then 1 else null end as dummy_item_csv

                ,item_master.record_create_date as gen_record_create_date
                ,item_master.record_creator as gen_record_creater
                ,coalesce(item_master.record_update_date, item_master.record_create_date) as gen_record_update_date
                ,coalesce(item_master.record_updater, item_master.record_creator) as gen_record_updater

            from
                item_master
                " . (is_numeric(@$form['gen_search_parent_item_id']) ? " inner join (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on item_master.item_id = t_exp.exp_item_id " : "") . "
                left join (select item_group_id as gid, item_group_code as item_group_code, item_group_name as item_group_name from item_group_master) as t1 on item_master.item_group_id = t1.gid
                left join (select item_group_id as gid, item_group_code as item_group_code_2, item_group_name as item_group_name_2 from item_group_master) as t2 on item_master.item_group_id_2 = t2.gid
                left join (select item_group_id as gid, item_group_code as item_group_code_3, item_group_name as item_group_name_3 from item_group_master) as t3 on item_master.item_group_id_3 = t3.gid
                left join (select location_id as lid, location_code as default_location_code, location_name as default_location_name from location_master) as t4 on item_master.default_location_id = t4.lid
                left join (select location_id as lid, location_code as default_location_code_2, location_name as default_location_name_2 from location_master) as t5 on item_master.default_location_id_2 = t5.lid
                left join (select location_id as lid, location_code as default_location_code_3, location_name as default_location_name_3 from location_master) as t6 on item_master.default_location_id_3 = t6.lid
                left join (select item_id as iid, order_user_id, partner_class from item_order_master where line_number=0) as t7 on item_master.item_id = t7.iid
                left join customer_master on t7.order_user_id = customer_master.customer_id
                /* 13iまでは item_process_master や item_in_out を直接JOINしていた。しかし15iで window関数を使用するようになったため、かなり速度低下するケースが出てきた。*/
                /* そこでテンポラリテーブルで処理するようにした。 */
                left join temp_item_process on item_master.item_id = temp_item_process.iid
                left join temp_item_in_out on item_master.item_id = temp_item_in_out.iid
            ";
        if (isset($form['gen_csvMode']) || isset($form['gen_excelMode'])) {
            $this->selectQuery .= "
                    left join (
                        select
                            item_id as iid
            ";
            for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
                $this->selectQuery .= "
                    ,MAX(case when line_number = {$i} then partner_class end) as partner_class_{$i}
                    ,MAX(case when line_number = {$i} then case when item_order_master.partner_class = 3 then '" . _g("内製") . "' else customer_no end end) as order_user_code_{$i}
                    ,MAX(case when line_number = {$i} then item_sub_code end) as item_sub_code_{$i}
                    ,MAX(case when line_number = {$i} then order_measure end) as order_measure_{$i}
                    ,MAX(case when line_number = {$i} then multiple_of_order_measure end) as multiple_of_order_measure_{$i}
                    ,MAX(case when line_number = {$i} then default_lot_unit end) as default_lot_unit_{$i}
                    ,MAX(case when line_number = {$i} then default_lot_unit_2 end) as default_lot_unit_2_{$i}
                    ,MAX(case when line_number = {$i} then default_order_price end) as default_order_price_{$i}
                    ,MAX(case when line_number = {$i} then order_price_limit_qty_1 end) as order_price_limit_qty_1_{$i}
                    ,MAX(case when line_number = {$i} then default_order_price_2 end) as default_order_price_2_{$i}
                    ,MAX(case when line_number = {$i} then order_price_limit_qty_2 end) as order_price_limit_qty_2_{$i}
                    ,MAX(case when line_number = {$i} then default_order_price_3 end) as default_order_price_3_{$i}
                ";
            }
            $this->selectQuery .= "
                from
                    item_order_master
                    left join customer_master on item_order_master.order_user_id = customer_master.customer_id
                group by
                    item_order_master.item_id
                ) as t_item_order
                on item_master.item_id = t_item_order.iid

            left join (
                select
                    item_id as iid
            ";
            for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
                $this->selectQuery .= "
                    ,MAX(case when machining_sequence = {$i} then process_master.process_code end) as process_code_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.default_work_minute end) as default_work_minute_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.pcs_per_day end) as pcs_per_day_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.charge_price end) as charge_price_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.overhead_cost end) as overhead_cost_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.process_lt end) as process_lt_{$i}
                    ,MAX(case when machining_sequence = {$i} then customer_master.customer_no end) as subcontract_partner_code_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.subcontract_unit_price end) as subcontract_unit_price_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.process_remarks_1 end) as process_remarks_1_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.process_remarks_2 end) as process_remarks_2_{$i}
                    ,MAX(case when machining_sequence = {$i} then item_process_master.process_remarks_3 end) as process_remarks_3_{$i}
                ";
            }
            $this->selectQuery .= "
                from
                    item_process_master
                    left join process_master on item_process_master.process_id = process_master.process_id
                    left join customer_master on item_process_master.subcontract_partner_id = customer_master.customer_id
                group by
                    item_process_master.item_id
                ) as t_item_process
                on item_master.item_id = t_item_process.iid
            ";
        }

        // 「and item_id >= 0」はインデックスを確実に使わせるために入れた。劇的に速度向上。
        $this->selectQuery .= "
            [Where]
                and item_id >= 0
                " . (@$form['gen_search_end_item'] == "0" ? " and (end_item is null or end_item = false)" : "") . "
                " . (@$form['gen_search_end_item'] == "1" ? " and end_item = true" : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'item_code';
    }

    function setCsvParam(&$form)
    {
        global $gen_db;

        $form['gen_importLabel'] = _g("品目");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（品目コードが既存の場合はレコードを上書きする）");

        // 通知メール（成功時のみ）
        $form['gen_csvAlertMail_id'] = 'item_master_new';   // Master_AlertMail_Edit冒頭を参照
        $form['gen_csvAlertMail_title'] = _g("品目マスタ登録");
        $form['gen_csvAlertMail_body'] = _g("品目マスタがCSVインポートされました。");    // インポート件数等が自動的に付加される

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
            ),
            array(
                'label' => _g('管理区分'),
                'addLabel' => _g('(0:製番、1:MRP、2:ロット)'),
                'field' => 'order_class',
            ),
            array(
                'label' => _g('品目グループコード1'),
                'field' => 'item_group_code',
            ),
            array(
                'label' => _g('品目グループコード2'),
                'field' => 'item_group_code_2',
            ),
            array(
                'label' => _g('品目グループコード3'),
                'field' => 'item_group_code_3',
            ),
            array(
                'label' => _g('標準販売単価1'),
                'field' => 'default_selling_price',
            ),
            array(
                'label' => _g('販売単価1適用数'),
                'field' => 'selling_price_limit_qty_1',
            ),
            array(
                'label' => _g('標準販売単価2'),
                'field' => 'default_selling_price_2',
            ),
            array(
                'label' => _g('販売単価2適用数'),
                'field' => 'selling_price_limit_qty_2',
            ),
            array(
                'label' => _g('標準販売単価3'),
                'field' => 'default_selling_price_3',
            ),
            array(
                'label' => _g('在庫評価単価'),
                'addLabel' => "({$keyCurrency})",
                'field' => 'stock_price',
            ),
            array(
                'label' => _g('管理単位'),
                'field' => 'measure',
            ),
            array(
                'label' => _g('支給単価'),
                'addLabel' => "({$keyCurrency})",
                'field' => 'payout_price',
            ),
            array(
                'label' => _g('安全在庫数'),
                'field' => 'safety_stock',
            ),
            array(
                'label' => _g('所要量計算に含めるか'),
                'addLabel' => _g('(0：含める、1:含めない)'),
                'field' => 'without_mrp',
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
            ),
            array(
                'label' => _g('標準ロケーションコード（受入）'),
                'field' => 'default_location_code',
            ),
            array(
                'label' => _g('標準ロケーションコード（使用）'),
                'field' => 'default_location_code_2',
            ),
            array(
                'label' => _g('標準ロケーションコード（完成）'),
                'field' => 'default_location_code_3',
            ),
            array(
                'label' => _g('リードタイム'),
                'addLabel' => _g('(日)'),
                'field' => 'lead_time',
            ),
            array(
                'label' => _g('安全リードタイム'),
                'addLabel' => _g('(日)'),
                'field' => 'safety_lead_time',
            ),
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
            ),
            array(
                'label' => _g('入数'),
                'field' => 'quantity_per_carton',
            ),
            array(
                'label' => _g('受注対象'),
                'addLabel' => _g('(0:対象、1:非対象)'),
                'field' => 'received_object',
            ),
            array(
                'label' => _g('課税区分'),
                'addLabel' => _g('(0:課税、1:非課税)'),
                'field' => 'tax_class',
            ),
            array(
                'label' => _g('税率'),
                'field' => 'tax_rate',
            ),
            array(
                'label' => _g('消費期限日数'),
                'field' => 'use_by_days',
            ),
            array(
                'label' => _g('ロット頭文字'),
                'field' => 'lot_header',
            ),
            array(
                'label' => _g('非表示'),
                'addLabel' => _g('(1なら非表示)'),
                'field' => 'end_item',
                'exportField' => 'end_item_csv',
            ),
            array(
                'label' => _g('ダミー品目'),
                'addLabel' => _g('(1ならダミー品目)'),
                'field' => 'dummy_item',
                'exportField' => 'dummy_item_csv',
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'comment',
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'comment_2',
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'comment_3',
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'comment_4',
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'comment_5',
            ),
        );
        //$form['gen_csvArray']
        for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
            $no = ($i == 0 ? _g("標準手配先") : _g("手配先") . $i) . ":";
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('手配区分'),
                'addLabel' => _g('（0：発注、1：外注[支給なし]、2：外注[支給あり]、3：内製）'),
                'field' => 'partner_class_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('手配先コード'),
                'field' => 'order_user_code_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('メーカー型番'),
                'field' => 'item_sub_code_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('手配単位'),
                'field' => 'order_measure_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('手配単位倍数'),
                'field' => 'multiple_of_order_measure_' . $i,
            );
            if ($i == 0) {
                $form['gen_csvArray'][] = array(
                    'label' => $no . _g('最低ロット数'),
                    'field' => 'default_lot_unit_' . $i,
                );
                $form['gen_csvArray'][] = array(
                    'label' => $no . _g('手配ロット数'),
                    'field' => 'default_lot_unit_2_' . $i,
                );
            }
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('購入単価1'),
                //'addLabel'=>"({$keyCurrency})",   // 手配先によって取引通貨が異なるためコメントアウト
                'field' => 'default_order_price_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('購入単価1適用数'),
                'field' => 'order_price_limit_qty_1_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('購入単価2'),
                //'addLabel'=>"({$keyCurrency})",   // 手配先によって取引通貨が異なるためコメントアウト
                'field' => 'default_order_price_2_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('購入単価2適用数'),
                'field' => 'order_price_limit_qty_2_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('購入単価3'),
                //'addLabel'=>"({$keyCurrency})",   // 手配先によって取引通貨が異なるためコメントアウト
                'field' => 'default_order_price_3_' . $i,
            );
        }
        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            $no = _g("工程") . ($i + 1) . ":";
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('工程コード'),
                'field' => 'process_code_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('標準加工時間'),
                'addLabel' => _g('（分）'),
                'field' => 'default_work_minute_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('製造能力'),
                'addLabel' => _g('（1日あたり）'),
                'field' => 'pcs_per_day_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('工賃'),
                'addLabel' => _g('（1分あたり）'),
                'field' => 'charge_price_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('外製先コード'),
                'field' => 'subcontract_partner_code_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('外製単価'),
                'addLabel' => sprintf(_g('（%s/個）'), $keyCurrency),
                'field' => 'subcontract_unit_price_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('工程リードタイム'),
                'addLabel' => _g('（日）'),
                'field' => 'process_lt_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('固定経費'),
                'addLabel' => sprintf(_g('（%s/個）'), $keyCurrency),
                'field' => 'overhead_cost_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('工程メモ1'),
                'field' => 'process_remarks_1_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('工程メモ2'),
                'field' => 'process_remarks_2_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => $no . _g('工程メモ3'),
                'field' => 'process_remarks_3_' . $i,
            );
        }
    }

    function setViewParam(&$form) {
        global $gen_db;

        $form['gen_pageTitle'] = _g("品目マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Item_List";
        $form['gen_editAction'] = "Master_Item_Edit";
        $form['gen_deleteAction'] = "Master_Item_Delete";
        $form['gen_idField'] = 'item_id';
        $form['gen_idFieldForUpdateFile'] = "item_master.item_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("品目マスタ");
        
        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集
        $form['gen_multiEditEnable'] = "true";      // 一括編集
//        $form['gen_inlineEditEnable'] = "true";   // ダイレクト新規登録

        // 編集用エクセルファイル
        $form['gen_editExcel'] = "true";

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("品目ラベル 印刷"),
                'link' => "javascript:gen.list.printReport('Master_Item_Report','check')",
                'reportEdit' => 'Master_Item_Report'
            ),
        );

        $form['gen_javascript_noEscape'] = "
            function goBom(itemCode) {
                window.open('index.php?action=Master_Bom_List&parent_item_code='+itemCode);
            }
            function goStocklist(itemCode) {
                window.open('index.php?action=Stock_Stocklist_List&gen_searchConditionClear&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
            function goReceived(itemCode) {
                window.open('index.php?action=Manufacturing_Received_List&gen_searchConditionClear&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
            function goDelivery(itemCode) {
                window.open('index.php?action=Delivery_Delivery_List&gen_searchConditionClear&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
            function goManOrder(itemCode) {
                window.open('index.php?action=Manufacturing_Order_List&gen_searchConditionClear&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
            function goPartnerOrder(itemCode) {
                window.open('index.php?action=Partner_Order_List&gen_searchConditionClear&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
            function goSubcontract(itemCode) {
                window.open('index.php?action=Partner_Subcontract_List&gen_searchConditionClear&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
            function goStockIn(itemCode) {
                window.open('index.php?action=Stock_Inout_List&gen_searchConditionClear&classification=in&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
            function goStockOut(itemCode) {
                window.open('index.php?action=Stock_Inout_List&gen_searchConditionClear&classification=out&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
            function goStockinput(itemCode) {
                // 先月末時点
                window.open('index.php?action=Monthly_StockInput_List&gen_search_inventory_date=" . (date('Y-m-d', mktime(0, 0, 0, date('m'), 0, date('Y')))) . "gen_searchConditionClear&classification=in&gen_search_item_code='+itemCode+'&gen_search_match_mode_gen_search_item_code=3');
            }
        ";

        // ラベル印刷機能はとりあえず廃止。テンプレートで横方向の明細展開が難しいため
//        $form['gen_reportArray'] =
//            array(
//                array(
//                    'label'=>_g("ラベル印刷"),
//                    'link'=>"javascript:labelPrint();",
//                ),
//            );
//
//        $form['gen_javascript_noEscape'] = "
//            function labelPrint() {
//                var frm = gen.list.table.getCheckedPostSubmit('check');
//                if (frm.count == 0) {
//                    alert('" . _g("ラベルを印刷する品目が選択されていません。印刷対象品目の「ラベル印刷」列のチェックボックスをオンにしてください。") . "');
//                } else {
//                    var postUrl = 'index.php?action=Master_Item_Label';
//                    frm.submit(postUrl);
//                }
//            }
//        ";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[show_end_item]'!=''"     // 非表示
        );
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("非表示")),
        );

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group_id = $gen_db->getHtmlOptionArray($query, true);
        
        $query = "select location_id, location_name from location_master order by location_code;";
        $option_location_id = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_fixColumnArray'] =
            array(
                array(
                    'label'=>_g('明細'),
                    'type'=>'edit',
                ),
                array(
                    'label'=>_g('コピー'),
                    'type'=>'copy',
                ),
                array(
                    'label'=>_g("選択"),
                    //'label'=>_g("印刷"),
                    'name'=>'check',
                    'type'=>'checkbox',
                ),
                array(
                    'label'=>_g('削除'),
                    'type'=>'delete_check',
                    'deleteAction'=>'Master_Item_BulkDelete',
                ),
                array(
                    'label'=>_g('品目コード'),
                    'field'=>'item_code',
                    'editType'=>'text',
                ),
            );
        $form['gen_columnArray'] =
            array(
                array(
                    'field'=>'item_name',
                    'label'=>_g('品目名'),
                    'width'=>'250',
                    'editType'=>'text',
                ),
                array(
                    'label'=>_g('画像登録'),
                    'field'=>'is_image_exist',
                    'width'=>'40',
                    'align'=>'center',
                    'editType'=>'none', // fieldがあってもclickEdit対象にしたくないカラムにはこのパラメータが必要
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('管理区分'),
                    'field'=>'order_class_show',
                    'width'=>'70',
                    'align'=>'center',
                    'editType'=>'select',
                    'editOptions'=>array(1=>_g('MRP'), 0=>_g('製番'), 2=>_g('ロット')),
                    'entryField'=>'order_class',                    
                ),
                array(
                    'label'=>_g('手配区分'),
                    'field'=>'partner_class_show',
                    'width'=>'100',
                    'align' => 'center',
                    'editType'=>'select',
                    'editOptions'=>array(''=>_g('(なし)'), 3=>_g('内製'), 0=>_g('発注'), 1=>_g('外注(支給なし)'), 2=>_g('外注(支給あり)')),
                    'entryField'=>'partner_class_0',                    
                ),
                array(
                    'label'=>_g('標準手配先'),
                    'field'=>'customer_name',
                    'width'=>'100',
                    'align'=>'center',
                    'hide'=>true,
                    'editType'=>'dropdown',
                    'dropdownCategory'=>'partner',
                    'entryField'=>'order_user_id_0',                    
                ),
                array(
                    'label'=>_g('リードタイム'),
                    'field'=>'lead_time',
                    'type'=>'numeric',
                    'hide'=>true,
                    'editType'=>'text',
                ),
                array(
                    'label'=>_g('安全リードタイム'),
                    'field'=>'safety_lead_time',
                    'type'=>'numeric',
                    'hide'=>true,
                    'editType'=>'text',
                ),
                array(
                    'label'=>_g('品目グループ1'),
                    'field'=>'item_group_name',
                    'hide'=>true,
                    'editType'=>'select',
                    'editOptions'=>$option_item_group_id,
                    'entryField'=>'item_group_id',                    
                ),
                array(
                    'label'=>_g('品目グループ2'),
                    'field'=>'item_group_name_2',
                    'hide'=>true,
                    'editType'=>'select',
                    'editOptions'=>$option_item_group_id,
                    'entryField'=>'item_group_id_2',                    
                ),
                array(
                    'label'=>_g('品目グループ3'),
                    'field'=>'item_group_name_3',
                    'hide'=>true,
                    'editType'=>'select',
                    'editOptions'=>$option_item_group_id,
                    'entryField'=>'item_group_id_3',                    
                ),
                array(
                    'label'=>_g('標準販売単価1'),
                    'field'=>'default_selling_price',
                    'type'=>'numeric',
                    'width'=>'90',
                    'hide'=>true,
                    'editType'=>'text',
                ),
                array(
                    'label'=>_g('販売単価1適用数'),
                    'field'=>'selling_price_limit_qty_1',
                    'type'=>'numeric',
                    'width'=>'90',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('標準販売単価2'),
                    'field'=>'default_selling_price_2',
                    'type'=>'numeric',
                    'width'=>'90',
                    'hide'=>true,
                    'editType'=>'text',
                ),
                array(
                    'label'=>_g('販売単価2適用数'),
                    'field'=>'selling_price_limit_qty_2',
                    'type'=>'numeric',
                    'width'=>'90',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('標準販売単価3'),
                    'field'=>'default_selling_price_3',
                    'type'=>'numeric',
                    'width'=>'90',
                    'hide'=>true,
                    'editType'=>'text',
                ),
                array(
                    'label'=>_g('在庫評価単価'),
                    'field'=>'stock_price',
                    'type'=>'numeric',
                    'width'=>'90',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('管理単位'),
                    'field'=>'measure',
                    'align'=>'center',
                    'width'=>'70',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('支給単価'),
                    'field'=>'payout_price',
                    'type'=>'numeric',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('安全在庫数'),
                    'field'=>'safety_stock',
                    'type'=>'numeric',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('課税区分'),
                    'field'=>'show_tax_class',
                    'width'=>'90',
                    'align'=>'center',
                    'editType'=>'select',
                    'editOptions'=> array('0' => _g('課税'), '1' => _g('非課税')),
                    'entryField'=>'tax_class',                    
                    'hide'=>true,
                ),
                array(
                    'label' => _g('税率'),
                    'field' => 'tax_rate',
                    'type' => 'numeric',
                ),
                array(
                    'label'=>_g('所要量計算'),
                    'field'=>'without_mrp_show',
                    'width'=>'70',
                    'align'=>'center',
                    'editType'=>'select',
                    'editOptions'=> array('0' => _g('含める'), '1' => _g('含めない')),
                    'entryField'=>'without_mrp',                    
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('メーカー'),
                    'field'=>'maker_name',
                    'width'=>'100',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('仕様'),
                    'field'=>'spec',
                    'width'=>'100',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('標準ﾛｹｰｼｮﾝ（受入）'),
                    'field'=>'default_location_name',
                    'editType'=>'select',
                    'editOptions'=> $option_location_id,
                    'entryField'=>'default_location_id',                    
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('標準ﾛｹｰｼｮﾝ（使用）'),
                    'field'=>'default_location_name_2',
                    'editType'=>'select',
                    'editOptions'=> $option_location_id,
                    'entryField'=>'default_location_id_2',                    
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('標準ﾛｹｰｼｮﾝ（完成）'),
                    'field'=>'default_location_name_3',
                    'editType'=>'select',
                    'editOptions'=> $option_location_id,
                    'entryField'=>'default_location_id_3',                    
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('棚番'),
                    'field'=>'rack_no',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('入数'),
                    'field'=>'quantity_per_carton',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('受注対象'),
                    'field'=>'received_object_show',
                    'width'=>'70',
                    'align'=>'center',
                    'editType'=>'select',
                    'editOptions'=> array('0' => _g('受注対象'), '1' => _g('対象外')),
                    'entryField'=>'received_object',                    
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('消費期限日数'),
                    'field'=>'use_by_days',
                    'type'=>'numeric',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('ロット頭文字'),
                    'field'=>'lot_header',
                    'align'=>'center',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('標準加工時間(分)'),
                    'field'=>'default_work_minute',
                    'type'=>'numeric',
                    'editType'=>'none',     // この項目は集計値を表示しているのでダイレクト更新は不可
                    'hide'=>true,
                ),
                array(
                    'label'=>sprintf(_g('標準工賃(%s)'),$keyCurrency),
                    'field'=>'default_work_price',
                    'type'=>'numeric',
                    'editType'=>'none',     // この項目は集計値を表示しているのでダイレクト更新は不可
                    'hide'=>true,
                ),
                array(
                    'label'=>sprintf(_g('固定経費(%s)'),$keyCurrency),
                    'field'=>'overhead_cost',
                    'type'=>'numeric',
                    'editType'=>'none',     // この項目は集計値を表示しているのでダイレクト更新は不可
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('非表示'),
                    'field'=>'show_end_item',
                    'width'=>'40',
                    'align'=>'center',
                    'editType'=>'select',
                    'editOptions'=> array('false' => "", 'true' => _g('非表示')),
                    'entryField'=>'end_item',                    
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('ダミー品目'),
                    'field'=>'show_dummy_item',
                    'width'=>'40',
                    'align'=>'center',
                    'editType'=>'select',
                    'editOptions'=> array('false' => "", 'true' => _g('ダミー')),
                    'entryField'=>'dummy_item',                    
                    'hide'=>true,
                ),
//                array(
//                    'label' => _g('最低ロット数'),
//                    'field' => 'default_lot_unit_0',
//                    'type' => 'numeric',
//                ),
//                array(
//                    'label' => _g('手配ロット数'),
//                    'field' => 'default_lot_unit_2_0',
//                    'type' => 'numeric',
//                ),
                array(
                    'label'=>_g('最終入庫日'),
                    'field'=>'last_in_date',
                    'width'=>'100',
                    'type'=>'date',
                    'align'=>'center',
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('最終出庫日'),
                    'field'=>'last_out_date',
                    'width'=>'100',
                    'type'=>'date',
                    'align'=>'center',
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考1'),
                    'field'=>'comment',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考2'),
                    'field'=>'comment_2',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考3'),
                    'field'=>'comment_3',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考4'),
                    'field'=>'comment_4',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考5'),
                    'field'=>'comment_5',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('構成表マスタ'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('構成表マスタ'),
                    'align'=>'center',
                    'link'=>"javascript:goBom('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで構成表マスタ画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('在庫表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('在庫表示'),
                    'align'=>'center',
                    'link'=>"javascript:goStocklist('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで在庫リスト画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('受注表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('受注表示'),
                    'align'=>'center',
                    'link'=>"javascript:goReceived('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで受注画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('納品表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('納品表示'),
                    'align'=>'center',
                    'link'=>"javascript:goDelivery('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで納品画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('製造表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('製造表示'),
                    'align'=>'center',
                    'link'=>"javascript:goManOrder('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで製造指示登録画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('注文表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('注文表示'),
                    'align'=>'center',
                    'link'=>"javascript:goPartnerOrder('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで注文書画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('外製表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('外製表示'),
                    'align'=>'center',
                    'link'=>"javascript:goSubcontract('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで外製指示書画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('入庫表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('入庫表示'),
                    'align'=>'center',
                    'link'=>"javascript:goStockIn('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで入庫画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('出庫表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('出庫表示'),
                    'align'=>'center',
                    'link'=>"javascript:goStockOut('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで出庫画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('棚卸表示'),
                    'width'=>'70',
                    'type' => 'literal',
                    'literal_noEscape' => _g('棚卸表示'),
                    'align'=>'center',
                    'link'=>"javascript:goStockinput('[urlencode:item_code]')",
                    'helpText_noEscape'=>_g('クリックすると別ウィンドウで棚卸登録画面が開きます。'),
                    'editType'=>'none',
                    'hide'=>true,
                ),
            );
    }
}
