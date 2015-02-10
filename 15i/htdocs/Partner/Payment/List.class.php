<?php

class Partner_Payment_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('発注先名'),
                'type' => 'dropdown',
                'field' => 'payment___customer_id',
                'size' => '150',
                'dropdownCategory' => 'partner',
            ),
            array(
                'label' => _g('支払日'),
                'type' => 'dateFromTo',
                'field' => 'payment_date',
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
            ),
            array(
                'label' => _g('支払種別'),
                'type' => 'select',
                'field' => 'way_of_payment',
                'options' => Gen_Option::getWayOfPayment('search'),
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 'customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
            ),
        );
    
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("種別支払額（今月・日次）") => self::_getPreset("5", "way_of_payment_show", "payment_date_day"),
                _g("種別支払額（今年・日次）") => self::_getPreset("7", "way_of_payment_show", "payment_date_day"),
                _g("種別支払額（今年・月次）") => self::_getPreset("7", "way_of_payment_show", "payment_date_month"),
                _g("取引別支払額（今月・日次）") => self::_getPreset("5", "payment_date_day", "customer_name"),
                _g("取引支払額（今年・日次）") => self::_getPreset("7", "payment_date_day", "customer_name"),
                _g("取引支払額（今年・月次）") => self::_getPreset("7", "payment_date_month", "customer_name"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby = "", $value = "amount", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "payment_date", "dp" => $datePattern),
                    
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
    }

    function setQueryParam(&$form)
    {
        $classQuery = Gen_Option::getWayOfPayment('list-query');

        $this->selectQuery = "
            select
                payment_id
                ,payment_date
                ,customer_no
                ,customer_name
                ,amount
                ,adjust_amount
                ,coalesce(amount,0) + coalesce(adjust_amount,0) as after_amount
                ,payment.remarks
                ,way_of_payment
                ,case way_of_payment {$classQuery} end as way_of_payment_show
                ,currency_name
                ,case when currency_name is not null then foreign_currency_rate end as foreign_currency_rate
                ,foreign_currency_amount
                ,foreign_currency_adjust_amount
                ,coalesce(foreign_currency_amount,0) + coalesce(foreign_currency_adjust_amount,0) as foreign_currency_after_amount
                ,case when foreign_currency_id is null then amount else foreign_currency_amount end as amount_for_csv
                ,case when foreign_currency_id is null then adjust_amount else foreign_currency_adjust_amount end as adjust_amount_for_csv
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3

                ,coalesce(payment.record_update_date, payment.record_create_date) as gen_record_update_date
                ,coalesce(payment.record_updater, payment.record_creator) as gen_record_updater

            from
                payment
                inner join customer_master on payment.customer_id = customer_master.customer_id
                left join currency_master on payment.foreign_currency_id = currency_master.currency_id
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = "payment_date desc, customer_no";
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("customer_master", "", "", "payment_id"),
        );        
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("支払");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）");
        $form['gen_allowUpdateCheck'] = false;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('発注先コード'),
                'field' => 'customer_no',
            ),
            array(
                'label' => _g('日付'),
                'field' => 'payment_date',
            ),
            array(
                'label' => _g('支払種別'),
                'addLabel' => _g('(1:現金、2:振込、3:小切手、4:手形、5:相殺、6:値引、7:振込手数料、8:その他、9:先振込、10:代引)'),
                'field' => 'way_of_payment',
            ),
            array(
                'label' => _g('支払金額'),
                'field' => 'amount',
                'exportField' => 'amount_for_csv',
            ),
            array(
                'label' => _g('調整金額'),
                'field' => 'adjust_amount',
                'exportField' => 'adjust_amount_for_csv',
            ),
            array(
                'label' => _g('レート'),
                'field' => 'foreign_currency_rate',
            ),
            array(
                'label' => _g('支払備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("支払登録");
        $form['gen_menuAction'] = "Menu_Partner";
        $form['gen_listAction'] = "Partner_Payment_List";
        $form['gen_editAction'] = "Partner_Payment_Edit";
        $form['gen_deleteAction'] = "Partner_Payment_Delete";
        $form['gen_idField'] = 'payment_id';
        $form['gen_idFieldForUpdateFile'] = "payment.payment_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("支払登録");

        $form['gen_isClickableTable'] = "true";

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'width' => 42,
                'deleteAction' => 'Partner_Payment_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('支払日付'),
                'field' => 'payment_date',
                'type' => 'date',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('発注先コード'),
                'field' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('発注先名'),
                'field' => 'customer_name',
                'width' => '200',
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
                'label' => sprintf(_g('金額(%s)'), $keyCurrency),
                'field' => 'amount',
                'type' => 'numeric',
                'width' => '100',
            ),
            array(
                'label' => sprintf(_g('調整金額(%s)'), $keyCurrency),
                'field' => 'adjust_amount',
                'type' => 'numeric',
                'width' => '100',
            ),
            array(
                'label' => sprintf(_g('調整後金額(%s)'), $keyCurrency),
                'field' => 'after_amount',
                'type' => 'numeric',
                'width' => '100',
            ),
            array(
                'label' => _g('支払種別'),
                'field' => 'way_of_payment_show',
                'width' => '80',
                'align' => 'center',
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
                'label' => _g('金額(外貨)'),
                'field' => 'foreign_currency_amount',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('調整金額(外貨)'),
                'field' => 'foreign_currency_adjust_amount',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('調整後金額(外貨)'),
                'field' => 'foreign_currency_after_amount',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('支払備考'),
                'field' => 'remarks',
                'width' => '200',
                'hide' => true,
            ),
        );
    }

}
