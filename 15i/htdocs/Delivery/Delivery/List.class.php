<?php

class Delivery_Delivery_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;
        
        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        
        $items = Gen_Option::getMonthlyLimit('options');
        $option_monthly_limit = array("" => _g("(すべて)"));
        foreach($items as $key => $val) {
            $option_monthly_limit[$key] = $val;
        }
        
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
            ),
            array(
                'label' => _g('客先注番'),
                'field' => 'customer_received_number',
            ),
            array(
                'label' => _g('受注製番'),
                'field' => 'seiban',
                'hide' => true,
            ),
            array(
                'label' => _g('見積番号'),
                'field' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('納品書番号'),
                'field' => 'delivery_no',
            ),
            array(
                'label' => _g('得意先コード/名'),
                'field' => 't_customer___customer_no',
                'field2' => 't_customer___customer_name',
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 't_customer___customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
                'hide' => true,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_master___item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
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
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'hide' => true,
            ),
            array(
                'label' => _g('出庫ロケーション'),
                'field' => 'location_id',
                'type' => 'select',
                'options' => $option_location_group,
                'hide' => true,
            ),
            array(
                'label' => _g('請求先コード/名'),
                'field' => 't_bill_customer___customer_no',
                'field2' => 't_bill_customer___customer_name',
                'hide' => true,
            ),
            array(
                'label' => _g('発送先コード/名'),
                'field' => 't_delivery_customer___customer_no',
                'field2' => 't_delivery_customer___customer_name',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者名'),
                'field' => 'delivery_header___person_in_charge',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('受注担当者'),
                'field' => 'worker_name',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('納品日'),
                'type' => 'dateFromTo',
                'field' => 'delivery_date',
                'defaultFrom' => date('Y-m-01'),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('検収日'),
                'type' => 'dateFromTo',
                'field' => 'inspection_date',
                'rowSpan' => 2,
                'hide' => true,
            ),
            array(
                'label' => _g('合計金額'),
                'type' => 'numFromTo',
                'field' => 'delivery_note_amount',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('ロット番号'),
                'field' => 'use_lot_no',
                'hide' => true,
            ),
            array(
                'label' => _g('納品備考1'),
                'field' => 'delivery_header___remarks_header',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('納品備考2'),
                'field' => 'delivery_header___remarks_header_2',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('納品備考3'),
                'field' => 'delivery_header___remarks_header_3',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('明細の表示'),
                'type' => 'select',
                'field' => 'show_detail',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
                'nosql' => 'true',
                'default' => 'true',
            ),
            array(
                'label' => _g('印刷状況'),
                'type' => 'select',
                'field' => 'printed',
                'options' => Gen_Option::getPrinted('search'),
                'nosql' => 'true',
                'default' => '0',
                'hide' => true,
            ),
            array(
                'label' => _g('請求状況'),
                'type' => 'select',
                'field' => 'bill_done',
                'options' => array("0" => _g("(すべて)"), "1" => _g("未請求のみ"), "2" => _g("請求済のみ")),
                'nosql' => 'true',
                'default' => '0',
                'hide' => true,
            ),
            array(
                'label' => _g('請求パターン'),
                'type' => 'select',
                'field' => 'delivery_header___bill_pattern',
                'options' => array("" => _g("(すべて)"), "0" => _g("締め(残高表示なし)"), "1" => _g("締め(残高表示あり)"), "2" => _g("都度")),
                'hide' => true,
            ),
            array(
                'label' => _g('締日グループ'),
                'field' => 't_customer___monthly_limit_date',
                'type' => 'select',
                'options' => $option_monthly_limit,
                'hide' => true,
            ),
        );
        // 表示条件クリアの指定がされていたときの設定。
        // 進捗画面のリンク等からレコード指定でこの画面を開いたときのため。
        if (isset($form['gen_searchConditionClear'])) {
            $form['gen_search_show_detail'] = 'true';
            $form['gen_search_printed'] = '0';
            $form['gen_search_bill_done'] = '0';
        }
        
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("日次売上（当月）") => self::_getPreset("5", "gen_all", "delivery_date_day", ""),
                _g("月次売上（今年）") => self::_getPreset("7", "gen_all", "delivery_date_month", ""),
                _g("売上額 前年対比") => self::_getPreset("0", "delivery_date_month", "delivery_date_year", ""),
                _g("得意先売上ランキング（今年）") => self::_getPreset("7", "gen_all", "customer_name", "order by field1 desc"),
                _g("得意先グループ1売上額ランキング（今年）") => self::_getPreset("7", "gen_all", "customer_group_name_1", "order by field1 desc"),
                _g("品目売上ランキング（今年）") => self::_getPreset("7", "gen_all", "item_name", "order by field1 desc"),
                _g("担当者売上ランキング（今年）") => self::_getPreset("7", "gen_all", "worker_name", "order by field1 desc"),
                _g("部門売上ランキング（今年）") => self::_getPreset("7", "gen_all", "section_name", "order by field1 desc"),
                _g("得意先 - 品目（今年）") => self::_getPreset("7", "customer_name", "item_name", ""),
                _g("得意先別月次売上額（今年）") => self::_getPreset("7", "delivery_date_month", "customer_name", ""),
                _g("得意先グループ1月次売上額（今年）") => self::_getPreset("7", "delivery_date_month", "customer_group_name_1", ""),
                _g("品目別月次売上額（今年）") => self::_getPreset("7", "delivery_date_month", "item_name", ""),
                _g("品目別月次納品数量（今年）") => self::_getPreset("7", "delivery_date_month", "item_name", "", "delivery_quantity"),
                _g("担当者別月次売上額（今年）") => self::_getPreset("7", "delivery_date_month", "worker_name", ""),
                _g("部門別月次売上額（今年）") => self::_getPreset("7", "delivery_date_month", "section_name", ""),
                _g("データ入力件数（今年）") => self::_getPreset("7", "delivery_date_month", "gen_record_updater", "", "gen_record_updater", "count"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby, $value = "amount", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "delivery_date", "dp" => $datePattern),
                    array("f" => "show_detail", "v" => "true"),
                    array("f" => "gen_crossTableHorizontal", "v" => $horiz),
                    array("f" => "gen_crossTableVertical", "v" => $vert),
                    array("f" => "gen_crossTableValue", "v" => $value),
                    array("f" => "gen_crossTableMethod", "v" => $method),
                    array("f" => "gen_crossTableChart", "v" => _g("すべて")),
                ),
                "orderby" => $orderby,
            );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        $this->isDetailMode = (@$form['gen_search_show_detail'] == "true" || isset($form['gen_csvMode']));
    }

    function setQueryParam(&$form)
    {
        global $gen_db;

        $classQuery1 = Gen_Option::getBillPattern('list-query');

        if ($this->isDetailMode || isset($form['gen_csvMode'])) {
            // 明細モード
            $this->selectQuery .= "
            select
                delivery_detail.delivery_detail_id
                ,delivery_header.delivery_header_id
                ,delivery_header.delivery_no as delivery_no
                ,delivery_detail.line_no
                ,delivery_header.delivery_date
                ,delivery_header.inspection_date
                ,case
                    when delivery_header.receivable_report_timing = 0 then '" . _g('納品日') . "'
                    when delivery_header.receivable_report_timing = 1 then '" . _g('検収日') . "' end as timing_show
                ,case
                    when delivery_header.receivable_report_timing = 0 and delivery_date is not null then 1
                    when delivery_header.receivable_report_timing = 1 and inspection_date is not null then 1
                    else 0 end as timing_flag
                ,case delivery_header.rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '' end as rounding_show
                ,received_header.received_header_id
                ,received_header.received_number
                ,received_header.customer_received_number
                ,t_estimate.estimate_number
                ,received_header.received_date
                ,received_detail.dead_line
                ,received_detail.seiban
                ,delivery_detail.use_lot_no
                ,t_bill_customer.monthly_limit_date     /* 「請求先」の締日グループであることに注意 */
                ,case t_bill_customer.monthly_limit_date when 31 then '" . _g("末") . "' else cast(t_bill_customer.monthly_limit_date as text) end as monthly_limit_date_show
                ,t_customer.customer_no
                ,t_customer.customer_name
                ,t_customer.template_delivery
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,delivery_header.bill_pattern
                ,case delivery_header.bill_pattern {$classQuery1} end as bill_pattern_show
                ,delivery_header.tax_category
                ,case delivery_header.tax_category
                    when 0 then '" . _g("請求書単位") . "'
                    when 1 then '" . _g("納品書単位") . "'
                    when 2 then '" . _g("納品明細単位") . "'
                    end as tax_category_show
                ,bill_header.bill_number
                ,bill_header.close_date as close_date_show
                ,t_bill_customer.customer_no as bill_customer_no
                ,t_bill_customer.customer_name as bill_customer_name
                ,t_delivery_customer.customer_no as delivery_customer_no
                ,t_delivery_customer.customer_name as delivery_customer_name
                ,received_detail.item_id
                ,item_master.item_code
                ,item_master.item_name
                ,location_code
                ,location_name
                ,delivery_detail.delivery_quantity
                ,measure
                ,delivery_detail.delivery_price
                ,delivery_detail.delivery_amount as amount /* 端数処理にもとづく金額計算 */
                ,delivery_detail.tax_rate
                ,delivery_detail.sales_base_cost_total
                ,delivery_detail.delivery_amount - delivery_detail.sales_base_cost_total as sales_gross_margin
                ,case when delivery_completed then '" . _g("完了") . "' else '' end as delivery_completed
                ,case when delivery_completed then '1' else '' end as delivery_completed_csv
                ,delivery_header.person_in_charge
                ,section_master.section_code
                ,section_master.section_name
                ,t_item_group1.item_group_code as item_group_code1
                ,t_item_group1.item_group_name as item_group_name1
                ,t_item_group2.item_group_code as item_group_code2
                ,t_item_group2.item_group_name as item_group_name2
                ,t_item_group3.item_group_code as item_group_code3
                ,t_item_group3.item_group_name as item_group_name3
                ,delivery_header.remarks_header
                ,delivery_header.remarks_header_2
                ,delivery_header.remarks_header_3
                ,case when coalesce(delivery_header.bill_header_id,0) <> 0 then 'done' else '' end as bill  /* 請求済み判断用 */                
                ,delivery_detail.remarks
                ,case when delivery_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed
                ,worker_master.worker_name
                ,lot_no

                ,item_master.spec as spec
                ,item_master.maker_name as maker_name
                ,item_master.rack_no as rack_no

                ,item_master.comment as item_remarks_1
                ,item_master.comment_2 as item_remarks_2
                ,item_master.comment_3 as item_remarks_3
                ,item_master.comment_4 as item_remarks_4
                ,item_master.comment_5 as item_remarks_5
                
                ,t_customer.remarks as customer_remarks_1
                ,t_customer.remarks_2 as customer_remarks_2
                ,t_customer.remarks_3 as customer_remarks_3
                ,t_customer.remarks_4 as customer_remarks_4
                ,t_customer.remarks_5 as customer_remarks_5

                ,t_delivery_customer.zip as delivery_customer_zip
                ,t_delivery_customer.address1 as delivery_customer_address1
                ,t_delivery_customer.address2 as delivery_customer_address2
                ,t_delivery_customer.tel as delivery_customer_tel
                ,t_delivery_customer.fax as delivery_customer_fax
                ,t_delivery_customer.e_mail as delivery_customer_e_mail
                ,t_delivery_customer.person_in_charge as delivery_customer_person_in_charge
                ,t_delivery_customer.remarks as delivery_customer_remarks_1
                ,t_delivery_customer.remarks_2 as delivery_customer_remarks_2
                ,t_delivery_customer.remarks_3 as delivery_customer_remarks_3
                ,t_delivery_customer.remarks_4 as delivery_customer_remarks_4
                ,t_delivery_customer.remarks_5 as delivery_customer_remarks_5

                -- foreign_currency
                ,currency_name
                ,delivery_header.foreign_currency_rate
                ,foreign_currency_delivery_price
                ,delivery_detail.foreign_currency_delivery_amount as foreign_currency_amount
                ,delivery_detail.foreign_currency_sales_base_cost_total
                ,delivery_detail.foreign_currency_delivery_amount - delivery_detail.foreign_currency_sales_base_cost_total as foreign_currency_sales_gross_margin

                -- for csv
                ,case when delivery_detail.location_id =-1 then '-1' else coalesce(location_code,'') end as location_code_csv
                ,case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_price else delivery_detail.foreign_currency_delivery_price end as delivery_price_for_csv
                ,case when delivery_header.foreign_currency_id is null then delivery_detail.sales_base_cost else delivery_detail.foreign_currency_sales_base_cost end as sales_base_cost_for_csv
                ,null as delivery_regist_for_csv

                ,coalesce(delivery_detail.record_update_date, delivery_detail.record_create_date) as gen_record_update_date
                ,coalesce(delivery_detail.record_updater, delivery_detail.record_creator) as gen_record_updater

            from
                delivery_header
                inner join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                left join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                left join received_header on received_header.received_header_id = received_detail.received_header_id
                left join customer_master as t_customer on received_header.customer_id = t_customer.customer_id
                left join (select 
                        customer_id, customer_no, customer_name,
                        zip, address1, address2, tel, fax, e_mail, person_in_charge,
                        remarks, remarks_2, remarks_3, remarks_4, remarks_5
                    from customer_master) as t_delivery_customer on delivery_header.delivery_customer_id = t_delivery_customer.customer_id
                left join (select customer_id, customer_no, customer_name, monthly_limit_date from customer_master) as t_bill_customer on delivery_header.bill_customer_id = t_bill_customer.customer_id
                left join customer_group_master as t_customer_group_1 on t_customer.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on t_customer.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on t_customer.customer_group_id_3 = t_customer_group_3.customer_group_id
                left join item_master on received_detail.item_id = item_master.item_id
                left join (select location_id as lid, location_code, location_name from location_master) as t_loc on delivery_detail.location_id = t_loc.lid
                left join section_master on received_header.section_id = section_master.section_id
                left join item_group_master as t_item_group1 on item_master.item_group_id = t_item_group1.item_group_id
                left join item_group_master as t_item_group2 on item_master.item_group_id_2 = t_item_group2.item_group_id
                left join item_group_master as t_item_group3 on item_master.item_group_id_3 = t_item_group3.item_group_id
                left join currency_master on delivery_header.foreign_currency_id = currency_master.currency_id
                left join (select estimate_header_id, estimate_number from estimate_header) as t_estimate on received_header.estimate_header_id = t_estimate.estimate_header_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                /* 請求情報 */
                left join bill_header on delivery_header.bill_header_id = bill_header.bill_header_id
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
            [Where]
             	" . ($form['gen_search_printed'] == '1' ? ' and not coalesce(delivery_printed_flag,false)' : '') . "
             	" . ($form['gen_search_printed'] == '2' ? ' and delivery_printed_flag' : '') . "
             	" . ($form['gen_search_bill_done'] == '1' ? " and coalesce(delivery_header.bill_header_id,0) = 0" : "") . "
             	" . ($form['gen_search_bill_done'] == '2' ? " and coalesce(delivery_header.bill_header_id,0) <> 0" : "") . "
            [Orderby]
            ";
        } else {
            // ヘッダモード
            $this->selectQuery .= "
            select
                delivery_header.delivery_header_id
                ,max(delivery_header.delivery_no) as delivery_no
                ,count(delivery_detail.*) as detail_count
                ,max(t_estimate.estimate_number) as estimate_number
                ,max(received_header.received_date) as received_date
                ,max(delivery_header.delivery_date) as delivery_date
                ,max(delivery_header.inspection_date) as inspection_date
                ,max(case
                    when delivery_header.receivable_report_timing = 0 then '" . _g('納品日') . "'
                    when delivery_header.receivable_report_timing = 1 then '" . _g('検収日') . "' end) as timing_show
                ,max(case
                    when delivery_header.receivable_report_timing = 0 and delivery_date is not null then 1
                    when delivery_header.receivable_report_timing = 1 and inspection_date is not null then 1
                    else 0 end) as timing_flag
                ,max(case delivery_header.rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '' end) as rounding_show
                ,max(amount_header) as amount_header
                ,max(t_amount.sales_base_cost_total_header) as sales_base_cost_total_header
                ,max(t_amount.sales_gross_margin_header) as sales_gross_margin_header
                ,max(currency_master.currency_name) as currency_name
                ,max(t_amount.foreign_currency_delivery_amount_header) as foreign_currency_delivery_amount_header
                ,max(t_amount.foreign_currency_sales_base_cost_total_header) as foreign_currency_sales_base_cost_total_header
                ,max(t_amount.foreign_currency_sales_gross_margin_header) as foreign_currency_sales_gross_margin_header
                ,max(t_customer.customer_no) as customer_no
                ,max(t_customer.customer_name) as customer_name
                ,max(t_customer.template_delivery) as template_delivery
                /* 「請求先」の締日グループであることに注意 */
                ,max(case t_bill_customer.monthly_limit_date when 31 then '" . _g("末") . "' else cast(t_bill_customer.monthly_limit_date as text) end) as monthly_limit_date_show
                ,max(t_customer_group_1.customer_group_code) as customer_group_code_1
                ,max(t_customer_group_1.customer_group_name) as customer_group_name_1
                ,max(t_customer_group_2.customer_group_code) as customer_group_code_2
                ,max(t_customer_group_2.customer_group_name) as customer_group_name_2
                ,max(t_customer_group_3.customer_group_code) as customer_group_code_3
                ,max(t_customer_group_3.customer_group_name) as customer_group_name_3
                ,max(delivery_header.bill_pattern) as bill_pattern
                ,max(case delivery_header.bill_pattern {$classQuery1} end) as bill_pattern_show
                ,max(delivery_header.tax_category) as tax_category
                ,max(case delivery_header.tax_category
                    when 0 then '" . _g("請求書単位") . "'
                    when 1 then '" . _g("納品書単位") . "'
                    when 2 then '" . _g("納品明細単位") . "'
                    end) as tax_category_show
                ,max(bill_header.bill_number) as bill_number
                ,max(bill_header.close_date) as close_date_show
                ,max(t_bill_customer.customer_no) as bill_customer_no
                ,max(t_bill_customer.customer_name) as bill_customer_name
                ,max(t_delivery_customer.customer_no) as delivery_customer_no
                ,max(t_delivery_customer.customer_name) as delivery_customer_name
                ,max(delivery_header.person_in_charge) as person_in_charge
                ,max(delivery_header.remarks_header) as remarks_header
                ,max(delivery_header.remarks_header_2) as remarks_header_2
                ,max(delivery_header.remarks_header_3) as remarks_header_3
                ,max(case when coalesce(delivery_header.bill_header_id,0) <> 0 then 'done' else '' end) as bill /* 請求済み判断用 */
                ,max(case when delivery_printed_flag = true then '" . _g("印刷済") . "' else '' end ) as printed
                ,max(t_customer.remarks) as customer_remarks_1
                ,max(t_customer.remarks_2) as customer_remarks_2
                ,max(t_customer.remarks_3) as customer_remarks_3
                ,max(t_customer.remarks_4) as customer_remarks_4
                ,max(t_customer.remarks_5) as customer_remarks_5
                
                ,max(t_delivery_customer.zip) as delivery_customer_zip
                ,max(t_delivery_customer.address1) as delivery_customer_address1
                ,max(t_delivery_customer.address2) as delivery_customer_address2
                ,max(t_delivery_customer.tel) as delivery_customer_tel
                ,max(t_delivery_customer.fax) as delivery_customer_fax
                ,max(t_delivery_customer.e_mail) as delivery_customer_e_mail
                ,max(t_delivery_customer.person_in_charge) as delivery_customer_person_in_charge
                ,max(t_delivery_customer.remarks) as delivery_customer_remarks_1
                ,max(t_delivery_customer.remarks_2) as delivery_customer_remarks_2
                ,max(t_delivery_customer.remarks_3) as delivery_customer_remarks_3
                ,max(t_delivery_customer.remarks_4) as delivery_customer_remarks_4
                ,max(t_delivery_customer.remarks_5) as delivery_customer_remarks_5

                ,max(coalesce(delivery_detail.record_update_date, delivery_detail.record_create_date)) as gen_record_update_date
                ,max(coalesce(delivery_detail.record_updater, delivery_detail.record_creator)) as gen_record_updater

            from
                delivery_header
                inner join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                left join (select 
                        delivery_header_id
                        ,sum(delivery_amount) as amount_header 
                        ,sum(sales_base_cost_total) as sales_base_cost_total_header 
                        ,sum(delivery_amount - sales_base_cost_total) as sales_gross_margin_header 
                        ,sum(foreign_currency_delivery_amount) as foreign_currency_delivery_amount_header 
                        ,sum(foreign_currency_sales_base_cost_total) as foreign_currency_sales_base_cost_total_header 
                        ,sum(foreign_currency_delivery_amount - foreign_currency_sales_base_cost_total) as foreign_currency_sales_gross_margin_header 
                    from delivery_detail
                    group by delivery_header_id) as t_amount on delivery_header.delivery_header_id = t_amount.delivery_header_id
                left join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                left join received_header on received_header.received_header_id = received_detail.received_header_id
                left join customer_master as t_customer on received_header.customer_id = t_customer.customer_id
                left join customer_group_master as t_customer_group_1 on t_customer.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on t_customer.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on t_customer.customer_group_id_3 = t_customer_group_3.customer_group_id
                left join (select 
                        customer_id, customer_no, customer_name,
                        zip, address1, address2, tel, fax, e_mail, person_in_charge, 
                        remarks, remarks_2, remarks_3, remarks_4, remarks_5
                    from customer_master) as t_delivery_customer on delivery_header.delivery_customer_id = t_delivery_customer.customer_id
                left join (select customer_id, customer_no, customer_name, monthly_limit_date from customer_master) as t_bill_customer on delivery_header.bill_customer_id = t_bill_customer.customer_id
                left join item_master on received_detail.item_id = item_master.item_id
                left join (select estimate_header_id, estimate_number from estimate_header) as t_estimate on received_header.estimate_header_id = t_estimate.estimate_header_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join currency_master on delivery_header.foreign_currency_id = currency_master.currency_id
                /* 請求情報 */
                left join bill_header on delivery_header.bill_header_id = bill_header.bill_header_id            
            [Where]
             	" . ($form['gen_search_printed'] == '1' ? ' and not coalesce(delivery_printed_flag,false)' : '') . "
             	" . ($form['gen_search_printed'] == '2' ? ' and delivery_printed_flag' : '') . "
             	" . ($form['gen_search_bill_done'] == '1' ? " and coalesce(delivery_header.bill_header_id,0) = 0" : "") . "
             	" . ($form['gen_search_bill_done'] == '2' ? " and coalesce(delivery_header.bill_header_id,0) <> 0" : "") . "
            group by
                delivery_header.delivery_header_id
            [Orderby]
            ";
        }

        $this->orderbyDefault = "delivery_header.delivery_no desc" . ($this->isDetailMode ? ", delivery_detail.line_no" : "");
        if ($this->isDetailMode) {
            $this->customColumnTables = array(
                // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
                //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
                //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
                //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
                //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
                //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
                //          estimate_detail, received_detail, delivery_detail, order_detail
                array("received_header", "", "", "delivery_header_id", true),
                array("item_master", "", "", "delivery_detail_id"),
                array("customer_master", "t_customer", "", "received_header_id"),
                array("section_master", "", "", "received_header_id"),
                array("worker_master", "", "", "received_header_id"),
            );        
        }
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("納品");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）") . "<br><br>" .
                _g("※1行ごとに別個の納品書としてインポートされます。") . "<br>" .
                _g("　複数の明細行を持つデータを作成することはできません。");
        $form['gen_allowUpdateCheck'] = false;

        // 通知メールの設定（成功時のみ）
        $form['gen_csvAlertMail_id'] = 'delivery_delivery_new';   // Master_AlertMail_Edit冒頭を参照
        $form['gen_csvAlertMail_title'] = _g("納品登録");
        $form['gen_csvAlertMail_body'] = _g("納品データがCSVインポートされました。");    // インポート件数等が自動的に付加される

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('受注製番'),
                'field' => 'seiban',
            ),
            array(
                'label' => _g('納品書番号'),
                'field' => 'delivery_no',
            ),
            array(
                'label' => _g('納品日'),
                'field' => 'delivery_date',
            ),
            array(
                'label' => _g('検収日'),
                'field' => 'inspection_date',
            ),
            array(
                'label' => _g('納品数'),
                'field' => 'delivery_quantity',
            ),
            array(
                'label' => _g('納品単価'),
                'field' => 'delivery_price',
                'exportField' => 'delivery_price_for_csv',
            ),
            array(
                'label' => _g('販売原単価'),
                'field' => 'sales_base_cost',
                'exportField' => 'sales_base_cost_for_csv',
            ),
            array(
                'label' => _g('税率'),
                'field' => 'tax_rate',
            ),
            array(
                'label' => _g('出庫ロケーションコード'),
                'addLabel' => sprintf(_g('(空欄：「%s」、-1：「(品目の標準ロケ)」)'), _g(GEN_DEFAULT_LOCATION_NAME)),
                'field' => 'location_code',
                'exportField' => 'location_code_csv',
            ),
            array(
                'label' => _g('ロット番号'),
                'field' => 'use_lot_no',
            ),
            array(
                'label' => _g('完了'),
                'addLabel' => _g('(1なら完了)'),
                'field' => 'delivery_completed',
                'exportField' => 'delivery_completed_csv',
            ),
            array(
                'label' => _g('自社担当者名'),
                'field' => 'person_in_charge',
            ),
            array(
                'label' => _g('納品明細備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("納品登録");
        $form['gen_menuAction'] = "Menu_Delivery";
        $form['gen_listAction'] = "Delivery_Delivery_List";
        $form['gen_editAction'] = "Delivery_Delivery_Edit";
        $form['gen_idField'] = ($this->isDetailMode ? 'delivery_detail_id' : 'delivery_header_id');
        $form['gen_idFieldForUpdateFile'] = "delivery_header.delivery_header_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("納品");

        $form['gen_checkAndDoLinkArray'] = array(
            array(
                'id' => 'bulkInspectionDate',
                'value' => _g('一括検収登録'),
                'onClick' => "javascript:bulkInspection();",
            ),
        );
        $form['gen_goLinkArray'] = array(
            array(
                'id' => 'bulkEdit',
                'value' => _g('一括納品登録'),
                'onClick' => "javascript:location.href='index.php?action=Delivery_Delivery_BulkEdit'",
            ),
            array(
                'id' => 'barcodeAccept',
                'value' => _g('バーコード登録'),
                'onClick' => "javascript:gen.modal.open('index.php?action=Delivery_Delivery_BarcodeEdit')",
            ),
        );

        $form['gen_reportArray'] = array(
            array(
                'label' => _g('納品書 印刷'),
                'link' => "javascript:gen.list.printReport('Delivery_Delivery_Report" . ($this->isDetailMode ? "&detail=true" : "") . "','check')",
                'reportEdit' => 'Delivery_Delivery_Report'
            ),
            array(
                'label' => _g('請求書（都度） 発行'),
                'link' => "javascript:printBillReport()",
                'reportEdit' => 'Monthly_Bill_Report'
            ),
        );
        $form['gen_javascript_noEscape'] = "
            function bulkInspection(){
                var inputDate = '';
                var frm = gen.list.table.getCheckedPostSubmit('chk_ins');
                if (frm.count == 0) {
                    alert('" . _g("一括検収するデータを選択してください。") . "');
                } else {
                    while(true) {
                    	inputDate = window.prompt('" . _g('検収日を入力してください（空欄にすると検収日の登録を削除します）') . "\\n" .
                            _g('売上計上基準が“検収日”の場合、検収日のレートが適用されます。') . "', gen.date.getCalcDateStr(0));
                    	if (inputDate===null) return;
                    	if (inputDate=='' || gen.date.isDate(inputDate)) break;
                        alert('" . _g('日付が正しくありません。') . "');
                    }
                    if (inputDate == '') {
                        if (!confirm('" . _g('日付が指定されていませんので、検収日の登録が削除されます。本当に実行してよろしいですか？') . "')) {
                            return;
                        }
                    }
                    var postUrl = 'index.php?action=Delivery_Delivery_BulkInspection';
                    postUrl += '&inspection_date=' + inputDate" . ($this->isDetailMode ? " + '&detail=true'" : "") . ";
                    frm.submit(postUrl);
                    // 画面更新とwaitダイアログ表示。listUpdateによるAjax更新はBulkInspectionクラスの処理が終わるまで
                    // session_start()で足止めになるので、結果として処理が終わるまでダイアログが出たままとなる。
                    listUpdate(null, false);
                }
            }

            function printBillReport() {
                var frm = gen.list.table.getCheckedPostSubmit('chk_bill');
                var inputDate = '';
                if (frm.count == 0) {
                    alert('" . _g("請求するデータを選択してください。") . "');
                } else {
                    while(true) {
                    	inputDate = window.prompt('" . _g('請求日を入力してください。') . "', gen.date.getCalcDateStr(0));
                    	if (inputDate===null) return;
                    	if (gen.date.isDate(inputDate)) break;
                        alert('" . _g('請求日が正しくありません。') . "');
                    }
                    gen.list.printReport('Monthly_Bill_Report&is_delivery=true&close_date=' + inputDate + '" . ($this->isDetailMode ? "&detail=true" : "") . "','chk_bill');
                }
            }

            function goReceived(receivedHeaderId) {
                gen.modal.open('index.php?action=Manufacturing_Received_Edit&received_header_id=' + receivedHeaderId);
            }
            
            function goBillList(billNumber) {
                window.open('index.php?action=Monthly_Bill_BillList&gen_searchConditionClear&gen_search_bill_number_from=' + billNumber + '&gen_search_bill_number_to=' + billNumber);
            }
             
            function showItemMaster(itemId) {
                gen.modal.open('index.php?action=Master_Item_Edit&item_id=' + itemId);
            }
       ";

        $form['gen_isClickableTable'] = "true";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[bill]'=='done'", // 請求済み（シルバー）
        );
        if ($this->isDetailMode) {
            $form['gen_rowColorCondition']['#f9bdbd'] = "'[delivery_quantity]'<'0'";     // 赤伝票
        }

        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("請求済み")),
        );
        if ($this->isDetailMode) {
            $form['gen_colorSample']["f9bdbd"] = array(_g("ピンク"), _g("赤伝票"));
        }

        //  モードにより動的に列を切り替える場合、モードごとに列情報（列順、列幅、ソートなど）を別々に保持できるよう、次の設定が必要。
        $form['gen_columnMode'] = ($this->isDetailMode ? "detail" : "list");

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Delivery_Delivery_BulkDelete' . ($this->isDetailMode ? "&detail=true" : ""),
                'beforeAction' => 'Delivery_Delivery_AjaxPrintedCheck', // 印刷済チェック
                'beforeDetail' => ($this->isDetailMode ? 'true' : 'false'), // 印刷済チェック用
                // readonlyであれば表示しない
                // また請求済みレコードには表示しない。
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " && '[bill]'!='done'",
            ),
            array(
                'label' => _g("印刷"),
                'name' => 'check', // 印刷チェックボックスのnameはcheckである必要がある
                'type' => 'checkbox',
                'sameCellJoin' => true,
                'parentColumn' => 'delivery_no',
            ),
            array(
                'label' => _g('印刷済'),
                'field' => 'printed',
                'width' => '47',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'delivery_no',
                'helpText_noEscape' => _g("未印刷であっても、データとしては確定扱いです。") . "<br>" . _g("印刷済データを修正した場合、未印刷に戻ります。"),
            ),
            array(
                'label' => _g("検収"),
                'name' => 'chk_ins', // 印刷以外のチェックボックスのnameは「check_」ではじまっていてはいけない
                'type' => 'checkbox',
                // readonlyであれば表示しない。
                // また請求済みレコードには表示しない。検収基準のときに請求済みレコードの検収日を変更されると納品額と請求額に矛盾が発生するため
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " && '[bill]'!='done'",
            ),
            array(
                'label' => _g("請求"),
                'name' => 'chk_bill', // 印刷以外のチェックボックスのnameは「check_」ではじまっていてはいけない
                'type' => 'checkbox',
                // readonlyであれば表示しない。
                // また請求済みレコードには表示しない。
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " && '[bill_pattern]'=='2' && '[bill]'!='done' && '[timing_flag]'=='1'",
            ),
            array(
                'label' => _g('納品書番号'),
                'field' => 'delivery_no',
                'width' => '80',
                'align' => 'center',
                'cellId' => 'check_[id]_printed', //印刷時書き換え用
                'sameCellJoin' => true
            ),
            array(
                'label' => _g('請求書番号'),
                'field' => 'bill_number',
                'width' => '80',
                'align' => 'center',
                'cellId' => 'chk_bill_[id]_printed', //印刷時書き換え用
                'sameCellJoin' => true,
                'parentColumn' => 'delivery_no',
                'link' => "javascript:goBillList('[urlencode:bill_number]')",
                'linkCondition' => "'[bill_number]'!=''",
                'linkDisableColor' => "#000000",
                'helpText_noEscape' => _g('クリックすると別ウィンドウで請求書リスト画面が開きます。'),
            ),
        );
        if (!$this->isDetailMode) {
            // ヘッダモード
            $form['gen_columnArray'] = array(
                array(
                    'label' => _g('見積番号'),
                    'field' => 'estimate_number',
                    'width' => '110',
                    'align' => 'center',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品日'),
                    'field' => 'delivery_date',
                    'type' => 'date',
                ),
                array(
                    'label' => _g('検収日'),
                    'field' => 'inspection_date',
                    'type' => 'date',
                ),
                array(
                    'label' => _g('請求日'),
                    'field' => 'close_date_show',
                    'type' => 'date',
                ),
                array(
                    'label' => _g('明細行数'),
                    'field' => 'detail_count',
                    'width' => '60',
                    'type' => 'numeric',
                ),
                array(
                    'label' => _g('受注日'),
                    'field' => 'received_date',
                    'type' => 'date',
                    'hide' => true,
                ),
                array(
                    'label' => _g('得意先コード'),
                    'field' => 'customer_no',
                    'width' => '80',
                    'hide' => true,
                ),
                array(
                    'label' => _g('得意先名'),
                    'field' => 'customer_name',
                ),
                array(
                    'label' => _g('取引先グループコード1'),
                    'field' => 'customer_group_code_1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名1'),
                    'field' => 'customer_group_name_1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループコード2'),
                    'field' => 'customer_group_code_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名2'),
                    'field' => 'customer_group_name_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループコード3'),
                    'field' => 'customer_group_code_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名3'),
                    'field' => 'customer_group_name_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('請求先コード'),
                    'field' => 'bill_customer_no',
                    'width' => '80',
                    'hide' => true,
                ),
                array(
                    'label' => _g('請求先名'),
                    'field' => 'bill_customer_name',
                    'hide' => true,
                ),
                array(
                    'label' => _g('売上計上基準'),
                    'field' => 'timing_show',
                    'width' => '90',
                    'align' => 'center',
                    'hide' => true,
                ),
                 array(
                    'label' => _g('請求パターン'),
                    'field' => 'bill_pattern_show',
                    'width' => '130',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('締日グループ'),
                    'field' => 'monthly_limit_date_show',
                    'width' => '80',
                    'align' => 'center',
                    'hide' => true,
                    'helpText_noEscape' => _g("請求先の締日グループです。"),
                ),
                array(
                    'label' => _g('税計算単位'),
                    'field' => 'tax_category_show',
                    'width' => '90',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('端数処理'),
                    'field' => 'rounding_show',
                    'width' => '80',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先コード'),
                    'field' => 'delivery_customer_no',
                    'width' => '80',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先名'),
                    'field' => 'delivery_customer_name',
                    'hide' => true,
                ),
                array(
                    'label' => _g('担当者名'),
                    'field' => 'person_in_charge',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('合計金額'),
                    'field' => 'amount_header',
                    'type' => 'numeric',
                ),
                array(
                    'label' => _g('販売原価'),
                    'field' => 'sales_base_cost_total_header',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('販売粗利'),
                    'field' => 'sales_gross_margin_header',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引通貨'),
                    'field' => 'currency_name',
                    'width' => '50',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('売上金額(外貨)'),
                    'field' => 'foreign_currency_delivery_amount_header',
                    'type' => 'numeric',
                    'width' => '100',
                    'hide' => true,
                ),
                array(
                    'label' => _g('販売原価(外貨)'),
                    'field' => 'foreign_currency_sales_base_cost_total_header',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('販売粗利(外貨)'),
                    'field' => 'foreign_currency_sales_gross_margin_header',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('帳票テンプレート'),
                    'field' => 'template_delivery',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'helpText_noEscape' => _g("取引先マスタの [帳票(納品書)] です。指定されている場合はそのテンプレートが使用されます。未指定の場合、テンプレート設定画面で選択されたテンプレートが使用されます。"),
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品備考1'),
                    'field' => 'remarks_header',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品備考2'),
                    'field' => 'remarks_header_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品備考3'),
                    'field' => 'remarks_header_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考1'),
                    'field' => 'customer_remarks_1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考2'),
                    'field' => 'customer_remarks_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考3'),
                    'field' => 'customer_remarks_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考4'),
                    'field' => 'customer_remarks_4',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考5'),
                    'field' => 'customer_remarks_5',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先郵便番号'),
                    'field' => 'delivery_customer_zip',
                    'hide' => true,
                ),
                // 以下の項目　ag.cgi?page=ProjectDocView&pPID=1516&pbid=205550
                array(
                    'label' => _g('発送先住所1'),
                    'field' => 'delivery_customer_address1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先住所2'),
                    'field' => 'delivery_customer_address2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先TEL'),
                    'field' => 'delivery_customer_tel',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先FAX'),
                    'field' => 'delivery_customer_fax',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先メールアドレス'),
                    'field' => 'delivery_customer_e_mail',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先担当者'),
                    'field' => 'delivery_customer_person_in_charge',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考1'),
                    'field' => 'delivery_customer_remarks_1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考2'),
                    'field' => 'delivery_customer_remarks_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考3'),
                    'field' => 'delivery_customer_remarks_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考4'),
                    'field' => 'delivery_customer_remarks_4',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考5'),
                    'field' => 'delivery_customer_remarks_5',
                    'hide' => true,
                ),
            );
        } else {
            // 明細モード
            $form['gen_columnArray'] = array(
                array(
                    'label' => _g('行'),
                    'field' => 'line_no',
                    'width' => '50',
                    'align' => 'center',
                ),
                array(
                    'label' => _g('納品日'),
                    'field' => 'delivery_date',
                    'type' => 'date',
                ),
                array(
                    'label' => _g('検収日'),
                    'field' => 'inspection_date',
                    'type' => 'date',
                ),
                array(
                    'label' => _g('請求日'),
                    'field' => 'close_date_show',
                    'type' => 'date',
                ),
                array(
                    'label' => _g('受注番号'),
                    'field' => 'received_number',
                    'width' => '100',
                    'align' => 'center',
                    'link' => "javascript:goReceived('[urlencode:received_header_id]')",
                    'linkCondition' => "'[received_header_id]'!=''",
                    'linkDisableColor' => "#000000",
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                ),
                array(
                    'label' => _g('見積番号'),
                    'field' => 'estimate_number',
                    'width' => '110',
                    'align' => 'center',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('客先注番'),
                    'field' => 'customer_received_number',
                    'width' => '100',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注日'),
                    'field' => 'received_date',
                    'type' => 'date',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注納期'),
                    'field' => 'dead_line',
                    'type' => 'date',
                    'hide' => true,
                ),
                array(
                    'label' => _g('製番'),
                    'field' => 'seiban',
                    'width' => '100',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('得意先コード'),
                    'field' => 'customer_no',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('得意先名'),
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'field' => 'customer_name',
                ),
                array(
                    'label' => _g('取引先グループコード1'),
                    'field' => 'customer_group_code_1',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名1'),
                    'field' => 'customer_group_name_1',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループコード2'),
                    'field' => 'customer_group_code_2',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名2'),
                    'field' => 'customer_group_name_2',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループコード3'),
                    'field' => 'customer_group_code_3',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名3'),
                    'field' => 'customer_group_name_3',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('請求先コード'),
                    'field' => 'bill_customer_no',
                    'width' => '80',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('請求先名'),
                    'field' => 'bill_customer_name',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                ),
                array(
                    'label' => _g('売上計上基準'),
                    'field' => 'timing_show',
                    'width' => '90',
                    'align' => 'center',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('請求パターン'),
                    'field' => 'bill_pattern_show',
                    'width' => '130',
                    'align' => 'center',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('締日グループ'),
                    'field' => 'monthly_limit_date_show',
                    'width' => '80',
                    'align' => 'center',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                    'helpText_noEscape' => _g("請求先の締日グループです。"),
                ),
                array(
                    'label' => _g('税計算単位'),
                    'field' => 'tax_category_show',
                    'width' => '90',
                    'align' => 'center',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('端数処理'),
                    'field' => 'rounding_show',
                    'width' => '80',
                    'align' => 'center',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先コード'),
                    'field' => 'delivery_customer_no',
                    'width' => '80',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先名'),
                    'field' => 'delivery_customer_name',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目コード'),
                    'field' => 'item_code',
                ),
                array(
                    'label' => _g('品目名'),
                    'field' => 'item_name',
                ),
                array(
                    'label' => _g('品目マスタ'),
                    'width' => '40',
                    'type' => 'literal',
                    'literal_noEscape' => "<img src='img/application-form.png' class='gen_cell_img'>",
                    'align' => 'center',
                    'link' => "javascript:showItemMaster('[item_id]')",
                    'hide' => true,
                ),
                array(
                    'label' => _g('出庫ロケーション'),
                    'field' => 'location_name',
                    'hide' => true,
                ),
                array(
                    'label' => _g('数量'),
                    'field' => 'delivery_quantity',
                    'type' => 'numeric',
                ),
                array(
                    'label' => _g('単位'),
                    'field' => 'measure',
                    'type' => 'data',
                    'width' => '35',
                ),
                array(
                    'label' => _g('税率'),
                    'field' => 'tax_rate',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品単価'),
                    'field' => 'delivery_price',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('売上金額'),
                    'field' => 'amount',
                    'type' => 'numeric',
                ),
                array(
                    'label' => _g('販売原価'),
                    'field' => 'sales_base_cost_total',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('販売粗利'),
                    'field' => 'sales_gross_margin',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引通貨'),
                    'field' => 'currency_name',
                    'width' => '50',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('レート'),
                    'field' => 'foreign_currency_rate',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品単価(外貨)'),
                    'field' => 'foreign_currency_delivery_price',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('売上金額(外貨)'),
                    'field' => 'foreign_currency_amount',
                    'type' => 'numeric',
                    'width' => '100',
                    'hide' => true,
                ),
                array(
                    'label' => _g('販売原価(外貨)'),
                    'field' => 'foreign_currency_sales_base_cost_total',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('販売粗利(外貨)'),
                    'field' => 'foreign_currency_sales_gross_margin',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('部門コード'),
                    'field' => 'section_code',
                    'width' => '70',
                    'hide' => true,
                ),
                array(
                    'label' => _g('部門名'),
                    'field' => 'section_name',
                    'width' => '120',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目グループコード1'),
                    'field' => 'item_group_code1',
                    'width' => '120',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目グループ名1'),
                    'field' => 'item_group_name1',
                    'width' => '120',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目グループコード2'),
                    'field' => 'item_group_code2',
                    'width' => '120',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目グループ名2'),
                    'field' => 'item_group_name2',
                    'width' => '120',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目グループコード3'),
                    'field' => 'item_group_code3',
                    'width' => '120',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目グループ名3'),
                    'field' => 'item_group_name3',
                    'width' => '120',
                    'hide' => true,
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
                    'label' => _g('棚番'),
                    'field' => 'rack_no',
                    'hide' => true,
                ),
                array(
                    'label' => _g('完了'),
                    'field' => 'delivery_completed',
                    'width' => '50',
                    'align' => 'center',
                ),
                array(
                    'label' => _g('担当者名'),
                    'field' => 'person_in_charge',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注担当者'),
                    'field' => 'worker_name',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('ロット番号'),
                    'field' => 'use_lot_no',
                    'width' => '80',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label'=>_g('出庫ロット'),
                    'field'=>'lot_no',
                    'width'=>'200',
                    'hide'=>true,
                    'helpText_noEscape'=>_g("品目マスタ「管理区分」が「ロット」の品目だけが対象です。受注登録画面で引き当てられたロットを表示します。分納の場合、すべての納品に同じロットが表示されます。") . "<br><br>"
                        ._g("「ロット番号」列は納品登録画面で手入力したロット番号（簡易ロット機能）、「出庫ロット」列は受注登録画面で引き当てたロットです。")
                ),
                array(
                    'label' => _g('帳票テンプレート'),
                    'field' => 'template_delivery',
                    'sameCellJoin' => true,
                    'parentColumn' => 'delivery_no',
                    'helpText_noEscape' => _g("取引先マスタの [帳票(納品書)] です。指定されている場合はそのテンプレートが使用されます。未指定の場合、テンプレート設定画面で選択されたテンプレートが使用されます。"),
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品備考1'),
                    'field' => 'remarks_header',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品備考2'),
                    'field' => 'remarks_header_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品備考3'),
                    'field' => 'remarks_header_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品明細備考'),
                    'field' => 'remarks',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考1'),
                    'field' => 'customer_remarks_1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考2'),
                    'field' => 'customer_remarks_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考3'),
                    'field' => 'customer_remarks_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考4'),
                    'field' => 'customer_remarks_4',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先備考5'),
                    'field' => 'customer_remarks_5',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目備考1'),
                    'field' => 'item_remarks_1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目備考2'),
                    'field' => 'item_remarks_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目備考3'),
                    'field' => 'item_remarks_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目備考4'),
                    'field' => 'item_remarks_4',
                    'hide' => true,
                ),
                array(
                    'label' => _g('品目備考5'),
                    'field' => 'item_remarks_5',
                    'hide' => true,
                ),
                // 以下の項目　ag.cgi?page=ProjectDocView&pPID=1516&pbid=205550
                array(
                    'label' => _g('発送先郵便番号'),
                    'field' => 'delivery_customer_zip',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先住所1'),
                    'field' => 'delivery_customer_address1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先住所2'),
                    'field' => 'delivery_customer_address2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先TEL'),
                    'field' => 'delivery_customer_tel',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先FAX'),
                    'field' => 'delivery_customer_fax',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先メールアドレス'),
                    'field' => 'delivery_customer_e_mail',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先担当者'),
                    'field' => 'delivery_customer_person_in_charge',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考1'),
                    'field' => 'delivery_customer_remarks_1',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考2'),
                    'field' => 'delivery_customer_remarks_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考3'),
                    'field' => 'delivery_customer_remarks_3',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考4'),
                    'field' => 'delivery_customer_remarks_4',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先備考5'),
                    'field' => 'delivery_customer_remarks_5',
                    'hide' => true,
                ),
            );
        }
    }

}
