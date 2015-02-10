<?php

class Delivery_ReceivableList_List extends Base_ListBase
{

    var $fromDate;
    var $toDate;
    var $defaultFrom;
    var $defaultTo;
    var $isDetailMode;
    var $dateMode;
    var $yenMode;

    function validate($validator, &$form)
    {
        // 未セットの場合はsetSearchConditionDefaultでデフォルト値が設定されるため、
        // エラーを出さなくてもよい。
        if (isset($form['gen_search_receivable_Year']))
            $validator->range('gen_search_receivable_Year', _g('年が正しくありません。'), 2006, date("Y") + 1);
        if (isset($form['gen_search_receivable_Month']))
            $validator->range('gen_search_receivable_Month', _g('月が正しくありません。'), 1, 12);
        if ($validator->hasError()) {
            $this->setViewParam($form);    // エラー時のために表示paramを取得しておく
        }

        return "list.tpl";        // if Error
    }

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $lastDate = date('Y-m-01', strtotime(date('Y-m-01') . ' -1 month'));

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        // 検索項目を追加/変更したときは、このクラスのJSやReportクラスを変更する必要がある。
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('年月'),
                'type' => 'yearMonth',
                'field' => 'receivable', // この名前の後に「_Year」と「_Month」
                'start_year' => date('Y') - 5,
                'end_year' => date('Y') + 2,
                'defaultYear' => date('Y', strtotime($lastDate)),
                'defaultMonth' => date('m', strtotime($lastDate)),
                'nosql' => true,
            ),
            array(
                'label' => _g('得意先コード/名'),
                'field' => 'temp_receivable___customer_no',
                'field2' => 'temp_receivable___customer_name',
                //'helpText_noEscape' => _g("この条件は帳票発行の際には反映されません。画面表示に対してのみ有効です。"),
            ),
            array(
                'label' => _g('金額モード'),
                'type' => 'select',
                'field' => 'data_mode',
                'options' => array('2' => _g("請求ベース"), '0' => _g("納品ベース"), '1' => _g("受注ベース")),
                'nosql' => true,
                'helpText_noEscape' => _g("売掛管理表のモードを指定します。") . '<br><br>'
                . _g("●請求ベース") . "：<br>" . _g("請求書を発行した時点で売上が計上されます（未請求の納品は計上されません）。税額は正確に計算されます。請求締日が基準となります。") . '<br><br>'
                . _g("●納品ベース") . "：<br>" . _g("納品登録した時点で売上が計上されます（未請求の納品も計上されます）。ただし、税額は仮計算となります。納品日もしくは検収日が基準となります。") . '<br><br>'
                . _g("●受注ベース") . "：<br>" . _g("受注登録した時点で売上が計上されます（未納品・未請求の受注も計上されます）。ただし、税額は仮計算となります。受注納期が基準となります。") . '<br><br>'
                . _g("「繰越額」については、期間前の直近の請求書の金額をもとに、それ以後の納品金額を加算して計算されます。")
            ),
            array(
                'label' => _g('外貨建て請求の扱い'),
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
                'label' => _g('売掛がない得意先の表示'),
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
        $year = date('Y');
        if (isset($form['gen_search_receivable_Year']) && is_numeric($form['gen_search_receivable_Year'])) {
            $year = $form['gen_search_receivable_Year'];
        }
        $month = date('m', strtotime(date('Y-m-01') . ' -1 month'));
        if (isset($form['gen_search_receivable_Month']) && is_numeric($form['gen_search_receivable_Month'])) {
            $month = $form['gen_search_receivable_Month'];
        }
        $this->fromDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
        $this->toDate = date("Y-m-d", mktime(0, 0, 0, $month + 1, 0, $year));

        if (isset($form['gen_search_data_mode'])) {
            $dataMode = @$form['gen_search_data_mode'];
        } else {
            $dataMode = 2;
        }
        $this->dateMode = $dataMode;

        $yenMode = (@$form['gen_search_foreign_currency_mode'] == 1);
        $this->yenMode = $yenMode;

        if (isset($form['gen_reportAction'])) {
            // 帳票印刷時は、Reportならヘッダモード、Report2,3なら明細モード
            $this->isDetailMode = (substr($form['gen_reportAction'],0,31) == "Delivery_ReceivableList_Report2" 
                    || substr($form['gen_reportAction'],0,31) == "Delivery_ReceivableList_Report3");
            $form['from_date_for_report'] = $this->fromDate;
            $form['to_date_for_report'] = $this->toDate;
        } else {
            $this->isDetailMode = (@$form['gen_search_show_detail'] != "false");
        }

        $showCustomer = (@$form['gen_search_show_customer'] == "true");

        // 売掛残高データの取得（temp_receivable）
        Logic_Receivable::createTempReceivableTable($this->fromDate, $this->toDate, $dataMode, $yenMode, null, $showCustomer );
    }

    function setQueryParam(&$form)
    {
        global $gen_db;

        $classQuery1 = Gen_Option::getBillPattern('list-query');
        
        $this->selectQuery = "
            select
                temp_receivable.currency_name
                ,temp_receivable.currency_id
                ,temp_receivable.customer_no
                ,temp_receivable.customer_name
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,temp_receivable.before_sales
                ,temp_receivable.sales
                ,temp_receivable.sales_tax
                ,temp_receivable.paying_in
                ,temp_receivable.receivable_balance
                ,temp_receivable.credit_line
                ,case when temp_receivable.credit_line is not null and coalesce(temp_receivable.credit_line,0) < coalesce(receivable_balance,0) then 1 else 0 end as credit_over
                ,case temp_receivable.bill_pattern {$classQuery1} end as bill_pattern_show
                ,case temp_receivable.monthly_limit_date when 31 then '" . _g("末") . "' else cast(temp_receivable.monthly_limit_date as text) end as monthly_limit_date_show
                ,customer_master.remarks as customer_remarks_1
                ,customer_master.remarks_2 as customer_remarks_2
                ,customer_master.remarks_3 as customer_remarks_3 
                ,customer_master.remarks_4 as customer_remarks_4
                ,customer_master.remarks_5 as customer_remarks_5
                
                /* 帳票では明細モード(Report2)でもこれらが必要 */
                ,temp_receivable.paying_in_0
                ,temp_receivable.paying_in_1
                ,temp_receivable.paying_in_2
                ,temp_receivable.paying_in_3
                ,temp_receivable.paying_in_4
                ,temp_receivable.paying_in_5
                ,temp_receivable.paying_in_6
                ,temp_receivable.paying_in_7
                ,temp_receivable.paying_in_8
                ,temp_receivable.paying_in_9
                ,temp_receivable.paying_in_10
                
                /* for report class */
                ,customer_master.customer_id
                ,'{$this->fromDate}' as from_date
                ,'{$this->toDate}' as to_date
                ,'" . ($this->dateMode == '1' ? _g('受注ベース') : ($this->dateMode == '2' ? _g('請求ベース') : _g('納品ベース'))) . "' as mode
        ";
        if ($this->isDetailMode) {
            // 明細モード

            // 月末時点の在庫評価単価をテンポラリテーブル（temp_stock_price）に取得
            Logic_Stock::createTempStockPriceTable($this->toDate);
            
            $this->selectQuery .= "
                ,t_detail.delivery_no::text
                ,t_detail.line_no
                ,t_detail.item_id
                ,t_detail.item_code
                ,t_detail.item_name
                ,t_detail.stock_price
                ,t_detail.quantity
                ,t_detail.measure
                ,t_detail.price
                ,t_detail.amount
                ,t_detail.detail_tax
                ,t_detail.show_date_1
                ,t_detail.show_date_2
                ,t_detail.item_remarks_1
                ,t_detail.item_remarks_2
                ,t_detail.item_remarks_3
                ,t_detail.item_remarks_4
                ,t_detail.item_remarks_5
                ,t_detail.received_number
                
                /* for report class */
                ,t_detail.received_header_id
                ,t_detail.received_detail_id
                ,t_detail.delivery_header_id
                ,t_detail.delivery_detail_id
            ";
            if ($this->dateMode == 0) {
                // 納品ベース
                $this->selectQuery .= "
                from
                    temp_receivable
                    left join (
                        select
                            delivery_header.bill_customer_id as customer_id
                            ,delivery_header.delivery_no
                            ,delivery_detail.line_no
                            ,item_master.item_id
                            ,item_master.item_code
                            ,item_master.item_name
                            ,temp_stock_price.stock_price
                            ,delivery_detail.delivery_quantity as quantity
                            ,item_master.measure
                            " . ($this->yenMode ? "
                                ,delivery_detail.delivery_price as price
                                ,delivery_detail.delivery_amount as amount " : "
                                ,case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_price else delivery_detail.foreign_currency_delivery_price end as price
                                ,case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_amount else delivery_detail.foreign_currency_delivery_amount end as amount "
                            ) . "
                            /* 税（税計算単位が納品明細単位の場合のみ）。得意先元帳のために追加。*/
                            " . ($this->yenMode ? "
                                ,delivery_detail.delivery_tax " : "
                                ,case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_tax else delivery_detail.foreign_currency_delivery_tax end "
                            ) . " as detail_tax
                            ,delivery_header.delivery_date as show_date_1
                            ,delivery_header.inspection_date as show_date_2
                            ,item_master.comment as item_remarks_1
                            ,item_master.comment_2 as item_remarks_2
                            ,item_master.comment_3 as item_remarks_3
                            ,item_master.comment_4 as item_remarks_4
                            ,item_master.comment_5 as item_remarks_5
                            ,received_header.received_number
                            
                            /* for report class */
                            ,received_header.received_header_id
                            ,received_detail.received_detail_id
                            ,delivery_header.delivery_header_id
                            ,delivery_detail.delivery_detail_id
                        from
                            delivery_detail
                            inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                            inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                            inner join received_header on received_detail.received_header_id = received_header.received_header_id
                            inner join item_master on received_detail.item_id = item_master.item_id
                            left join temp_stock_price on item_master.item_id = temp_stock_price.item_id
                        where
                            case when delivery_header.receivable_report_timing = 1
                                then delivery_header.inspection_date >= '{$this->fromDate}'::date and delivery_header.inspection_date <= '{$this->toDate}'::date
                                else delivery_header.delivery_date >= '{$this->fromDate}'::date and delivery_header.delivery_date <= '{$this->toDate}'::date end
                        ) as t_detail on temp_receivable.customer_id = t_detail.customer_id
                ";
            } elseif ($this->dateMode == 1) {
                // 受注ベース
                $receivedPrice = (!$this->yenMode ? "case when received_detail.foreign_currency_id is null then received_detail.product_price else received_detail.foreign_currency_product_price end" : "received_detail.product_price");
                $this->selectQuery .= "
                from
                    temp_receivable
                    left join (
                        select
                            received_header.customer_id
                            ,'' as delivery_no
                            ,received_detail.line_no
                            ,item_master.item_id
                            ,item_master.item_code
                            ,item_master.item_name
                            ,temp_stock_price.stock_price
                            ,received_detail.received_quantity as quantity
                            ,item_master.measure
                            " . ($this->yenMode ? "
                                ,received_detail.product_price as price
                                ,gen_round_precision(received_detail.product_price * received_detail.received_quantity, t_bill_customer.rounding, t_bill_customer.precision) as amount " : "
                                ,case when received_detail.foreign_currency_id is null then received_detail.product_price else received_detail.foreign_currency_product_price end as price
                                ,gen_round_precision(case when received_detail.foreign_currency_id is null then received_detail.product_price else received_detail.foreign_currency_product_price end * received_detail.received_quantity, t_bill_customer.rounding, t_bill_customer.precision) as amount "
                            ) . "
                            /* 税（税計算単位が納品明細単位の場合のみ）。得意先元帳のために追加。*/
                            ,case when received_detail.foreign_currency_id is null then gen_round_precision({$receivedPrice} * received_detail.received_quantity * coalesce(coalesce(item_master.tax_rate,tax_rate_master.tax_rate),0) / 100, t_bill_customer.rounding, t_bill_customer.precision) end as detail_tax
                            ,received_detail.dead_line as show_date_1
                            ,null::date as show_date_2
                            ,item_master.comment as item_remarks_1
                            ,item_master.comment_2 as item_remarks_2
                            ,item_master.comment_3 as item_remarks_3
                            ,item_master.comment_4 as item_remarks_4
                            ,item_master.comment_5 as item_remarks_5
                            ,received_header.received_number
                            
                            /* for report class */
                            ,received_header.received_header_id
                            ,received_detail.received_detail_id
                            ,delivery_header.delivery_header_id /* 受注ベースの原則に反するが、納品IDを取得している。詳細は delivery_detail の join のところのコメントを参照 */
                            ,delivery_detail.delivery_detail_id
                        from
                            received_detail
                            inner join received_header on received_detail.received_header_id = received_header.received_header_id
                            inner join item_master on received_detail.item_id = item_master.item_id
                            inner join customer_master on received_header.customer_id = customer_master.customer_id
                            left join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
                            /* 消費税率の取得 */
                            left join (select received_header_id, max(apply_date) as max_apply_date from received_header inner join tax_rate_master on received_header.received_date >= tax_rate_master.apply_date group by received_header_id) as t_tax_max_date
                                on received_detail.received_header_id = t_tax_max_date.received_header_id
                            left join tax_rate_master on t_tax_max_date.max_apply_date = tax_rate_master.apply_date
                            left join temp_stock_price on item_master.item_id = temp_stock_price.item_id
                            /* 受注ベースでは納品関連のデータは表示しないのが原則（納品データが無い場合や、分納の場合があるため）だが、*/
                            /* 帳票（売掛残高明細、元帳）で納品関連のデータを表示したいというニーズがあるため、ここで納品IDを取得している。*/
                            /* 受注ベースなので、納品IDが無い場合もある。また分納の場合、複数の納品IDのうち任意の一つが取得される。 */
                            left join (select received_detail_id, max(delivery_detail_id) as delivery_detail_id from delivery_detail group by received_detail_id) as t_delivery_detail on received_detail.received_detail_id = t_delivery_detail.received_detail_id
                            left join delivery_detail on t_delivery_detail.delivery_detail_id = delivery_detail.delivery_detail_id
                            left join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                        where
                            received_detail.dead_line >= '{$this->fromDate}'::date
                            and received_detail.dead_line <= '{$this->toDate}'::date
                    ) as t_detail on temp_receivable.customer_id = t_detail.customer_id
                ";
            } else {
                // 請求ベース
                $this->selectQuery .= "
                from
                    temp_receivable
                    left join (
                        select
                            bill_header.customer_id
                            ,bill_detail.delivery_no
                            ,bill_detail.line_no
                            ,item_master.item_id
                            ,bill_detail.item_code
                            ,bill_detail.item_name
                            ,temp_stock_price.stock_price
                            ,bill_detail.quantity
                            ,item_master.measure
                            " . ($this->yenMode ? "
                                ,bill_detail.price
                                ,bill_detail.amount " : "
                                ,case when bill_header.foreign_currency_id is null then bill_detail.price else bill_detail.foreign_currency_price end as price
                                ,case when bill_header.foreign_currency_id is null then bill_detail.amount else bill_detail.foreign_currency_amount end as amount "
                            ) . "
                            /* 税（税計算単位が納品明細単位の場合のみ）。得意先元帳のために追加。*/
                            " . ($this->yenMode ? "
                                ,bill_detail.tax " : "
                                ,case when bill_detail.foreign_currency_tax is null then bill_detail.tax end "
                            ) . " as detail_tax
                            ,bill_detail.delivery_date as show_date_1
                            ,bill_detail.inspection_date as show_date_2
                            ,item_master.comment as item_remarks_1
                            ,item_master.comment_2 as item_remarks_2
                            ,item_master.comment_3 as item_remarks_3
                            ,item_master.comment_4 as item_remarks_4
                            ,item_master.comment_5 as item_remarks_5
                            ,bill_detail.received_number
                            
                            /* for report class */
                            /* 請求ベースの原則に反するが、受注や納品のIDを取得している。詳細は delivery_detail の join のところのコメントを参照 */
                            ,received_header.received_header_id
                            ,received_detail.received_detail_id
                            ,delivery_header.delivery_header_id
                            ,delivery_detail.delivery_detail_id
                        from
                            bill_detail
                            inner join bill_header on bill_detail.bill_header_id = bill_header.bill_header_id
                            left join customer_master on bill_header.customer_id = customer_master.customer_id
                            left join item_master on bill_detail.item_code = item_master.item_code
                            left join temp_stock_price on item_master.item_id = temp_stock_price.item_id
                            /* 請求ベースでは、請求書発行時に取得した情報（bill_header, bill_detail）だけを使用するのが原則だが、*/
                            /* 帳票（売掛残高明細、元帳）で受注や納品関連のデータを表示したいというニーズがあるため、ここで受注や納品のIDを取得している。*/
                            /* ag.cgi?page=ProjectDocView&pid=1574&did=211028  ag.cgi?page=ProjectDocView&pid=1574&did=214332 */
                            /* 請求書を発行したら納品・受注のデータはロックされるため、問題はないだろうと判断した。 */
                            left join delivery_detail on bill_detail.delivery_detail_id = delivery_detail.delivery_detail_id
                            left join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                            left join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                            left join received_header on received_detail.received_header_id = received_header.received_header_id
                        where
                            bill_header.close_date >= '{$this->fromDate}'::date
                            and bill_header.close_date <= '{$this->toDate}'::date
                    ) as t_detail on temp_receivable.customer_id = t_detail.customer_id
                ";
            }
        } else {
            // ヘッダーモード
            $this->selectQuery .= "
            from
                temp_receivable
            ";
        }
        $this->selectQuery .= "
                left join customer_master on temp_receivable.customer_id = customer_master.customer_id
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
            [Orderby]
        ";
        $this->orderbyDefault = "temp_receivable.customer_no, currency_name" . ($this->isDetailMode ? ", show_date_1, item_code" : "");
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
        $form['gen_pageTitle'] = _g("売掛残高表");
        $form['gen_menuAction'] = "Menu_Delivery";
        $form['gen_listAction'] = "Delivery_ReceivableList_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = 'customer_id';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("売掛残高");

        // Excel出力
        $form['gen_excelShowArray'] = array(array(1, 0, sprintf(_g("%1\$s から %2\$s まで"), $this->fromDate, $this->toDate)));

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("売掛残高一覧表 印刷"),
                'link' => "javascript:reportPrint(1);",
                'reportEdit' => 'Delivery_ReceivableList_Report'
            ),
            array(
                'label' => _g("売掛残高明細 印刷"),
                'link' => "javascript:reportPrint(2);",
                'reportEdit' => 'Delivery_ReceivableList_Report2'
            ),
            array(
                'label' => _g("得意先元帳 印刷"),
                'link' => "javascript:reportPrint(3);",
                'reportEdit' => 'Delivery_ReceivableList_Report3'
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
                var postUrl = 'Delivery_ReceivableList_List&gen_reportAction=Delivery_ReceivableList_Report';
                if (cat == 2) {
                    postUrl += '2';
                } else if (cat == 3) {
                    postUrl += '3';
                }
                gen.list.printReport(postUrl,'');
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#f9bdbd" => "'[credit_over]'=='1'", // 与信限度額超過（ピンク）
        );
        $form['gen_colorSample'] = array(
            "f9bdbd" => array(_g("ピンク"), _g("与信限度額超過")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
                'width' => '120',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'width' => '250',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('請求パターン'),
                'field' => 'bill_pattern_show',
                'width' => '130',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('締日'),
                'field' => 'monthly_limit_date_show',
                'width' => '70',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '70',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
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
                'field' => 'before_sales',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('期間中売上額'),
                'field' => 'sales',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('期間中消費税額'),
                'field' => 'sales_tax',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('期間中入金額'),
                'field' => 'paying_in',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('売掛金残高'),
                'field' => 'receivable_balance',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
            ),
            array(
                'label' => _g('与信限度額'),
                'field' => 'credit_line',
                'width' => '100',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'customer_no',
                'hide' => true,
            ),
        );
        if ($this->isDetailMode) {
            // 明細モード
            $form['gen_columnArray'][] = array(
                'label' => _g('納品書番号'),
                'field' => 'delivery_no',
                'type' => 'data',
                'width' => '100',
                'sameCellJoin' => true,
                'align' => 'center',
            );
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
                'label' => _g('受注番号'),
                'field' => 'received_number',
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
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '40',
                'align' => 'center',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('納品単価'),
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
                'helpText_noEscape' => _g("月末時点の在庫評価単価を表示します。詳細は在庫リスト画面の「評価単価」のチップヘルプを参照してください。"), 
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
                'label' => _g('期間中入金額(現金)'),
                'field' => 'paying_in_1',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(振込)'),
                'field' => 'paying_in_2',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(小切手)'),
                'field' => 'paying_in_3',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(手形)'),
                'field' => 'paying_in_4',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(相殺)'),
                'field' => 'paying_in_5',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(値引)'),
                'field' => 'paying_in_6',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(振込手数料)'),
                'field' => 'paying_in_7',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(先振込)'),
                'field' => 'paying_in_9',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(代引)'),
                'field' => 'paying_in_10',
                'width' => '120',
                'type' => 'numeric',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('期間中入金額(その他)'),
                'field' => 'paying_in_8',
                'width' => '120',
                'type' => 'numeric',
            );
        }
    }

}
