<?php

class Partner_BuyList_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('発注先'),
                'type' => 'dropdown',
                'field' => 'partner_id',
                'size' => '150',
                'dropdownCategory' => 'partner',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('注文納期'),
                'type' => 'dateFromTo',
                'field' => 'order_detail_dead_line',
                'defaultFrom' => date('Y-m-01'),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('受入日'),
                'type' => 'dateFromTo',
                'field' => 'accepted_date',
                'nosql' => true,
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('検収日'),
                'type' => 'dateFromTo',
                'field' => 'inspection_date',
                'nosql' => true,
                'rowSpan' => 2,
                'hide' => true,
            ),
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'order_detail___item_code',
                'field2' => 'order_detail___item_name',
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
                'label' => _g('部門コード/名'),
                'field' => 'section_code',
                'field2' => 'section_name',
                'hide' => true,
            ),
            array(
                'label' => _g('注文明細備考'),
                'field' => 'order_detail___remarks',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('ゼロ完了分の表示'),
                'type' => 'select',
                'field' => 'show_zero',
                'options' => Gen_Option::getTrueOrFalse('search-show'), // 「しない」時は order_detail_completed = false のレコードに限定
                'nosql' => 'true',
                'default' => 'false',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 'customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
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
        $this->selectQuery = "
            select
                order_header.order_header_id
                ,order_detail.order_detail_id
                ,customer_no as customer_no
                ,customer_name as customer_name
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,case when order_header.classification=1 then order_id_for_user else null end as order_id_for_user
                ,order_detail.order_no as order_no
                ,order_detail.item_code as item_code
                ,order_detail.item_name as item_name
                ,order_detail_quantity as order_detail_quantity
                ,measure
                ,coalesce(order_amount, order_detail_quantity * item_price) as order_amount
                ,t1.accepted_quantity   /* order_detailのaccepted_quantityは日付指定できないので使えない */
                 /* 15iからは、オーダー完了の場合は残数および残額を表示しないようにした ag.cgi?page=ProjectDocView&pid=1574&did=217859 */
                ,case when order_detail_completed then 0 else coalesce(order_detail_quantity,0) - coalesce(t1.accepted_quantity,0) end as remained_qty
                ,accepted_amount
                 /* 発注残額。受入時に単価変更しているケースを考え、「発注金額 - 受入金額」ではなく、「発注残数 * 発注単価」で計算する */
                 /* 15iからは、オーダー完了の場合は残額を表示しないようにした ag.cgi?page=ProjectDocView&pid=1574&did=217859 */
                ,case when order_detail_completed then 0 else gen_round_precision((coalesce(order_detail_quantity,0) - coalesce(t1.accepted_quantity,0)) * item_price, customer_master.rounding, customer_master.precision) end as remained_amount
                ,accepted_tax
                ,accepted_date

                ,currency_name
                ,foreign_currency_rate
                ,coalesce(foreign_currency_order_amount, foreign_currency_item_price * order_detail_quantity) as foreign_currency_order_amount
                ,foreign_currency_accepted_amount
                 /* 外貨の発注残額。上の「発注残額」のコメントも参照 */
                ,case when order_detail_completed then 0 else gen_round_precision((coalesce(order_detail_quantity,0) - coalesce(t1.accepted_quantity,0)) * foreign_currency_item_price, customer_master.rounding, customer_master.precision) end as foreign_currency_remained_amount

                ,item_master.comment as item_remarks_1
                ,item_master.comment_2 as item_remarks_2
                ,item_master.comment_3 as item_remarks_3
                ,item_master.comment_4 as item_remarks_4
                ,item_master.comment_5 as item_remarks_5

                ,section_master.section_code
                ,section_master.section_name
                ,t_item_group_1.item_group_code as item_group_code_1
                ,t_item_group_1.item_group_name as item_group_name_1
                ,t_item_group_2.item_group_code as item_group_code_2
                ,t_item_group_2.item_group_name as item_group_name_2
                ,t_item_group_3.item_group_code as item_group_code_3
                ,t_item_group_3.item_group_name as item_group_name_3
                ,case when order_detail_completed then '" . _g("完了") . "' else '' end as order_detail_completed
                ,order_header.remarks_header as remarks_header
                ,order_detail.remarks
                ,case when order_header.classification = 2 then order_header.remarks_header else order_detail.remarks end as remarks_show
            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                " . (Gen_String::isDateString(@$form['gen_search_accepted_date_from'])
                || Gen_String::isDateString(@$form['gen_search_accepted_date_to'])
                || Gen_String::isDateString(@$form['gen_search_inspection_date_from'])
                || Gen_String::isDateString(@$form['gen_search_inspection_date_to']) ?
                        " inner " : " left ") . "
                join (select order_detail_id, sum(accepted_quantity) as accepted_quantity, sum(accepted_amount) as accepted_amount
                    , max(accepted_date) as accepted_date
                    , sum(accepted_tax) as accepted_tax
                    , sum(foreign_currency_accepted_price) as foreign_currency_accepted_price
                    , sum(foreign_currency_accepted_amount) as foreign_currency_accepted_amount
                    from accepted
                    where 1=1
                    " . (Gen_String::isDateString(@$form['gen_search_accepted_date_from']) ? " and accepted_date >= '{$form['gen_search_accepted_date_from']}'" : "") . "
                    " . (Gen_String::isDateString(@$form['gen_search_accepted_date_to']) ? " and accepted_date <= '{$form['gen_search_accepted_date_to']}'" : "") . "
                    " . (Gen_String::isDateString(@$form['gen_search_inspection_date_from']) ? " and inspection_date >= '{$form['gen_search_inspection_date_from']}'" : "") . "
                    " . (Gen_String::isDateString(@$form['gen_search_inspection_date_to']) ? " and inspection_date <= '{$form['gen_search_inspection_date_to']}'" : "") . "
                    group by order_detail_id) as t1 on order_detail.order_detail_id = t1.order_detail_id
                left join item_master on order_detail.item_id = item_master.item_id
                left join section_master on order_header.section_id = section_master.section_id
                left join item_group_master as t_item_group_1 on item_master.item_group_id = t_item_group_1.item_group_id
                left join item_group_master as t_item_group_2 on item_master.item_group_id_2 = t_item_group_2.item_group_id
                left join item_group_master as t_item_group_3 on item_master.item_group_id_3 = t_item_group_3.item_group_id
                left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id
                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
                and order_header.classification <> 0
                " . (@$form['gen_search_show_zero'] == "false" ? " and (not order_detail_completed or order_detail_completed is null or t1.accepted_quantity <> 0)" : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'customer_no, customer_name, order_id_for_user, order_no';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "order_detail_id"),
            array("section_master", "", "", "order_header_id"),
            array("customer_master", "", "", "order_header_id"),
        );        
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("買掛リスト");
        $form['gen_menuAction'] = "Menu_Partner";
        $form['gen_listAction'] = "Partner_BuyList_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = 'order_id_for_user';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("買掛");

        $form['gen_rowColorCondition'] = array(
            "#fae0a6" => "'[order_detail_completed]'==''",
        );
        $form['gen_colorSample'] = array(
            "fae0a6" => array(_g("イエロー"), _g("受入未完了")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('発注先コード'),
                'field' => 'customer_no',
            ),
            array(
                'label' => _g('発注先名'),
                'field' => 'customer_name',
            ),
        );

        $form['gen_columnArray'] = array(
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
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
                'width' => '70',
                'align' => 'center',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'width' => '80',
                'align' => 'center',
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
                'label' => _g('発注数'),
                'field' => 'order_detail_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('発注金額'),
                'field' => 'order_amount',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('受入数'),
                'field' => 'accepted_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('最終受入日'),
                'field' => 'accepted_date',
                'align' => 'center',
                'width' => '85',
            ),
            array(
                'label' => _g('受入金額'),
                'field' => 'accepted_amount',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('消費税額'),
                'field' => 'accepted_tax',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('残数量'),
                'field' => 'remained_qty',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('発注残額'),
                'field' => 'remained_amount',
                'type' => 'numeric',
                'helpText_noEscape' => _g("発注単価 × 残数量 です。受入単価ではなく発注単価であることにご注意ください。")
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '50',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('発注時レート'),
                'field' => 'foreign_currency_rate',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('発注金額(外貨)'),
                'field' => 'foreign_currency_order_amount',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('受入金額(外貨)'),
                'field' => 'foreign_currency_accepted_amount',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('発注残額(外貨)'),
                'field' => 'foreign_currency_remained_amount',
                'type' => 'numeric',
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
                'field' => 'item_group_code_1',
                'width' => '120',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ名1'),
                'field' => 'item_group_name_1',
                'width' => '120',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループコード2'),
                'field' => 'item_group_code_2',
                'width' => '120',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ名2'),
                'field' => 'item_group_name_2',
                'width' => '120',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループコード3'),
                'field' => 'item_group_code_3',
                'width' => '120',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ名3'),
                'field' => 'item_group_name_3',
                'width' => '120',
                'hide' => true,
            ),
            array(
                'label' => _g('完了'),
                'field' => 'order_detail_completed',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g('注文備考'),  // モードによっては注文明細備考が表示される
                'field' => 'remarks_show',
                'hide' => true,
            ),
        );
    }

}
