<?php

class Manufacturing_Estimate_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;
        
        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('見積番号'),
                'field' => 'estimate_number',
            ),
            array(
                'label' => _g('日付'),
                'type' => 'dateFromTo',
                'field' => 'estimate_date',
                'defaultFrom' => date('Y-m-01'),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('合計金額'),
                'type' => 'numFromTo',
                'field' => 'amount_header',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'ime' => 'on',
            ),
            array(
                'label' => _g('ランク'),
                'type' => 'select',
                'field' => 'estimate_rank',
                'options' => Gen_Option::getEstimateRank('search'),
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
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'hide' => true,
            ),
            array(
                'label' => _g('客先担当者名'),
                'field' => 'person_in_charge',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('自社部門コード/名'),
                'field' => 'section_code',
                'field2' => 'section_name',
                'hide' => true,
            ),
            array(
                'label' => _g('自社担当者コード/名'),
                'field' => 'worker_code',
                'field2' => 'worker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'estimate_detail___item_code',
                'field2' => 'estimate_detail___item_name',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 'customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
                'hide' => true,
            ),
            array(
                'label' => _g('見積備考'),
                'field' => 'estimate_header___remarks',
                'ime' => 'on',
                'hide' => true,
            ),
        );
    
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("日次見積額（当月）") => self::_getPreset("5", "gen_all", "estimate_date_day", ""),
                _g("月次見積額（今年）") => self::_getPreset("7", "gen_all", "estimate_date_month", ""),
                _g("見積額 前年対比") => self::_getPreset("0", "estimate_date_month", "estimate_date_year", ""),
                _g("得意先見積ランキング（今年）") => self::_getPreset("7", "gen_all", "customer_name", "order by field1 desc"),
                _g("品目見積ランキング（今年）") => self::_getPreset("7", "gen_all", "item_name", "order by field1 desc"),
                _g("担当者見積ランキング（今年）") => self::_getPreset("7", "gen_all", "worker_name", "order by field1 desc"),
                _g("部門見積ランキング（今年）") => self::_getPreset("7", "gen_all", "section_name", "order by field1 desc"),
                _g("得意先 - 品目（今年）") => self::_getPreset("7", "customer_name", "item_name", ""),
                _g("得意先別月次見積額（今年）") => self::_getPreset("7", "estimate_date_month", "customer_name", ""),
                _g("品目別月次見積額（今年）") => self::_getPreset("7", "estimate_date_month", "item_name", ""),
                _g("品目別月次見積数量（今年）") => self::_getPreset("7", "estimate_date_month", "item_name", "", "quantity"),
                _g("担当者別月次見積額（今年）") => self::_getPreset("7", "estimate_date_month", "worker_name", ""),
                _g("部門別月次見積額（今年）") => self::_getPreset("7", "estimate_date_month", "section_name", ""),
                _g("データ入力件数（今年）") => self::_getPreset("7", "estimate_date_month", "gen_record_updater", "", "gen_record_updater", "count"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby = "", $value = "amount", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "estimate_date", "dp" => $datePattern),
                    
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
        $classQuery = Gen_Option::getEstimateRank('list-query');
        
        if ($this->isDetailMode) {
            // 明細モード
            $this->selectQuery = "
                select
                    estimate_header.estimate_header_id
                    ,estimate_detail.estimate_detail_id
                    ,line_no

                    ,estimate_header.estimate_number
                    ,estimate_header.subject
                    ,estimate_header.customer_id
                    ,t_customer.customer_no    /* for csv */
                    ,estimate_header.customer_name
                    ,estimate_header.customer_zip
                    ,estimate_header.customer_address1
                    ,estimate_header.customer_address2
                    ,estimate_header.customer_tel
                    ,estimate_header.customer_fax
                    ,estimate_header.estimate_date
                    ,estimate_header.person_in_charge
                    ,estimate_header.delivery_date
                    ,estimate_header.delivery_place
                    ,estimate_header.mode_of_dealing
                    ,estimate_header.expire_date
                    ,estimate_header.estimate_rank
                    ,section_master.section_code
                    ,section_master.section_name
                    ,worker_master.worker_code
                    ,worker_master.worker_name
                    ,received_header.received_number
                    ,estimate_header.remarks
                    ,coalesce(t_no_item.item_count,0) as item_count
                    ,case estimate_header.estimate_rank {$classQuery} end as estimate_rank_show
                        
                    ,t_customer_group_1.customer_group_code as customer_group_code_1
                    ,t_customer_group_1.customer_group_name as customer_group_name_1
                    ,t_customer_group_2.customer_group_code as customer_group_code_2
                    ,t_customer_group_2.customer_group_name as customer_group_name_2
                    ,t_customer_group_3.customer_group_code as customer_group_code_3
                    ,t_customer_group_3.customer_group_name as customer_group_name_3

                    ,estimate_detail.item_code
                    ,estimate_detail.item_name
                    ,estimate_detail.quantity
                    ,estimate_detail.measure
                    ,estimate_detail.base_cost
                    ,estimate_detail.base_cost_total
                    ,estimate_detail.sale_price
                    ,estimate_detail.estimate_amount as amount
                    ,estimate_detail.estimate_amount - estimate_detail.base_cost_total as profit
                    ,estimate_detail.estimate_tax
                    ,case when coalesce(estimate_detail.tax_class,0)=0 then '" . _g('課税') . "' else '" . _g('非課税') . "' end as tax_class
                    ,estimate_detail.tax_class as tax_class_csv
                    ,estimate_detail.remarks as remarks_detail
                    ,estimate_detail.remarks_2 as remarks_detail_2

                    /* foreign_currency */
                    ,currency_name
                    ,foreign_currency_rate
                    ,foreign_currency_sale_price
                    ,foreign_currency_estimate_amount
                    ,foreign_currency_base_cost_total
                    ,foreign_currency_estimate_tax

                    ,coalesce(estimate_detail.record_update_date, estimate_detail.record_create_date) as gen_record_update_date
                    ,coalesce(estimate_detail.record_updater, estimate_detail.record_creator) as gen_record_updater
                from
                    estimate_header
                    inner join estimate_detail on estimate_header.estimate_header_id = estimate_detail.estimate_header_id
                    left join (select estimate_header_id, count(estimate_detail_id) as item_count from estimate_detail
                        where item_id is not null group by estimate_header_id) as t_no_item on estimate_header.estimate_header_id = t_no_item.estimate_header_id
                    left join (select estimate_header_id, sum(quantity * sale_price) as amount_header from estimate_detail
                        group by estimate_header_id) as t_amount on estimate_header.estimate_header_id = t_amount.estimate_header_id
                    left join item_master on estimate_detail.item_code = item_master.item_code
                    left join worker_master on estimate_header.worker_id = worker_master.worker_id
                    left join section_master on estimate_header.section_id = section_master.section_id
                    /* 本来、received_headerの中でestimate_header_idはユニークになるはずで、その状態であればreceived_headerを
                        単純にJOINしてもいい。しかし15i rev.20140820より前は受注コピーするとestimate_header_idまでコピーされて
                        しまうという不具合があり、ユニークになっていない場合があった。そのためgroupbyしてJOINをしている。
                        ちなみに、単純にgroupbyしただけだとカスタム項目の処理がうまくいかないので、下記のように2段階にしている。*/
                    left join (select estimate_header_id, min(received_header_id) as received_header_id from received_header group by estimate_header_id) as t_received_pre
                         on estimate_header.estimate_header_id = t_received_pre.estimate_header_id
                    left join received_header on t_received_pre.received_header_id = received_header.received_header_id
                    left join currency_master on estimate_detail.foreign_currency_id = currency_master.currency_id
                    left join (select customer_id, customer_no, customer_group_id_1, customer_group_id_2, customer_group_id_3 from customer_master) 
                        as t_customer on estimate_header.customer_id = t_customer.customer_id
                    left join customer_group_master as t_customer_group_1 on t_customer.customer_group_id_1 = t_customer_group_1.customer_group_id
                    left join customer_group_master as t_customer_group_2 on t_customer.customer_group_id_2 = t_customer_group_2.customer_group_id
                    left join customer_group_master as t_customer_group_3 on t_customer.customer_group_id_3 = t_customer_group_3.customer_group_id
                [Where]
                [Orderby]
            ";
        } else {
            // ヘッダモード
            $this->selectQuery = "
                select
                    estimate_header.estimate_header_id
                    ,count(estimate_detail.*) as detail_count
                    ,max(estimate_header.estimate_number) as estimate_number
                    ,max(estimate_header.subject) as subject
                    ,max(estimate_header.customer_id) as customer_id
                    ,max(estimate_header.customer_name) as customer_name
                    ,max(estimate_header.customer_zip) as customer_zip
                    ,max(estimate_header.customer_address1) as customer_address1
                    ,max(estimate_header.customer_address2) as customer_address2
                    ,max(estimate_header.customer_tel) as customer_tel
                    ,max(estimate_header.customer_fax) as customer_fax
                    ,max(estimate_header.estimate_date) as estimate_date
                    ,max(estimate_header.person_in_charge) as person_in_charge
                    ,max(estimate_header.delivery_date) as delivery_date
                    ,max(estimate_header.delivery_place) as delivery_place
                    ,max(estimate_header.mode_of_dealing) as mode_of_dealing
                    ,max(estimate_header.expire_date) as expire_date
                    ,max(section_master.section_code) as section_code
                    ,max(section_master.section_name) as section_name
                    ,max(worker_master.worker_code) as worker_code
                    ,max(worker_master.worker_name) as worker_name
                    ,max(received_header.received_number) as received_number
                    ,max(estimate_header.remarks) as remarks
                    ,coalesce(max(t_no_item.item_count),0) as item_count
                    ,max(case estimate_header.estimate_rank {$classQuery} end) as estimate_rank_show
                        
                    ,max(t_customer_group_1.customer_group_code) as customer_group_code_1
                    ,max(t_customer_group_1.customer_group_name) as customer_group_name_1
                    ,max(t_customer_group_2.customer_group_code) as customer_group_code_2
                    ,max(t_customer_group_2.customer_group_name) as customer_group_name_2
                    ,max(t_customer_group_3.customer_group_code) as customer_group_code_3
                    ,max(t_customer_group_3.customer_group_name) as customer_group_name_3

                    ,max(amount_header) as amount_header
                    ,sum(estimate_amount - base_cost_total) as profit_header
                    ,sum(estimate_detail.estimate_tax) as estimate_tax_header
                    
                    /* foreign_currency */
                    ,sum(foreign_currency_estimate_amount) as foreign_currency_estimate_amount
                    ,sum(foreign_currency_base_cost_total) as foreign_currency_base_cost_total
                    ,sum(foreign_currency_estimate_tax) as foreign_currency_estimate_tax

                    ,max(coalesce(estimate_detail.record_update_date, estimate_detail.record_create_date)) as gen_record_update_date
                    ,max(coalesce(estimate_detail.record_updater, estimate_detail.record_creator)) as gen_record_updater
                from
                    estimate_header
                    inner join estimate_detail on estimate_header.estimate_header_id = estimate_detail.estimate_header_id
                    left join (select estimate_header_id, count(estimate_detail_id) as item_count from estimate_detail
                        where item_id is not null group by estimate_header_id) as t_no_item on estimate_header.estimate_header_id = t_no_item.estimate_header_id
                    left join (select estimate_header_id, sum(estimate_amount) as amount_header from estimate_detail
                        group by estimate_header_id) as t_amount on estimate_header.estimate_header_id = t_amount.estimate_header_id
                    left join item_master on estimate_detail.item_code = item_master.item_code
                    left join worker_master on estimate_header.worker_id = worker_master.worker_id
                    left join section_master on estimate_header.section_id = section_master.section_id
                    /* このJOINについては明細モードSQL内のコメントを参照 */
                    left join (select estimate_header_id, min(received_header_id) as received_header_id from received_header group by estimate_header_id) as t_received_pre
                         on estimate_header.estimate_header_id = t_received_pre.estimate_header_id
                    left join received_header on t_received_pre.received_header_id = received_header.received_header_id
                    left join (select customer_id, customer_no, customer_group_id_1, customer_group_id_2, customer_group_id_3 from customer_master) 
                        as t_customer on estimate_header.customer_id = t_customer.customer_id
                    left join customer_group_master as t_customer_group_1 on t_customer.customer_group_id_1 = t_customer_group_1.customer_group_id
                    left join customer_group_master as t_customer_group_2 on t_customer.customer_group_id_2 = t_customer_group_2.customer_group_id
                    left join customer_group_master as t_customer_group_3 on t_customer.customer_group_id_3 = t_customer_group_3.customer_group_id
                [Where]
                group by
                    estimate_header.estimate_header_id
                [Orderby]
            ";
        }
        $this->orderbyDefault = 'estimate_number desc' . ($this->isDetailMode ? ", line_no" : "");
        if ($this->isDetailMode) {
            $this->customColumnTables = array(
                // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
                //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
                //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
                //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
                //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
                //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
                //          estimate_detail, received_detail, delivery_detail, order_detail
                array("item_master", "", "", "estimate_detail_id"),
                array("worker_master", "", "", "estimate_header_id"),
                array("section_master", "", "", "estimate_header_id"),
                array("received_header", "", "", "estimate_header_id"),
            );        
        }
    }
    
    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("見積");
        $form['gen_importMsg_noEscape'] =
            _g("※データは新規登録されます。（既存データの上書きはできません）") . "<br>" .
            _g("　見積番号が既存の場合はエラーになります。"). "<br><br>" .
            _g("※見積番号が連続したデータは複数の明細行としてインポートされます。") . "<br>" .
            _g("　ファイル内で同一の見積番号が連続しない場合は重複エラーになります。");
        $form['gen_allowUpdateCheck'] = false;

        // 通知メール（成功時のみ）
        $form['gen_csvAlertMail_id'] = 'manufacturing_estimate_new';   // Master_AlertMail_Edit冒頭を参照
        $form['gen_csvAlertMail_title'] = _g("見積も登録");
        $form['gen_csvAlertMail_body'] = _g("見積データがCSVインポートされました。");    // インポート件数等が自動的に付加される

        $form['gen_csvArray'] = array(
                array(
                    'label'=>_g('見積番号'),
                    'field' => 'estimate_number',
                    'header' => true,             // これを指定すると、連続するレコードでこのカラムの値が同じ場合に、同一header内の明細データとして扱われる
                    'table' => 'estimate_header', // headerフラグが指定されているとき用。親テーブル
                    'id' => 'estimate_header_id', // headerフラグが指定されているとき用。親ID
                    'unique' => true,             // これを指定すると、インポート時にCSVファイル内での重複がチェックされる（上記のケースを除く）
                ),
                array(
                    'label' => _g('発行日'),
                    'field' => 'estimate_date',
                    'isDate' => true,   // yyyymmdd、yy/mm/dd, yyyy-mm-dd などの形式も受け付ける
                ),
                array(
                    'label' => _g('得意先コード'),
                    'addLabel' => _g('(マスタに登録された得意先のみ)'),
                    'field' => 'customer_no',
                ),
                array(
                    'label' => _g('得意先名'),
                    'addLabel' => _g('(インポートの際は、マスタ未登録の得意先のみ)'),
                    'field' => 'customer_name',
                ),
                array(
                    'label' => _g('客先担当者名'),
                    'field' => 'person_in_charge',
                ),
                array(
                    'label' => _g('郵便番号'),
                    'field' => 'customer_zip',
                ),
                array(
                    'label' => _g('得意先住所1'),
                    'field' => 'customer_address1',
                ),
                array(
                    'label' => _g('得意先住所2'),
                    'field' => 'customer_address2',
                ),
                array(
                    'label' => _g('得意先TEL'),
                    'field' => 'customer_tel',
                ),
                array(
                    'label' => _g('得意先FAX'),
                    'field' => 'customer_fax',
                ),
                array(
                    'label' => _g('担当者(自社)コード'),
                    'field' => 'worker_code',
                ),
                array(
                    'label' => _g('部門(自社)'),
                    'field' => 'section_code',
                ),
                array(
                    'label' => _g('ランク'),
                    'addLabel' => _g('(0[なし]/1[A]/2[B]/3[C]/4[D]/5[E])'),
                    'field' => 'estimate_rank',
                ),
                array(
                    'label' => _g('件名'),
                    'field' => 'subject',
                ),
                array(
                    'label' => _g('受渡期日'),
                    'field' => 'delivery_date',
                ),
                array(
                    'label' => _g('受渡場所'),
                    'field' => 'delivery_place',
                ),
                array(
                    'label' => _g('お支払条件'),
                    'field' => 'mode_of_dealing',
                ),
                array(
                    'label' => _g('有効期限'),
                    'field' => 'expire_date',
                ),
                array(
                    'label' => _g('見積備考'),
                    'field' => 'remarks',
                ),
            // ****** estimate_detail ********
                array(
                    'label' => _g('品目コード'),
                    'addLabel' => _g('(マスタに登録された品目のみ)'),
                    'field' => 'item_code',
                ),
                array(
                    'label' => _g('品目名'),
                    'addLabel' => _g('(インポートの際、品目コードがマスタ登録済の場合は無視される)'),
                    'field' => 'item_name',
                ),
                array(
                    'label' => _g('数量'),
                    'field' => 'quantity',
                ),
                array(
                    'label' => _g('単位'),
                    'field' => 'measure',
                ),
                array(
                    'label' => _g('課税区分'),
                    'addLabel' => _g('(0[課税]／1[非課税])'),
                    'field' => 'tax_class',
                    'exportField' => 'tax_class_csv',
                ),
                array(
                    'label' => _g('見積単価'),
                    'field' => 'sale_price',
                ),
                array(
                    'label' => _g('見積明細備考1'),
                    'field' => 'remarks_detail',
                ),
                array(
                    'label' => _g('見積明細備考2'),
                    'field' => 'remarks_detail_2',
                ),
            );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("見積登録");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Estimate_List";
        $form['gen_editAction'] = "Manufacturing_Estimate_Edit";
        $form['gen_idField'] = ($this->isDetailMode ? 'estimate_detail_id' : 'estimate_header_id');
        $form['gen_idFieldForUpdateFile'] = "estimate_header.estimate_header_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("見積");

        $form['gen_checkAndDoLinkArray'] = array(
            array(
                'id' => 'convertToReceived',
                'value' => _g('受注へ転記'),
                'onClick' => "javascript:copyToReceived();",
            )
        );

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("見積書 印刷"),
                'link' => "javascript:gen.list.printReport('Manufacturing_Estimate_Report" . ($this->isDetailMode ? "&detail=true" : "") . "','check')",
                'reportEdit' => 'Manufacturing_Estimate_Report'
            ),
        );

        $form['gen_javascript_noEscape'] = "
            function copyToReceived(){
                var inputDate = '';
                var frm = gen.list.table.getCheckedPostSubmit('chk_rec');
                if (frm.count == 0) {
                   alert('" . _g("受注へ転記するデータを選択してください。") . "');
                } else {
                    while(true) {
                        var msg = \"" . _g("注意：品目マスタ画面で登録されていない明細行は受注登録画面へ転記されません。") . "\\n\\n\";
                        msg += '" . _g("受注日を入力してください（空欄にすると見積日が受注日になります）") . "';
                        recDate = window.prompt(msg, gen.date.getCalcDateStr(0));
                    	if (recDate===null) return;
                    	if (recDate=='' || gen.date.isDate(recDate)) break;
                        alert('" . _g('日付が正しくありません。') . "');
                    }
                    while(true) {
                    	deadLine = window.prompt('" . _g('受注納期を入力してください（空欄にすると受注日と同じになります）') . "', gen.date.getCalcDateStr(0));
                    	if (deadLine===null) return;
                    	if (deadLine=='' || gen.date.isDate(deadLine)) break;
                        alert('" . _g('日付が正しくありません。') . "');
                    }
                    var postUrl = 'index.php?action=Manufacturing_Estimate_CopyToReceived'" . ($this->isDetailMode ? " + '&detail=true'" : "") . ";
                    postUrl += '&received_date=' + recDate;
                    postUrl += '&dead_line=' + deadLine;
                    frm.submit(postUrl);
                    // 画面更新とwaitダイアログ表示。listUpdateによるAjax更新はBulkInspectionクラスの処理が終わるまで
                    // session_start()で足止めになるので、結果として処理が終わるまでダイアログが出たままとなる。
                    listUpdate(null, false);
                }
            }
        ";

        $form['gen_isClickableTable'] = "true";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[received_number]'!=''", // 受注転記ずみ
        );

        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("受注転記済み")),
        );

        //  モードにより動的に列を切り替える場合、モードごとに列情報（列順、列幅、ソートなど）を別々に保持できるよう、次の設定が必要。
        $form['gen_columnMode'] = ($this->isDetailMode ? "detail" : "list");

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('コピー'),
                'type' => 'copy',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Manufacturing_Estimate_BulkDelete' . ($this->isDetailMode ? "&detail=true" : ""),
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('印刷'),
                'name' => 'check',
                'type' => 'checkbox',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
            ),
            array(
                'label' => _g('受注'),
                'name' => 'chk_rec',
                'type' => 'checkbox',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                // 「受注未転記 and 取引先IDが記録されている and 品目IDが一行以上記録されている and readonlyでない」の場合に転記可能
                // 15iではいったん、ミス防止のためすべての行に品目IDがないと登録できない仕様としたが、やはりそれでは不便という話になり元に戻した。
                // ag.cgi?page=ProjectDocView&ppid=1516&pbid=174580
                'showCondition' => "'[received_number]'=='' && '[customer_id]'!='' && '[item_count]'>0 && " . ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('見積番号'),
                'field' => 'estimate_number',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('ランク'),
                'field' => 'estimate_rank_show',
                'width' => '45',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('日付'),
                'field' => 'estimate_date',
                'type' => 'date',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'width' => '200',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
            ),
            array(
                'label' => _g('取引先グループコード1'),
                'field' => 'customer_group_code_1',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名1'),
                'field' => 'customer_group_name_1',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループコード2'),
                'field' => 'customer_group_code_2',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名2'),
                'field' => 'customer_group_name_2',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループコード3'),
                'field' => 'customer_group_code_3',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名3'),
                'field' => 'customer_group_name_3',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            // ********** headerMode only ここから **********
            array(
                'label' => _g('合計金額'),
                'field' => 'amount_header',
                'type' => 'numeric',
                'visible' => !$this->isDetailMode,
            ),
            array(
                'label' => _g('粗利'),
                'field' => 'profit_header',
                'type' => 'numeric',
                'visible' => !$this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('合計税額'),
                'field' => 'estimate_tax_header',
                'type' => 'numeric',
                'visible' => !$this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('明細行数'),
                'field' => 'detail_count',
                'width' => '60',
                'type' => 'numeric',
                'visible' => !$this->isDetailMode,
                'hide' => true,
            ),
            // ********** headerMode only ここまで **********
            array(
                'label' => _g('件名'),
                'field' => 'subject',
                'width' => '200',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
            ),
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
                'helpText_noEscape' => _g("見積を受注に転記した場合に、この項目に転記先の受注番号が表示されます。")
            ),
            array(
                'label' => _g('客先担当者名'),
                'field' => 'person_in_charge',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('郵便番号'),
                'field' => 'customer_zip',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('得意先住所1'),
                'field' => 'customer_address1',
                'width' => '200',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('得意先住所2'),
                'field' => 'customer_address2',
                'width' => '200',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('得意先TEL'),
                'field' => 'customer_tel',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('得意先FAX'),
                'field' => 'customer_fax',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('自社部門コード'),
                'field' => 'section_code',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('自社部門名'),
                'field' => 'section_name',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('自社担当者コード'),
                'field' => 'worker_code',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('自社担当者名'),
                'field' => 'worker_name',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('受渡場所'),
                'field' => 'delivery_place',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('取引条件'),
                'field' => 'mode_of_dealing',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('有効期限'),
                'field' => 'expire_date',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            array(
                'label' => _g('見積備考'),
                'field' => 'remarks',
                'width' => '200',
                'sameCellJoin' => true,
                'parentColumn' => 'estimate_number',
                'hide' => true,
            ),
            // ********** detailMode only ここから **********
            array(
                'label' => _g('行'),
                'field' => 'line_no',
                'width' => '50',
                'align' => 'center',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('数量'),
                'field' => 'quantity',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('見積単価'),
                'field' => 'sale_price',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('金額'),
                'field' => 'amount',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('販売原単価'),
                'field' => 'base_cost',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('販売原価'),
                'field' => 'base_cost_total',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('粗利'),
                'field' => 'profit',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('課税区分'),
                'field' => 'tax_class',
                'type' => 'data',
                'width' => '50',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('消費税額'),
                'field' => 'estimate_tax',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '50',
                'align' => 'center',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('レート'),
                'field' => 'foreign_currency_rate',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('見積単価(外貨)'),
                'field' => 'foreign_currency_sale_price',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            // ********** detailMode only ここまで **********
            array(
                'label' => _g('受注額(外貨)'),
                'field' => 'foreign_currency_estimate_amount',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('販売原価(外貨)'),
                'field' => 'foreign_currency_base_cost_total',
                'type' => 'numeric',
                'hide' => true,
            ),
            // ********** detailMode only ここから **********
            array(
                'label' => _g('見積明細備考1'),
                'field' => 'remarks_detail',
                'type' => 'data',
                'width' => '120',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('見積明細備考2'),
                'field' => 'remarks_detail_2',
                'type' => 'data',
                'width' => '120',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            // ********** detailMode only ここまで **********
        );
    }

}
