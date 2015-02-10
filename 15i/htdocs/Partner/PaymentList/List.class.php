<?php

class Partner_PaymentList_List extends Base_ListBase
{

    var $fromDate;
    var $toDate;
    var $defaultFrom;
    var $defaultTo;
    var $isDetailMode;
    var $yenMode;
    var $timing;

    function setSearchCondition(&$form)
    {
        global $gen_db;
        
        $this1 = date('Y-m-01');
        $this->defaultFrom = date('Y-m-01', strtotime(date('Y-m-d') . ' -1 month'));
        $this->defaultTo = date('Y-m-d', strtotime($this1 . ' -1 day'));

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        // 検索項目を追加/変更したときは、このクラスのJSやReportクラスを変更する必要がある。
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _('日付'),
                'type' => 'dateFromTo',
                'field' => 'close_date',
                'defaultFrom' => $this->defaultFrom,
                'defaultTo' => $this->defaultTo,
                'nosql' => true,
                'rowSpan' => 2,
                'helpText' => _g('対象となる日付範囲を指定します。対象となる日付は受入日か検収日です（自社情報マスタ[仕入計上基準]の設定で決まります）。')
            ),
            array(
                'label' => _g('発注先コード/名'),
                'field' => 'temp_payment___customer_no',
                'field2' => 'temp_payment___customer_name',
            ),
            array(
                'label' => _g('外貨建て購買の扱い'),
                'type' => 'select',
                'field' => 'foreign_currency_mode',
                'options' => array('0' => _g("取引通貨別"), '1' => sprintf(_g("%s換算"), $keyCurrency)),
                'nosql' => true,
            ),
            array(
                'label' => _g('明細の表示'),
                'type' => 'select',
                'field' => 'show_detail',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
                'nosql' => true,
                'default' => 'false',
            ),
            array(
                'label' => _g('買掛がない発注先の表示'),
                'type' => 'select',
                'field' => 'show_customer',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
                'nosql' => true,
                'default' => 'false',
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
        global $gen_db;

        if (Gen_String::isDateString(@$form['gen_search_close_date_from'])) {
            $fromDate = $form['gen_search_close_date_from'];
        } else {
            $fromDate = $this->defaultFrom;
        }
        $this->fromDate = $fromDate;

        if (Gen_String::isDateString(@$form['gen_search_close_date_to'])) {
            $toDate = $form['gen_search_close_date_to'];
        } else {
            // 2038年以降は日付と認識されない
            $toDate = date('2037-12-31');
        }
        $this->toDate = $toDate;
        
        $yenMode = (@$form['gen_search_foreign_currency_mode'] == 1);
        $this->yenMode = $yenMode;

        if (isset($form['gen_reportAction'])) {
            // 帳票印刷時は、Reportならヘッダモード、Report2,3なら明細モード
            $this->isDetailMode = (substr($form['gen_reportAction'],0,27) == "Partner_PaymentList_Report2"
                    || substr($form['gen_reportAction'],0,31) == "Partner_PaymentList_Report3");
            $form['from_date_for_report'] = $this->fromDate;
            $form['to_date_for_report'] = $this->toDate;
        } else {
            $this->isDetailMode = (@$form['gen_search_show_detail'] != "false");
        }

        $showCustomer = (@$form['gen_search_show_customer'] == "true");

        // 支払データの取得（temp_payment）
        Logic_Payment::createTempPaymentTable($this->fromDate, $this->toDate, $yenMode, $showCustomer);
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
             select
                currency_name
                ,temp_payment.customer_id
                ,temp_payment.currency_id
                ,temp_payment.customer_no
                ,temp_payment.customer_name
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,temp_payment.before_accept_amount
                ,temp_payment.accepted_amount
                ,temp_payment.accepted_tax
                ,temp_payment.payment
                ,temp_payment.adjust_payment
                ,temp_payment.payment_total
                ,customer_master.remarks as customer_remarks_1
                ,customer_master.remarks_2 as customer_remarks_2
                ,customer_master.remarks_3 as customer_remarks_3 
                ,customer_master.remarks_4 as customer_remarks_4
                ,customer_master.remarks_5 as customer_remarks_5
                
                /* 帳票では明細モード(Report2)でもこれらが必要 */
                ,payment_0
                ,payment_1
                ,payment_2
                ,payment_3
                ,payment_4
                ,payment_5
                ,payment_6
                ,payment_7
                ,payment_8
                ,payment_9
                ,payment_10

                /* for report class */
                ,'{$this->fromDate}' as from_date
                ,'{$this->toDate}' as to_date
        ";
        if ($this->isDetailMode) {
            // 明細モード

            // 指定日時点の在庫評価単価をテンポラリテーブル（temp_stock_price）に取得
            Logic_Stock::createTempStockPriceTable($this->toDate);
            
            $this->selectQuery .= "
                ,t_detail.order_id_for_user::text
                ,t_detail.order_no::text
                ,t_detail.item_code
                ,t_detail.item_name
                ,t_detail.stock_price
                ,t_detail.quantity
                ,t_detail.measure
                ,t_detail.price
                ,t_detail.amount
                ,t_detail.detail_tax
                ,t_detail.show_date
                ,t_detail.show_date_1
                ,t_detail.show_date_2
                ,t_detail.item_group_code1
                ,t_detail.item_group_name1
                ,t_detail.item_group_code2
                ,t_detail.item_group_name2
                ,t_detail.item_group_code3
                ,t_detail.item_group_name3
                ,t_detail.item_remarks_1
                ,t_detail.item_remarks_2
                ,t_detail.item_remarks_3
                ,t_detail.item_remarks_4
                ,t_detail.item_remarks_5
                
                /* for report class */
                ,t_detail.item_id
                ,t_detail.accepted_date
                ,t_detail.inspection_date
                ,t_detail.order_header_id
                ,t_detail.order_detail_id
                ,t_detail.accepted_id
            from
                temp_payment
                left join (
                    select
                        order_header.partner_id
                        ,order_id_for_user
                        ,accepted.order_no
                        ,item_master.item_id
                        ,order_detail.item_code
                        ,order_detail.item_name
                        ,temp_stock_price.stock_price
                        ,accepted.accepted_quantity as quantity
                        ,item_master.measure
                        " . ($this->yenMode ? "
                            ,accepted.accepted_price as price
                            ,accepted.accepted_amount as amount " : "
                            ,case when order_detail.foreign_currency_id is null then accepted.accepted_price else accepted.foreign_currency_accepted_price end as price
                            ,case when order_detail.foreign_currency_id is null then accepted.accepted_amount else accepted.foreign_currency_accepted_amount end as amount "
                        ) . "
                        /* 税。仕入先元帳のために追加。*/
                        " . ($this->yenMode ? "
                            ,accepted.accepted_tax " : "
                            ,case when order_detail.foreign_currency_id is null then accepted.accepted_tax else 0 end "
                        ) . " as detail_tax
                        ,case when accepted.payment_report_timing = 1 then accepted.inspection_date else accepted.accepted_date end as show_date
                        ,accepted.accepted_date as show_date_1
                        ,accepted.inspection_date as show_date_2
                        ,t_item_group1.item_group_code as item_group_code1
                        ,t_item_group1.item_group_name as item_group_name1
                        ,t_item_group2.item_group_code as item_group_code2
                        ,t_item_group2.item_group_name as item_group_name2
                        ,t_item_group3.item_group_code as item_group_code3
                        ,t_item_group3.item_group_name as item_group_name3

                        ,item_master.comment as item_remarks_1
                        ,item_master.comment_2 as item_remarks_2
                        ,item_master.comment_3 as item_remarks_3
                        ,item_master.comment_4 as item_remarks_4
                        ,item_master.comment_5 as item_remarks_5
                        
                        /* for report class */
                        ,accepted.accepted_date
                        ,accepted.inspection_date
                        ,order_header.order_header_id
                        ,order_detail.order_detail_id
                        ,accepted.accepted_id
                    from
                        accepted
                        inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                        inner join order_header on order_detail.order_header_id = order_header.order_header_id
                        inner join customer_master on order_header.partner_id = customer_master.customer_id
                        inner join item_master on order_detail.item_id = item_master.item_id
                        left join (select item_group_id, item_group_code, item_group_name from item_group_master) as t_item_group1 on item_master.item_group_id = t_item_group1.item_group_id
                        left join (select item_group_id, item_group_code, item_group_name from item_group_master) as t_item_group2 on item_master.item_group_id_2 = t_item_group2.item_group_id
                        left join (select item_group_id, item_group_code, item_group_name from item_group_master) as t_item_group3 on item_master.item_group_id_3 = t_item_group3.item_group_id
                        left join temp_stock_price on item_master.item_id = temp_stock_price.item_id
                    where
                        case when accepted.payment_report_timing = 1
                            then accepted.inspection_date > coalesce(payment_opening_date,'1970-01-01')  -- 残高確定前は含まず
                                and accepted.inspection_date >= '{$this->fromDate}'::date and accepted.inspection_date <= '{$this->toDate}'::date
                            else accepted.accepted_date > coalesce(payment_opening_date,'1970-01-01')    -- 残高確定前は含まず
                                and accepted.accepted_date >= '{$this->fromDate}'::date and accepted.accepted_date <= '{$this->toDate}'::date
                            end
                ) as t_detail on temp_payment.customer_id = t_detail.partner_id
            ";
        } else {
            // ヘッダーモード
            $this->selectQuery .= "
            from
                temp_payment
            ";
        }
        $this->selectQuery .= "
                left join customer_master on temp_payment.customer_id = customer_master.customer_id
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
            [Orderby]
        ";
        $this->orderbyDefault = "temp_payment.customer_no, currency_name" . ($this->isDetailMode ? ", show_date, item_code" : "");
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("customer_master", "", "", "customer_id"),
        );        
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("買掛残高表");
        $form['gen_menuAction'] = "Menu_Partner";
        $form['gen_listAction'] = "Partner_PaymentList_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = 'customer_id';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("買掛残高");

        // Excel出力
        $form['gen_excelShowArray'] = array(array(1, 0, sprintf(_g("%1\$s から %2\$s まで"), $this->fromDate, $this->toDate)));

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("買掛残高一覧表 印刷"),
                'link' => "javascript:reportPrint(1);",
                'reportEdit' => 'Partner_PaymentList_Report'
            ),
            array(
                'label' => _g("買掛残高明細 印刷"),
                'link' => "javascript:reportPrint(2);",
                'reportEdit' => 'Partner_PaymentList_Report2'
            ),
            array(
                'label' => _g("仕入先元帳 印刷"),
                'link' => "javascript:reportPrint(3);",
                'reportEdit' => 'Partner_PaymentList_Report3'
            ),
        );

        if (@$form['gen_nodata']) {     // レポート表示時にデータがなかったとき
            $form['gen_message_noEscape'] = "<span style='background-color:#ffcc99'>" . _g("該当するデータがありませんでした。") . "</span><BR><BR>";
        }

        // 非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷）の場合、
        // Reportクラスではなく、「XXX_XXX_List&gen_report=XXX_XXX_Report」をactionとして指定する。
        // また gen.list.printReport() の第2引数は空欄とする。
        // すると Listクラスで現在の表示条件に合致するデータが取得され、gen_temp_for_reportテーブル
        // に挿入した上で Reportクラスが呼び出される。Reportクラスでは同テーブルからデータを取得するようにする。
        $form['gen_javascript_noEscape'] = "
            function reportPrint(cat) {
                var postUrl = 'Partner_PaymentList_List&gen_reportAction=Partner_PaymentList_Report';
                if (cat == 2) {
                    postUrl += '2';
                } else if (cat == 3) {
                    postUrl += '3';
                }
                gen.list.printReport(postUrl,'');
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('発注先コード'),
                'field' => 'customer_no',
                'width' => '120',
                'align' => 'center',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('発注先名'),
                'field' => 'customer_name',
                'width' => '250',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('取引先グループコード1'),
                'field' => 'customer_group_code_1',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名1'),
                'field' => 'customer_group_name_1',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループコード2'),
                'field' => 'customer_group_code_2',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名2'),
                'field' => 'customer_group_name_2',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループコード3'),
                'field' => 'customer_group_code_3',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名3'),
                'field' => 'customer_group_name_3',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('取引先備考1'),
                'field' => 'customer_remarks_1',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考2'),
                'field' => 'customer_remarks_2',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考3'),
                'field' => 'customer_remarks_3',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考4'),
                'field' => 'customer_remarks_4',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考5'),
                'field' => 'customer_remarks_5',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
            array(
                'label' => _g('繰越額'),
                'field' => 'before_accept_amount',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('期間中仕入額'),
                'field' => 'accepted_amount',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('期間中消費税額'),
                'field' => 'accepted_tax',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('期間中支払額'),
                'field' => 'payment',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('期間中調整額'),
                'field' => 'adjust_payment',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('買掛金残高'),
                'field' => 'payment_total',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
        );
        if ($this->isDetailMode) {
            // 明細モード
            $form['gen_columnArray'][] = array(
                'label' => _g('納品日'),
                'field' => 'show_date_1',
                'type' => 'date',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('検収日'),
                'field' => 'show_date_2',
                'type' => 'date',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'width' => '100',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目名'),
                'field' => 'item_name',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('数量'),
                'field' => 'quantity',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('受入単価'),
                'field' => 'price',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('金額'),
                'field' => 'amount',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('在庫評価単価'),
                'field' => 'stock_price',
                'type' => 'numeric',
                'helpText_noEscape' => _g("表示期間の最終日時点の在庫評価単価を表示します。詳細は在庫リスト画面の「評価単価」のチップヘルプを参照してください。"), 
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目グループコード1'),
                'field' => 'item_group_code1',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目グループ名1'),
                'field' => 'item_group_name1',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目グループコード2'),
                'field' => 'item_group_code2',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目グループ名2'),
                'field' => 'item_group_name2',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目グループコード3'),
                'field' => 'item_group_code3',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目グループ名3'),
                'field' => 'item_group_name3',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目備考1'),
                'field' => 'item_remarks_1',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目備考2'),
                'field' => 'item_remarks_2',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目備考3'),
                'field' => 'item_remarks_3',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目備考4'),
                'field' => 'item_remarks_4',
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('品目備考5'),
                'field' => 'item_remarks_5',
                'hide' => true,
            );
        } else {
            // ヘッダーモード
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(現金)'),
                'field' => 'payment_1',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(振込)'),
                'field' => 'payment_2',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(小切手)'),
                'field' => 'payment_3',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(手形)'),
                'field' => 'payment_4',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(相殺)'),
                'field' => 'payment_5',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(値引)'),
                'field' => 'payment_6',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(振込手数料)'),
                'field' => 'payment_7',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(先振込)'),
                'field' => 'payment_9',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(代引)'),
                'field' => 'payment_10',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中支払額(その他)'),
                'field' => 'payment_8',
                'width' => '120',
                'type' => 'numeric',
            );
        }
    }

}
