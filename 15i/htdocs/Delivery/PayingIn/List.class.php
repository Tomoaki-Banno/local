<?php

class Delivery_PayingIn_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('得意先'),
                'type' => 'dropdown',
                'field' => 'paying_in___customer_id',
                'size' => '150',
                'dropdownCategory' => 'customer',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('入金日'),
                'type' => 'dateFromTo',
                'field' => 'paying_in_date',
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('入金種別'),
                'type' => 'select',
                'field' => 'way_of_payment',
                'options' => Gen_Option::getWayOfPayment('search'),
            ),
            array(
                'label' => _g('請求書番号'),
                'field' => 'bill_number',
            ),
            array(
                'label' => _g('請求パターン'),
                'type' => 'select',
                'field' => 'bill_pattern',
                'options' => Gen_Option::getBillPattern('search'),
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
                _g("種別入金額（今月・日次）") => self::_getPreset("5", "way_of_payment_show", "paying_in_date_day"),
                _g("種別入金額（今年・日次）") => self::_getPreset("7", "way_of_payment_show", "paying_in_date_day"),
                _g("種別入金額（今年・月次）") => self::_getPreset("7", "way_of_payment_show", "paying_in_date_month"),
                _g("取引別支払額（今月・日次）") => self::_getPreset("5", "paying_in_date_day", "customer_name"),
                _g("取引支払額（今年・日次）") => self::_getPreset("7", "paying_in_date_day", "customer_name"),
                _g("取引支払額（今年・月次）") => self::_getPreset("7", "paying_in_date_month", "customer_name"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby = "", $value = "amount", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "paying_in_date", "dp" => $datePattern),
                    
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
        $classQuery1 = Gen_Option::getWayOfPayment('list-query');
        $classQuery2 = Gen_Option::getBillPattern('list-query');
        
        $this->selectQuery = "
            select
                paying_in_id
                ,paying_in_date
                ,customer_no
                ,customer_name
                ,amount
                ,paying_in.remarks
                ,way_of_payment
                ,case way_of_payment {$classQuery1} end as way_of_payment_show
                ,currency_name
                ,case when currency_name is not null then foreign_currency_rate end as foreign_currency_rate
                ,foreign_currency_amount
                ,case when foreign_currency_id is null then amount else foreign_currency_amount end as amount_for_csv
                ,bill_number
                ,bill_pattern
                ,case bill_pattern {$classQuery2} end as bill_pattern_show
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3

                ,coalesce(paying_in.record_update_date, paying_in.record_create_date) as gen_record_update_date
                ,coalesce(paying_in.record_updater, paying_in.record_creator) as gen_record_updater
            from
                paying_in
                inner join customer_master on paying_in.customer_id = customer_master.customer_id
                left join currency_master on paying_in.foreign_currency_id = currency_master.currency_id
                left join (select bill_header_id as bhid, bill_number from bill_header) as t_bill on paying_in.bill_header_id = t_bill.bhid
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = "paying_in_date desc, customer_no";
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("customer_master", "", "Master_Customer", "paying_in_id"),
        );        
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("入金");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）");
        $form['gen_allowUpdateCheck'] = false;

        // 通知メール（成功時のみ）
        $form['gen_csvAlertMail_id'] = 'delivery_payingin_new';   // Master_AlertMail_Edit冒頭を参照
        $form['gen_csvAlertMail_title'] = _g("入金登録");
        $form['gen_csvAlertMail_body'] = _g("入金データがCSVインポートされました。");    // インポート件数等が自動的に付加される

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
            ),
            array(
                'label' => _g('日付'),
                'field' => 'paying_in_date',
            ),
            array(
                'label' => _g('入金種別'),
                'addLabel' => _g('(1:現金、2:振込、3:小切手、4:手形、5:相殺、6:値引、7:振込手数料、8:その他、9:先振込、10:代引)'),
                'field' => 'way_of_payment',
            ),
            array(
                'label' => _g('入金金額'),
                'field' => 'amount',
                'exportField' => 'amount_for_csv'
            ),
            array(
                'label' => _g('レート'),
                'field' => 'foreign_currency_rate',
            ),
            array(
                'label' => _g('入金備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("入金登録");
        $form['gen_menuAction'] = "Menu_Delivery";
        $form['gen_listAction'] = "Delivery_PayingIn_List";
        $form['gen_editAction'] = "Delivery_PayingIn_Edit";
        $form['gen_deleteAction'] = "Delivery_PayingIn_Delete";
        $form['gen_idField'] = 'paying_in_id';
        $form['gen_idFieldForUpdateFile'] = "paying_in.paying_in_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("入金");

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("入金一覧 印刷"),
                'link' => "javascript:gen.list.printReport('Delivery_PayingIn_Report','print_check')",
                'reportEdit' => 'Delivery_PayingIn_Report'
            ),
        );

        // 請求書リストからの入金登録
        if (isset($form['isBill']) && $form['isBill'] && isset($form['customerId']) && is_numeric($form['customerId']) && isset($form['billHeaderId']) && is_numeric($form['billHeaderId'])) {
            $form['gen_javascript_noEscape'] = "
                var url = 'index.php?action=Delivery_PayingIn_Edit&isBill=true';
                url += '&customer_id='+" . h($form['customerId']) . ";
                url += '&billHeaderId='+" . h($form['billHeaderId']) . ";
                gen.modal.open(url);
            ";
        }

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
                'deleteAction' => 'Delivery_PayingIn_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g("印刷"),
                'name' => 'print_check',
                'type' => 'checkbox',
            ),
            array(
                'label' => _g('入金日付'),
                'field' => 'paying_in_date',
                'type' => 'date',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
                'width' => '120',
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'width' => '250',
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
                'label' => _g('入金種別'),
                'field' => 'way_of_payment_show',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('請求パターン'),
                'field' => 'bill_pattern_show',
                'width' => '130',
                'align' => 'center',
            ),
            array(
                'label' => _g('請求書番号'),
                'field' => 'bill_number',
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
                'label' => _g('入金備考'),
                'field' => 'remarks',
                'width' => '200',
                'hide' => true,
            ),
        );
    }

}
