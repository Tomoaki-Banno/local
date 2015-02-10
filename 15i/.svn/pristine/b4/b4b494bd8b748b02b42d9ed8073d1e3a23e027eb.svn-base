<?php

define('CLOSE_DATE_COLUMN_COUNT', 18);  // 締日列の最大数

class Delivery_ReceivableCalendar_List extends Base_ListBase
{

    var $fromDate;
    var $toDate;
    var $dateSpan;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));
        
        // 検索項目を追加/変更したときは、このクラスのJSやReportクラスを変更する必要がある。
        $form['gen_searchControlArray'] = array(
            array(
                'label' => sprintf(_g('表示期間（最大%s列）'), CLOSE_DATE_COLUMN_COUNT),
                'type' => 'dateFromTo',
                'field' => 'close_date',
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => date('Y-m-t', strtotime(date('Y-m-01') . ' +6 month -1 day')),
                'nosql' => true,
                'rowSpan' => 2
            ),
            array(
                'label' => _g('得意先コード/名'),
                'field' => 'temp_collect___customer_no',
                'field2' => 'temp_collect___customer_name',
                'helpText_noEscape' => _g("この条件は帳票発行の際には反映されません。画面表示に対してのみ有効です。"),
            ),
            array(
                'label' => _g('日付間隔'),
                'type' => 'select',
                'field' => 'date_span',
                'options' => array(5 => _g("5日"), 10 => _g("10日"), 30 => _g("1ヶ月")),
                'nosql' => true,
            ),
            array(
                'label' => _g('外貨建て請求の扱い'),
                'type' => 'select',
                'field' => 'foreign_currency_mode',
                'options' => array('0' => _g("取引通貨別"), '1' => sprintf(_g("%s換算"), $keyCurrency)),
                'nosql' => true,
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
        if (Gen_String::isDateString(@$form['gen_search_close_date_from'])) {
            $fromDate = $form['gen_search_close_date_from'];
        } else {
            $fromDate = date('Y-m-01');
        }
        $this->fromDate = $fromDate;

        if (Gen_String::isDateString(@$form['gen_search_close_date_to'])) {
            $toDate = $form['gen_search_close_date_to'];
        } else {
            $toDate = Gen_String::getThisMonthLastDateString();
        }
        $this->toDate = $toDate;

        $dateSpan = @$form['gen_search_date_span'];
        $this->dateSpan = $dateSpan;

        $yenMode = (@$form['gen_search_foreign_currency_mode'] == 1);

        // 回収予定データの取得（temp_collect）
        Logic_Receivable::createTempCollectTable($fromDate, $toDate, $dateSpan, $yenMode, CLOSE_DATE_COLUMN_COUNT);
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                temp_collect.customer_no
                ,temp_collect.customer_name
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,cast(currency_name as text) as currency_name
                ,temp_collect.bill_pattern
                ,temp_collect.monthly_limit_date
                ,temp_collect.collect_cycle
                ,temp_collect.total_sales_with_tax
                ,temp_collect.total_sales
                ,temp_collect.total_tax
                ";
                for ($i = 1; $i <= CLOSE_DATE_COLUMN_COUNT; $i++) {
                    $this->selectQuery .= "
                        ,temp_collect.collect_date_{$i}
                        ,temp_collect.sales_with_tax_{$i}
                        ,temp_collect.sales_{$i}
                        ,temp_collect.tax_{$i}
                    ";
                }
                $this->selectQuery .= "
                /* for report class */
                ,customer_master.customer_id
                ,'{$this->fromDate}' as from_date
                ,'{$this->toDate}' as to_date
                    
            from
                temp_collect
                left join customer_master on temp_collect.customer_id = customer_master.customer_id
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
            [Orderby]
        ";
        $this->orderbyDefault = 'temp_collect.customer_no, currency_name';
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
        $form['gen_pageTitle'] = _g("回収予定表");
        $form['gen_menuAction'] = "Menu_Delivery";
        $form['gen_listAction'] = "Delivery_ReceivableCalendar_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = 'customer_no';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("回収予定表");

        // Excel出力
        $form['gen_excelShowArray'] = array(array(1, 0, sprintf(_g("%1\$s から %2\$s まで"), $this->fromDate, $this->toDate)));

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("回収予定表 印刷"),
                'link' => "javascript:reportPrint();",
                'reportEdit' => 'Delivery_ReceivableCalendar_Report'
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
            function reportPrint() {
                var postUrl = 'Delivery_ReceivableCalendar_List&gen_reportAction=Delivery_ReceivableCalendar_Report';
                gen.list.printReport(postUrl,'');
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
                'width' => '120',
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'width' => '200',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('請求パターン'),
                'field' => 'bill_pattern',
                'width' => '90',
            ),
            array(
                'label' => _g('締日グループ'),
                'field' => 'monthly_limit_date',
                'width' => '90',
            ),
            array(
                'label' => _g('回収サイクル'),
                'field' => 'collect_cycle',
                'width' => '90',
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
                'label' => _g('合計税込売上'),
                'field' => 'total_sales_with_tax',
                'width' => '90',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('合計税別売上'),
                'field' => 'total_sales',
                'width' => '90',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('合計税額'),
                'field' => 'total_tax',
                'width' => '80',
                'type' => 'numeric',
                'hide' => true,
            ),
        );
        $arr = Logic_Receivable::getCollectCloseData($this->fromDate, $this->toDate, $this->dateSpan, CLOSE_DATE_COLUMN_COUNT);
        $close = $arr[1];
        for ($i = 0; $i < CLOSE_DATE_COLUMN_COUNT && $close[$i] <= $this->toDate; $i++) {
            $form['gen_columnArray'][] = array(
                'label' => $close[$i],
                'field' => 'sales_with_tax_' . ($i + 1),
                'width' => '80',
                'type' => 'numeric',
                'denyMove' => true, // 日付列は列順序固定。日付範囲を変更したときの表示乱れを防ぐため
            );
        }
    }

}
