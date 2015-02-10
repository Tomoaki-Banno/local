<?php

class Stock_StockProcess_List extends Base_ListBase
{

    function convert($converter, &$form)
    {
        $converter->nullToValue('gen_search_base_date', date('Y-m-d'));
    }

    function setSearchCondition(&$form)
    {
        global $gen_db;

        // 検索条件
        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('日付'),
                'type' => 'calendar',
                'field' => 'base_date',
                'nosql' => true,
            ),
            array(
                'label' => _g('品目集計'),
                'type' => 'select',
                'field' => 'item_sum',
                'options' => Gen_Option::getTrueOrFalse('search'),
                'nosql' => true,
                'default' => 'false',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('品目グループ'),
                'type' => 'select',
                'field' => 'item_group_id',
                'options' => $option_item_group,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
                'hide' => true,
            ),
            array(
                'label' => _g('工程コード/名'),
                'field' => 'process_code',
                'field2' => 'process_name',
            ),
            array(
                'label' => _g('標準外製先コード/名'),
                'field' => 'customer_no',
                'field2' => 'customer_name',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'item_remarks_1',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'item_remarks_2',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'item_remarks_3',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'item_remarks_4',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'item_remarks_5',
                'ime' => 'on',
                'hide' => true,
            ),
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
        
        // 日付情報取得
        $isDate = Gen_String::isDateString(@$form['gen_search_base_date']);
        $date = (Gen_String::isDateString(@$form['gen_search_base_date']) ? date('Y-m-d', strtotime($form['gen_search_base_date'])) : null);

        $query = "
        /* 「実績」「受入」の「最小日付」「最大日付」 */
        create temp view
        order_check_date as (
            select
                t_base.order_header_id
                ,max(max_date) as max_date
                ,min(min_date) as min_date
                ,sum(result_count) as result_count
            from
                (select
                    order_header.order_header_id
                    ,max(achievement_date) as max_date
                    ,min(achievement_date) as min_date
                    ,count(achievement.order_detail_id) as result_count
                from
                    achievement
                    inner join order_detail on achievement.order_detail_id = order_detail.order_detail_id
                    inner join order_header on order_detail.order_header_id = order_header.order_header_id
                where
                    order_header.classification = 0
                group by
                    order_header.order_header_id

                union all
                select
                    order_header.order_header_id
                    ,max(accepted_date) as max_date
                    ,min(accepted_date) as min_date
                    ,count(accepted.order_detail_id) as result_count
                from
                    accepted
                    inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                    inner join order_detail as parent_detail on order_detail.subcontract_parent_order_no = parent_detail.order_no
                    inner join order_header on parent_detail.order_header_id = order_header.order_header_id
                where
                    order_header.classification = 0
                group by
                    order_header.order_header_id
                ) as t_base
            group by
                t_base.order_header_id
        );
        
        /* 「実績」の集計データ */
        create temp view
        t_achievement as (
            select
                achievement.order_detail_id
                ,order_process.process_id
                ,sum(achievement_quantity) as achievement_qty
                ,max(waster_qty) as waster_qty
                ,sum(cost_1) as manufacture_cost_1
                ,sum(cost_2) as manufacture_cost_2
                ,sum(cost_3) as manufacture_cost_3
                ,sum(case when order_process.machining_sequence = t_machining_sequence.max_machining_sequence then achievement_quantity end) as last_achievement_qty
            from
                achievement
                left join (select achievement_id, sum(waster_quantity) as waster_qty from waster_detail group by achievement_id) as t_waster on achievement.achievement_id = t_waster.achievement_id
                left join order_process on achievement.order_detail_id = order_process.order_detail_id and achievement.process_id = order_process.process_id
                left join (select order_detail_id, max(machining_sequence) as max_machining_sequence from order_process
                    group by order_detail_id) as t_machining_sequence on achievement.order_detail_id = t_machining_sequence.order_detail_id
            where
                " . ($isDate ? "achievement_date <= '{$date}'::date" : "1=0") . "
            group by
                achievement.order_detail_id
                ,order_process.process_id
        );

        /* 「受入」の集計データ */
        create temp view
        t_accepted as (
            select
                subcontract_order_process_no
                ,sum(accepted.accepted_quantity) as accepted_qty
                ,sum(case when order_process.machining_sequence = t_machining_sequence.max_machining_sequence then accepted.accepted_quantity end) as last_accepted_qty
            from
                accepted
                left join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                left join order_process on accepted.order_detail_id = order_process.order_detail_id
                left join (select order_detail_id, max(machining_sequence) as max_machining_sequence from order_process
                    group by order_detail_id) as t_machining_sequence on accepted.order_detail_id = t_machining_sequence.order_detail_id
            where
                " . ($isDate ? "accepted_date <= '{$date}'::date" : "1=0") . "
            group by
                subcontract_order_process_no
        );
        ";
        
        $gen_db->query($query);

        if (@$form['gen_search_item_sum'] == "true") {
            // 品目集計する
            $this->selectQuery = "
            select
                item_code
                ,item_name
                ,sum(order_detail_quantity) as order_detail_quantity
                ,sequence_no
                ,process_code
                ,process_name
                ,sum(complete_qty) as complete_qty
                ,sum(waster_qty) as waster_qty
                ,max(default_price) as default_price
                ,max(subcontract_unit_price) as subcontract_unit_price
                ,max(overhead_cost) as overhead_cost
                ,max(manufacture_cost_1) as manufacture_cost_1
                ,max(manufacture_cost_2) as manufacture_cost_2
                ,max(manufacture_cost_3) as manufacture_cost_3
                ,max(base_cost) as base_cost
                ,max(reserve_cost) as reserve_cost
                ,customer_no
                ,customer_name
                ,max(process_remarks_1) as process_remarks_1
                ,max(process_remarks_2) as process_remarks_2
                ,max(process_remarks_3) as process_remarks_3
                ,sum(case when machining_sequence = max_sequence then 0
                    else coalesce(complete_qty,0) - coalesce(after_complete_qty,0) end) as remained_qty
                ,sum((case when machining_sequence = max_sequence then 0
                    else coalesce(complete_qty,0) - coalesce(after_complete_qty,0) end) * reserve_cost) as stock_cost
                ,max(max_date) as max_date
                ,min(completed_flag) as completed_flag
                ,maker_name
                ,spec
                ,item_remarks_1
                ,item_remarks_2
                ,item_remarks_3
                ,item_remarks_4
                ,item_remarks_5
            ";
        } else {
            // 品目集計しない
            $this->selectQuery = "
            select
                order_no
                ,item_code
                ,item_name
                ,order_detail_quantity
                ,sequence_no
                ,process_code
                ,process_name
                ,complete_qty
                ,waster_qty
                ,default_price
                ,subcontract_unit_price
                ,overhead_cost
                ,manufacture_cost_1
                ,manufacture_cost_2
                ,manufacture_cost_3
                ,base_cost
                ,reserve_cost
                ,customer_no
                ,customer_name
                ,process_remarks_1
                ,process_remarks_2
                ,process_remarks_3
                ,(case when machining_sequence = max_sequence then 0
                    else coalesce(complete_qty,0) - coalesce(after_complete_qty,0) end) as remained_qty
                ,(case when machining_sequence = max_sequence then 0
                    else coalesce(complete_qty,0) - coalesce(after_complete_qty,0) end) * reserve_cost as stock_cost
                ,max_date
                ,completed_flag
                ,maker_name
                ,spec
                ,item_remarks_1
                ,item_remarks_2
                ,item_remarks_3
                ,item_remarks_4
                ,item_remarks_5
            ";
        }
        $this->selectQuery .= "
            from (
                select
                    order_detail.order_detail_id
                    ,order_detail.order_no
                    ,order_detail.item_code
                    ,order_detail.item_name
                    ,order_process.machining_sequence
                    ,(order_process.machining_sequence + 1) as sequence_no
                    ,t_achievement.process_id
                    ,process_master.process_code
                    ,process_master.process_name
                    ,order_detail.order_detail_quantity
                    ,coalesce(t_achievement.achievement_qty,0) + coalesce(t_accepted.accepted_qty,0) as complete_qty
                    ,coalesce(t_achievement.waster_qty,0) as waster_qty
                    ,coalesce(order_process.default_work_minute,0) * coalesce(order_process.charge_price,0) as default_price
                    ,coalesce(order_process.subcontract_unit_price,0) as subcontract_unit_price
                    ,coalesce(order_process.overhead_cost,0) as overhead_cost
                    ,coalesce(t_achievement.manufacture_cost_1,0) as manufacture_cost_1
                    ,coalesce(t_achievement.manufacture_cost_2,0) as manufacture_cost_2
                    ,coalesce(t_achievement.manufacture_cost_3,0) as manufacture_cost_3
                    ,coalesce(order_process.default_work_minute,0) * coalesce(order_process.charge_price,0)
                        + coalesce(order_process.subcontract_unit_price,0) + coalesce(order_process.overhead_cost,0) 
                        as base_cost
                    ,t_reserve_cost.reserve_cost
                    ,t_item_process.max_sequence
                    ,order_process.process_remarks_1
                    ,order_process.process_remarks_2
                    ,order_process.process_remarks_3
                    ,customer_master.customer_no
                    ,customer_master.customer_name
                    ,t_item.item_group_id
                    ,t_item.item_group_id_2
                    ,t_item.item_group_id_3
                    ,order_check_date.max_date
                    ,(case when order_detail_completed then 1 else 0 end) as completed_flag
                    ,t_item.maker_name
                    ,t_item.spec
                    ,t_item.comment as item_remarks_1
                    ,t_item.comment_2 as item_remarks_2
                    ,t_item.comment_3 as item_remarks_3
                    ,t_item.comment_4 as item_remarks_4
                    ,t_item.comment_5 as item_remarks_5
                from
                    order_detail
                    inner join order_header on order_detail.order_header_id = order_header.order_header_id
                    left join order_check_date on order_header.order_header_id = order_check_date.order_header_id
                    left join order_process on order_detail.order_detail_id = order_process.order_detail_id
                    left join process_master on order_process.process_id = process_master.process_id
                    left join t_achievement on order_detail.order_detail_id = t_achievement.order_detail_id and process_master.process_id = t_achievement.process_id
                    left join t_accepted on order_process.order_process_no = t_accepted.subcontract_order_process_no
                    left join 
                        (select
                            t01.order_detail_id,
                            t01.machining_sequence,
                            sum(coalesce(t02.default_work_minute,0) * coalesce(t02.charge_price,0)
                                + coalesce(t02.subcontract_unit_price,0) + coalesce(t02.overhead_cost,0)
                                + coalesce(t03.manufacture_cost_1,0)
                                + coalesce(t03.manufacture_cost_2,0)
                                + coalesce(t03.manufacture_cost_3,0)
                                ) as reserve_cost
                        from
                            order_process as t01
                            inner join order_process as t02 on t01.order_detail_id = t02.order_detail_id and t01.machining_sequence >= t02.machining_sequence
                            left join (select order_detail_id, process_id, sum(cost_1) as manufacture_cost_1, sum(cost_2) as manufacture_cost_2, sum(cost_3) as manufacture_cost_3 from achievement group by order_detail_id, process_id) as t03
                                on t02.order_detail_id = t03.order_detail_id and t02.process_id = t03.process_id
                        group by
                            t01.order_detail_id, t01.machining_sequence
                        ) as t_reserve_cost
                        on order_process.order_detail_id = t_reserve_cost.order_detail_id and order_process.machining_sequence = t_reserve_cost.machining_sequence
                    left join (select order_detail_id, max(machining_sequence) as max_sequence from order_process
                        group by order_detail_id) as t_item_process on order_detail.order_detail_id = t_item_process.order_detail_id
                    left join customer_master on order_process.subcontract_partner_id = customer_master.customer_id
                    left join (select item_id, item_group_id, item_group_id_2, item_group_id_3, maker_name, spec, comment, comment_2, comment_3, comment_4, comment_5
                        from item_master) as t_item on order_detail.item_id = t_item.item_id
                where
                    order_header.classification = 0
                    " . ($isDate ? "
                        and result_count > 0
                        and order_header.order_header_id not in (select order_header_id from order_check_date where min_date > '{$date}'::date)
                    " : " and 1=0") . "
                ) as t01
                /* 完了分非表示用（オーダー数より最終工程の実績・受入数が少ないデータを表示する） */
                inner join (
                    select
                        order_detail.order_detail_id
                        ,sum(coalesce(last_achievement_qty,0) + coalesce(last_accepted_qty,0)) as last_quantity
                    from
                        order_detail
                        left join order_process on order_detail.order_detail_id = order_process.order_detail_id
                        left join t_achievement on order_detail.order_detail_id = t_achievement.order_detail_id and order_process.process_id = t_achievement.process_id
                        left join t_accepted on order_process.order_process_no = t_accepted.subcontract_order_process_no
                    group by
                        order_detail.order_detail_id
                ) as t02 on t01.order_detail_id = t02.order_detail_id and t01.order_detail_quantity > t02.last_quantity
                /* 次工程の製造済数（仕掛数計算に使用） */
                left join (
                    select
                        order_detail.order_detail_id
                        ,order_process.process_id
                        /* 工程のとばし登録に対応。ひとつ前の工程を見つける */
                        ,case when machining_sequence > 0 then lag(machining_sequence) over (order by order_detail.order_detail_id, order_process.machining_sequence) end as before_sequence
                        ,coalesce(t_achievement.achievement_qty,0) + coalesce(t_achievement.waster_qty,0) + coalesce(t_accepted.accepted_qty,0) as after_complete_qty
                    from
                        order_detail
                        inner join order_header on order_detail.order_header_id = order_header.order_header_id
                        left join order_process on order_detail.order_detail_id = order_process.order_detail_id
                        left join process_master on order_process.process_id = process_master.process_id
                        left join (select order_detail_id, process_id, sum(achievement_quantity) as achievement_qty, max(waster_qty) as waster_qty from achievement
                            left join (select achievement_id, sum(waster_quantity) as waster_qty from waster_detail group by achievement_id) as t_waster on achievement.achievement_id = t_waster.achievement_id
                            " . ($isDate ? " where achievement_date <= '{$date}'::date" : "") . "
                            group by order_detail_id, process_id
                            ) as t_achievement on order_detail.order_detail_id = t_achievement.order_detail_id and order_process.process_id = t_achievement.process_id
                        left join (select subcontract_order_process_no, sum(accepted.accepted_quantity) as accepted_qty from accepted
                            left join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                            " . ($isDate ? " where accepted_date <= '{$date}'::date" : "") . "
                            group by subcontract_order_process_no
                            ) as t_accepted on order_process.order_process_no = t_accepted.subcontract_order_process_no
                    where
                        classification = 0
                ) as t03 on t01.order_detail_id = t03.order_detail_id and t01.machining_sequence = t03.before_sequence
            [Where]
                " . ($isDate ? "and not (coalesce(max_date,'1970-01-01') <= '{$date}'::date and completed_flag = 1)" : " and 1=0") . "
        ";
        if (@$form['gen_search_item_sum'] == "true") {
            // 品目集計する
            $this->selectQuery .= "
            group by
                item_code
                ,item_name
                ,sequence_no
                ,process_code
                ,process_name
                ,customer_no
                ,customer_name
                ,maker_name
                ,spec
                ,item_remarks_1
                ,item_remarks_2
                ,item_remarks_3
                ,item_remarks_4
                ,item_remarks_5
            ";
        }
        $this->selectQuery .= "
            [Orderby]
        ";

        if (@$form['gen_search_item_sum'] == "true") {
            // 品目集計する
            $this->orderbyDefault = 'item_code, sequence_no';
        } else {
            // 品目集計しない
            $this->orderbyDefault = 'order_no, sequence_no';
        }
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("工程仕掛リスト");
        $form['gen_listAction'] = "Stock_StockProcess_List";
        $form['gen_idField'] = "dummy";
        $form['gen_excel'] = "true";

        $form['gen_returnUrl'] = "index.php?action=Stock_Stocklist_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('在庫リストへ戻る');

        $form['gen_message_noEscape'] = "";

        $form['gen_isClickableTable'] = "false";

        if (@$form['gen_search_item_sum'] == "false") {
            // 品目集計しない
            $form['gen_fixColumnArray'] = array(
                array(
                    'label' => _g('オーダー番号'),
                    'field' => 'order_no',
                    'sameCellJoin' => true,
                ),
            );
        }
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('品目コード'),
            'field' => 'item_code',
            'width' => '120',
            'sameCellJoin' => true,
            'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('品目名'),
            'field' => 'item_name',
            'width' => '150',
            'sameCellJoin' => true,
            'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('メーカー'),
            'field' => 'maker_name',
            'sameCellJoin' => true,
            'hide' => true,
            'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('仕様'),
            'field' => 'spec',
            'sameCellJoin' => true,
            'hide' => true,
            'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('数量'),
            'field' => 'order_detail_quantity',
            'type' => 'numeric',
            'width' => '70',
            'sameCellJoin' => true,
            'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('工程順'),
                'field' => 'sequence_no',
                'type' => 'numeric',
                'width' => '60',
                'align' => 'center',
            ),
            array(
                'label' => _g('工程コード'),
                'field' => 'process_code',
                'width' => '120',
            ),
            array(
                'label' => _g('工程名'),
                'field' => 'process_name',
                'width' => '150',
            ),
            array(
                'label' => _g('完了数'),
                'field' => 'complete_qty',
                'type' => 'numeric',
                'helpText_noEscape' => _g("実績登録画面 [製造数量]、もしくは外製受入登録画面 [受入数]です。"),
            ),
            array(
                'label' => _g('不適合数'),
                'field' => 'waster_qty',
                'type' => 'numeric',
                'helpText_noEscape' => _g("実績登録画面 [不適合数] です。"),
            ),
            array(
                'label' => _g('仕掛在庫数'),
                'field' => 'remained_qty',
                'type' => 'numeric',
                'helpText_noEscape' => _g("完了数 - 次工程の完了数 - 次工程の不適合数 です。") . "<br><br>" . _g("なお、最終工程の場合は0になります（完成品在庫として扱います）。"),
            ),
            array(
                'label' => _g('標準工賃'),
                'field' => 'default_price',
                'type' => 'numeric',
                'helpText_noEscape' => _g("品目マスタ [工賃] × 品目マスタ [標準加工時間] です。"). "<br><br>" . _g("なお、マスタ値は現時点のものではなく、指示書を登録した時点のものが使用されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('外製単価'),
                'field' => 'subcontract_unit_price',
                'type' => 'numeric',
                'helpText_noEscape' => _g("品目マスタ [外製単価] です。"). "<br><br>" . _g("なお、マスタ値は現時点のものではなく、指示書を登録した時点のものが使用されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('固定経費'),
                'field' => 'overhead_cost',
                'type' => 'numeric',
                'helpText_noEscape' => _g("品目マスタ [固定経費] です。"). "<br><br>" . _g("なお、マスタ値は現時点のものではなく、指示書を登録した時点のものが使用されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('製造経費1'),
                'field' => 'manufacture_cost_1',
                'type' => 'numeric',
                'helpText_noEscape' => _g("実績登録画面 [製造経費1] です。"),
                'hide' => true,
            ),
            array(
                'label' => _g('製造経費2'),
                'field' => 'manufacture_cost_2',
                'type' => 'numeric',
                'helpText_noEscape' => _g("実績登録画面 [製造経費2] です。"),
                'hide' => true,
            ),
            array(
                'label' => _g('製造経費3'),
                'field' => 'manufacture_cost_3',
                'type' => 'numeric',
                'helpText_noEscape' => _g("実績登録画面 [製造経費3] です。"),
                'hide' => true,
            ),
            array(
                'label' => _g('工程原単価'),
                'field' => 'base_cost',
                'type' => 'numeric',
                'helpText_noEscape' => _g("(品目マスタ [工賃] × 品目マスタ [標準加工時間]) + 品目マスタ [外製単価] + 品目マスタ [固定経費] です。"). "<br><br>" . _g("実績登録の「製造経費1-3」は含まれていません。"). "<br><br>" . _g("なお、マスタ値は現時点のものではなく、指示書を登録した時点のものが使用されます。"),
            ),
            array(
                'label' => _g('積上原単価'),
                'field' => 'reserve_cost',
                'type' => 'numeric',
                'helpText_noEscape' => _g("最初の工程からその工程までの工程原単価の積算値です。"),
            ),
            array(
                'label' => _g('在庫金額'),
                'field' => 'stock_cost',
                'type' => 'numeric',
                'helpText_noEscape' => _g("仕掛在庫数 × 積上原単価 です。"),
            ),
            array(
                'label' => _g('標準外製先'),
                'field' => 'customer_name',
                'helpText_noEscape' => _g("品目マスタ [外製先] です。"). "<br><br>" . _g("なお、マスタ値は現時点のものではなく、指示書を登録した時点のものが使用されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ1'),
                'field' => 'process_remarks_1',
                'helpText_noEscape' => _g("品目マスタ [工程メモ1] です。"). "<br><br>" . _g("なお、マスタ値は現時点のものではなく、指示書を登録した時点のものが使用されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ2'),
                'field' => 'process_remarks_2',
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ3'),
                'field' => 'process_remarks_3',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'item_remarks_1',
                'sameCellJoin' => true,
                'hide' => true,
                'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'item_remarks_2',
                'sameCellJoin' => true,
                'hide' => true,
                'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'item_remarks_3',
                'sameCellJoin' => true,
                'hide' => true,
                'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'item_remarks_4',
                'sameCellJoin' => true,
                'hide' => true,
                'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'item_remarks_5',
                'sameCellJoin' => true,
                'hide' => true,
                'parentColumn' => (@$form['gen_search_item_sum'] == "true" ? 'item_code' : 'order_no'),
            ),
        );
    }

}