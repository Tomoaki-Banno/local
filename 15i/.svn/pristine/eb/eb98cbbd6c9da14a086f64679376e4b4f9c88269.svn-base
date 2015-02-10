<?php

class Partner_Order_List extends Base_ListBase
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
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'helpText_noEscape' => _g('製番オーダーだけが検索対象となります。'),
                'hide' => true,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'order_detail___item_code',
                'field2' => 'order_detail___item_name',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
            ),
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'helpText_noEscape' => _g('製番オーダーだけが検索対象となります。'),
                'hide' => true,
            ),
            array(
                'label' => _g('発注先コード/名'),
                'field' => 'customer_master___customer_no',
                'field2' => 'customer_master___customer_name',
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 'customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
                'hide' => true,
            ),
            array(
                'label' => _g('担当者コード/名'),
                'field' => 'worker_code',
                'field2' => 'worker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('部門コード/名'),
                'field' => 'section_code',
                'field2' => 'section_name',
                'hide' => true,
            ),
            array(
                'label' => _g('発注日'),
                'type' => 'dateFromTo',
                'field' => 'order_date',
                'defaultFrom' => date('Y-m-01'), // 終了日を指定するとMRP取込のとき翌月データが表示できず不自然
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('注文納期'),
                'type' => 'dateFromTo',
                'field' => 'order_detail_dead_line',
                'rowSpan' => 2,
                'hide' => true,
            ),
            array(
                'label' => _g('親品目'),
                'type' => 'select',
                'type' => 'dropdown',
                'field' => 'parent_item_id',
                'size' => '150',
                'dropdownCategory' => 'item',
                'nosql' => true,
                'rowSpan' => 2,
                'hide' => true,
            ),
            array(
                'label' => _g('完了分の表示'),
                'type' => 'select',
                'field' => 'completed_status',
                'options' => Gen_Option::getTrueOrFalse('search-show'), // 「しない」時は order_detail_completed = false のレコードに限定
                'nosql' => 'true',
                'default' => 'false',
            ),
            array(
                'label' => _g('印刷状況'),
                'type' => 'select',
                'field' => 'printed',
                'options' => Gen_Option::getPrinted('search'),
                'nosql' => 'true',
                'default' => '0',
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'order_header___remarks_header',
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
        );
        // 表示条件クリアの指定がされていたときの設定。
        // 進捗画面のリンク等からレコード指定でこの画面を開いたときのため。
        if (isset($form['gen_searchConditionClear'])) {
            $form['gen_search_completed_status'] = 'true';  // 完了データの表示を「する」にしておく。
            $form['gen_search_printed'] = '0';
            $form['gen_search_show_detail'] = 'true';
        }
        
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("日次注文額（当月）") => self::_getPreset("5", "gen_all", "order_date_day", ""),
                _g("月次注文額（今年）") => self::_getPreset("7", "gen_all", "order_date_month", ""),
                _g("注文額 前年対比") => self::_getPreset("0", "order_date_month", "order_date_year", ""),
                _g("品目別 受入残数") => self::_getPreset("0", "order_detail_dead_line_day", "item_name", "", "remained_qty", "sum", "false"),
                _g("品目別 受入残額") => self::_getPreset("0", "order_detail_dead_line_day", "item_name", "", "remained_amount", "sum", "false"),
                _g("発注先別 受入残数") => self::_getPreset("0", "order_detail_dead_line_day", "customer_name", "", "remained_qty", "sum", "false"),
                _g("発注先別 受入残額") => self::_getPreset("0", "order_detail_dead_line_day", "customer_name", "", "remained_amount", "sum", "false"),
                _g("発注先注文ランキング（今年）") => self::_getPreset("7", "gen_all", "customer_name", "order by field1 desc"),
                _g("品目注文ランキング（今年）") => self::_getPreset("7", "gen_all", "item_name", "order by field1 desc"),
                _g("担当者注文ランキング（今年）") => self::_getPreset("7", "gen_all", "worker_name", "order by field1 desc"),
                _g("部門注文ランキング（今年）") => self::_getPreset("7", "gen_all", "section_name", "order by field1 desc"),
                _g("発注先 - 品目（今年）") => self::_getPreset("7", "customer_name", "item_name", ""),
                _g("発注先別 月次注文額（今年）") => self::_getPreset("7", "order_date_month", "customer_name", ""),
                _g("品目別 月次注文額（今年）") => self::_getPreset("7", "order_date_month", "item_name", ""),
                _g("品目別 月次発注数（今年）") => self::_getPreset("7", "order_date_month", "item_name", "", "order_detail_quantity"),
                _g("担当者別 月次注文額（今年）") => self::_getPreset("7", "order_date_month", "worker_name", ""),
                _g("部門別 月次注文額（今年）") => self::_getPreset("7", "order_date_month", "section_name", ""),
                _g("データ入力件数（今年）") => self::_getPreset("7", "order_date_month", "gen_record_updater", "", "gen_record_updater", "count"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby = "", $value = "amount", $method = "sum", $completedStatus = "true")
    {
        return
            array(
                "data" => array(
                    array("f" => "order_date", "dp" => $datePattern),
                    array("f" => "completed_status", "v" => $completedStatus),
                    
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
        $this->isDetailMode = (@$form['gen_search_show_detail'] == "true") || isset($form['gen_csvMode']);
    }

    function setQueryParam(&$form)
    {
        // 所要量計算結果の取り込み
        $orderHeaderIdList = "";
        if (isset($form['mrp'])) {
            $orderHeaderIdArray = Logic_Order::mrpToOrder(0);

            if (is_array($orderHeaderIdArray)) {
                $orderHeaderIdList = join($orderHeaderIdArray, ",");    // 配列をカンマ区切り文字列にする
                $_SESSION['gen_order_list_for_mrp_mode'] = $orderHeaderIdList;
                Gen_Log::dataAccessLog(_g("注文書"), _g("新規"), _g("所要量計算結果からの一括発行"));
            }
        }

        // MRP取込モードで、明細画面へ行ってから戻ったときに、モードが解除されてしまわないようにするためのsession処理
        if (isset($form['mrp']) || (isset($form['gen_restore_search_condition']) && @$_SESSION['gen_order_list_for_mrp_mode'] != "")) {
            if (!isset($form['mrp'])) {
                $orderHeaderIdList = $_SESSION['gen_order_list_for_mrp_mode'];
                $form['mrp'] = true;
            }
        } else {
            unset($_SESSION['gen_order_list_for_mrp_mode']);
        }

        // 親品目が指定されている場合はtemp_bom_expandテーブルを準備
        if (is_numeric(@$form['gen_search_parent_item_id'])) {
            Logic_Bom::expandBom($form['gen_search_parent_item_id'], 0, false, false, false);
        }

        $this->selectQuery = "
            select
                order_header.order_header_id
        ";
        if ($this->isDetailMode) {
            // 明細モード
            $this->selectQuery .= "
                ,order_detail.order_detail_id
                ,line_no
                ,case when order_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed
                ,order_id_for_user
                ,order_date
                ,customer_master.customer_no
                ,customer_name
                ,customer_master.template_partner_order
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,delivery_partner_no
                ,delivery_partner_name
                ,order_header.remarks_header
                ,worker_code
                ,worker_name
                ,section_code
                ,section_name
                ,order_no
                ,seiban
                ,received_number
                ,order_detail.item_id
                ,order_detail.item_code
                ,order_detail.item_name
                ,item_price
                ,maker_name
                ,spec
                ,rack_no
                ,item_sub_code
                ,item_master.comment as item_remarks_1
                ,item_master.comment_2 as item_remarks_2
                ,item_master.comment_3 as item_remarks_3
                ,item_master.comment_4 as item_remarks_4
                ,item_master.comment_5 as item_remarks_5
                ,order_detail_quantity
                ,order_measure
                ,order_detail_quantity / coalesce(multiple_of_order_measure,1) as show_quantity
                ,item_price * coalesce(multiple_of_order_measure,1) as show_price
                ,multiple_of_order_measure
                ,coalesce(order_amount, item_price * order_detail_quantity) as amount
                ,order_detail_dead_line
                ,coalesce(accepted_quantity,0) as accepted_quantity
                ,case when completed_status = 1 then '" . _g("完") . "' else
                  '" . _g("未(残") . " ' || (COALESCE(order_detail_quantity,0) - COALESCE(accepted_quantity,0)) || ')' end as completed
                ,case when order_detail_completed then 0
                    else coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0) end as remained_qty
                ,case when order_detail_completed then 0
                    else (coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) * item_price end as remained_amount
                ,case when t_acc.odi is null then 0 else 1 end as accepted_exist
                ,case when alarm_flag then 't' else 'f' end as alarm_flag
                ,order_detail.remarks
                
                -- foreign_currency
                ,currency_name
                ,foreign_currency_rate
                ,foreign_currency_item_price
                ,gen_round_precision(foreign_currency_item_price * coalesce(multiple_of_order_measure,1), customer_master.rounding, " . GEN_FOREIGN_CURRENCY_PRECISION . ") as foreign_currency_show_price
                ,coalesce(foreign_currency_order_amount, foreign_currency_item_price * order_detail_quantity) as foreign_currency_order_amount

                -- for csv
                ,case when foreign_currency_id is null then item_price else foreign_currency_item_price end as item_price_for_csv

                ,coalesce(order_detail.record_update_date, order_detail.record_create_date) as gen_record_update_date
                ,coalesce(order_detail.record_updater, order_detail.record_creator) as gen_record_updater
            ";
        } else {
            // 通常（ヘッダ）モード
            $this->selectQuery .= "
                ,count(order_detail.*) as detail_count
                ,max(order_id_for_user) as order_id_for_user
                ,max(customer_master.customer_no) as customer_no
                ,max(customer_name) as customer_name
                ,max(customer_master.template_partner_order) as template_partner_order
                ,max(t_customer_group_1.customer_group_code) as customer_group_code_1
                ,max(t_customer_group_1.customer_group_name) as customer_group_name_1
                ,max(t_customer_group_2.customer_group_code) as customer_group_code_2
                ,max(t_customer_group_2.customer_group_name) as customer_group_name_2
                ,max(t_customer_group_3.customer_group_code) as customer_group_code_3
                ,max(t_customer_group_3.customer_group_name) as customer_group_name_3
                ,max(delivery_partner_no) as delivery_partner_no
                ,max(delivery_partner_name) as delivery_partner_name
                ,max(order_date) as order_date
                ,max(order_header.remarks_header) as remarks_header
                ,max(worker_code) as worker_code
                ,max(worker_name) as worker_name
                ,max(section_code) as section_code
                ,max(section_name) as section_name
                ,max(case when order_printed_flag = true then '" . _g("印刷済") . "' else '' end ) as printed
                ,case when min(completed_status) = 1 then  '" . _g("完") . "' else
                  '" . _g("未(残") . " ' || (COALESCE(sum(order_detail_quantity),0) - COALESCE(sum(accepted_quantity),0)) || ')' end as completed
                ,case when min(cast(order_detail_completed as int))=1 then 0
                    else sum(coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) end as remained_qty
                ,case when min(cast(order_detail_completed as int))=1 then 0
                    else sum((coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) * item_price) end as remained_amount
                ,coalesce(sum(accepted_quantity),0) as accepted_quantity
                ,max(case when t_acc.odi is null then 0 else 1 end) as accepted_exist
                ,max(case when alarm_flag then 't' else 'f' end) as alarm_flag

                ,max(coalesce(order_detail.record_update_date, order_detail.record_create_date)) as gen_record_update_date
                ,max(coalesce(order_detail.record_updater, order_detail.record_creator)) as gen_record_updater

            ";
        }
        $this->selectQuery .= "
            from
                order_header
                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join (select customer_id as cid, customer_no as delivery_partner_no, customer_name as delivery_partner_name from customer_master) as t_delivery_partner on order_header.delivery_partner_id = t_delivery_partner.cid
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                left join worker_master on order_header.worker_id = worker_master.worker_id
                left join section_master on order_header.section_id = section_master.section_id
                left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id
                left join item_master on order_detail.item_id = item_master.item_id
                " . ($this->isDetailMode ?
                        "    inner join (select order_detail_id as oid,
                           (case when order_detail_completed then 1
                           else 0 end) as completed_status from order_detail) as t0
                           on order_detail.order_detail_id = t0.oid
                    " :
                        "    inner join (select order_header_id as oid,
                           min(case when order_detail_completed then 1
                           else 0 end) as completed_status from order_detail group by order_header_id) as t0
                           on order_header.order_header_id = t0.oid
                    "
                ) . "
                left join (select seiban as s2, received_number from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id) as t_rec on order_detail.seiban = t_rec.s2 and order_detail.seiban <> ''
                left join (select order_detail_id as odi from accepted group by order_detail_id) as t_acc on order_detail.order_detail_id = t_acc.odi
                " . (is_numeric(@$form['gen_search_parent_item_id']) ?
                        " inner join (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on order_detail.item_id = t_exp.exp_item_id " : "") . "
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
                and order_header.classification in (1)
                /* 所要量計算の結果取込モードの場合。取り込まれたデータのみを表示 */
                " . (isset($form['mrp']) && $orderHeaderIdList == "" ? " and 1=0" : "") . "
                " . (isset($form['mrp']) && $orderHeaderIdList != "" ? " and order_header.order_header_id in ({$orderHeaderIdList})" : "") . "
                " . (@$form['gen_search_completed_status'] == "false" ? " and completed_status = 0" : "") . "
             	" . ($form['gen_search_printed'] == '1' ? ' and not coalesce(order_printed_flag,false)' : '') . "
             	" . ($form['gen_search_printed'] == '2' ? ' and order_printed_flag' : '') . "

                " . ($this->isDetailMode ? "" : " group by order_header.order_header_id") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'order_id_for_user desc';
        if ($this->isDetailMode) {
            $this->orderbyDefault .= ",line_no";
            $this->customColumnTables = array(
                // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
                //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
                //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
                //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
                //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
                //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
                //          estimate_detail, received_detail, delivery_detail, order_detail
                array("customer_master", "", "", "order_header_id"),
                array("worker_master", "", "", "order_header_id"),
                array("section_master", "", "", "order_header_id"),
                array("item_master", "", "", "order_detail_id"),
            );        
        }
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("注文書");
        $form['gen_importMsg_noEscape'] = _g("※フォーマットは次のとおりです。") . "<br>" .
                _g("オーダー番号(新規の場合は空欄), (注文書番号), 発注日, 発注先コード, 発送先コード, 担当者コード, 部門コード, 注文備考, 製番, 品目コード, 発注単価, 発注数, 単位, 手配単位倍数, 注文納期, 注文明細備考") . "<br><br>" .
                _g("※新規登録の場合は、オーダー番号欄を空欄にしてください。") . "<br>" .
                _g("（オーダー番号を指定して新規登録することはできません。）") . "<br>" .
                _g("上書きの場合は、オーダー番号欄を入力してください。また、登録前に下の「上書き許可」をオンにしてください。") . "<br><br>" .
                _g("※注文書番号は指定する必要はありません。指定しても無視されます。") . "<br><br>" .
                _g("※発注先コードを省略すると、品目の標準手配先が発注先として自動的に設定されます。") . "<br>" .
                _g("※1行ごとに別個の注文としてインポートされます。") . "<br>" .
                _g("複数の明細行を持つデータを作成することはできません。");
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（オーダー番号が既存の場合はレコードを上書きする）");

        // 通知メール（成功時のみ）
        $form['gen_csvAlertMail_id'] = 'partner_order_new';   // Master_AlertMail_Edit冒頭を参照
        $form['gen_csvAlertMail_title'] = _g("注文書登録");
        $form['gen_csvAlertMail_body'] = _g("注文書データがCSVインポートされました。");    // インポート件数等が自動的に付加される

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
            ),
            array(
                'label' => _g('発注日'),
                'field' => 'order_date',
            ),
            array(
                'label' => _g('発注先コード'),
                'field' => 'partner_no',
                'exportField' => 'customer_no',
            ),
            array(
                'label' => _g('発送先コード'),
                'field' => 'delivery_partner_no',
            ),
            array(
                'label' => _g('担当者コード'),
                'field' => 'worker_code',
            ),
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'remarks_header',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('発注単価'),
                'field' => 'item_price',
                'exportField' => 'item_price_for_csv',
            ),
            array(
                'label' => _g('発注数'),
                'field' => 'order_detail_quantity',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'order_measure',
            ),
            array(
                'label' => _g('手配単位倍数'),
                'field' => 'multiple_of_order_measure',
            ),
            array(
                'label' => _g('注文納期'),
                'field' => 'order_detail_dead_line',
            ),
            array(
                'label' => _g('注文明細備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("注文登録");
        $form['gen_menuAction'] = "Menu_Partner";
        // ここはあえて「&gen_restore_search_condition=true」が必要。
        // MRP取込モードで絞り込み条件検索したとき、取込モードが解除されてしまうのを避けるため。
        $form['gen_listAction'] = "Partner_Order_List&gen_restore_search_condition=true";
        $form['gen_editAction'] = "Partner_Order_Edit";
        $form['gen_idField'] = ($this->isDetailMode ? 'order_detail_id' : 'order_header_id');
        $form['gen_idFieldForUpdateFile'] = "order_header.order_header_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("注文");

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("注文書 印刷"),
                'link' => "javascript:gen.list.printReport('Partner_Order_Report" . ($this->isDetailMode ? "&detail=true" : "") . "','check')",
                'reportEdit' => 'Partner_Order_Report'
            ),
        );

        // 簡易受入用の処理。 簡易受入で遷移するEntryクラスでgen_page_request_idが必要
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;

        $form['gen_javascript_noEscape'] = "
            function doAccept(orderDetailId, orderNo) {
               if (!confirm('" . _g("オーダー番号 %orderNo の注文の受入登録を行います。注文どおりの数量・注文納期で受入したものとして登録されます。※数量や注文納期を変更したい場合は、受入登録画面で登録を行ってください。\\登録を実行してもよろしいですか？") . "'.replace('%orderNo',orderNo))) return;
               var url = 'index.php?action=Partner_Accepted_Entry';
               url += '&easy_mode=true'
                + '&order_detail_id=' + orderDetailId
                + '&return_to_list=true'
                + '&gen_page_request_id={$reqId}';
               location.href = url;
            }

            function showOrderProgress(orderNo, from , to) {
               window.open('index.php?action=Progress_OrderProgress_List'
               + '&gen_searchConditionClear'
               + '&gen_search_order_no=' + orderNo
               + '&gen_search_date_from=' + from
               + '&gen_search_date_to=' + to
               , 'progress');
            }
            
            function showItemMaster(itemId) {
                gen.modal.open('index.php?action=Master_Item_Edit&item_id=' + itemId);
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[completed]'=='" . _g("完") . "'", // 完了行の色付け
            "#aee7fa" => "'[accepted_quantity]'>0", // 一部完了行の色付け
            "#f9bdbd" => "'[alarm_flag]'=='t'", // アラーム（間に合わない品目）の色付け
        );
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("受入完了")),
            "aee7fa" => array(_g("ブルー"), _g("一部受入済み")),
            "f9bdbd" => array(_g("ピンク"), _g("所要量計算でLTと休業日を無視して注文納期調整したオーダー")),
        );

        $form['gen_dataMessage_noEscape'] = "";
        $form['gen_message_noEscape'] = "";
        if (isset($form['mrp'])) {
            $form['gen_message_noEscape'] .=
                    _g("今回の所要量計算により作成されたオーダーだけが表示されています。") . "<BR>" .
                    "<a href=\"index.php?action=Manufacturing_Mrp_List\">" . _g("所要量計算の結果に戻る") . "</a>";
        }
        
        $form['gen_isClickableTable'] = "true";

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
                'width' => 42,
                'deleteAction' => 'Partner_Order_BulkDelete' . ($this->isDetailMode ? "&detail=true" : ""),
                'beforeAction' => 'Partner_Order_AjaxPrintedCheck', // 印刷済チェック
                'beforeDetail' => ($this->isDetailMode ? 'true' : 'false'), // 印刷済チェック用
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " and [accepted_exist] == 0",
            ),
            array(
                'label' => _g("印刷"),
                'name' => 'check',
                'type' => 'checkbox',
                'sameCellJoin' => true,
                'parentColumn' => 'order_id_for_user',
            ),
            array(
                'label' => _g('印刷済'),
                'width' => '50',
                'align' => 'center',
                'field' => 'printed',
                'cellId' => 'check_[id]_printed', // 印刷時書き換え用
                'sameCellJoin' => true,
                'parentColumn' => 'order_id_for_user',
                'helpText_noEscape' => _g("印刷（発行）したデータは「印刷済」と表示されます。") . "<br>" . _g("未印刷であっても、データとしては確定扱いです（正式なオーダーとして所要量計算等で考慮されます）。") . "<br>" . _g("印刷済データを修正すると未印刷に戻ります。"),
            ),
            array(
                'label' => _g('注文書番号'),
                'width' => '80',
                'align' => 'center',
                'field' => 'order_id_for_user',
                'sameCellJoin' => true,
            ),
            //  ******* 以下、明細モードのみ ******
            array(
                'label' => _g('行'),
                'field' => 'line_no',
                'width' => '50',
                'align' => 'center',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'width' => '80',
                'align' => 'center',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('ｸｲｯｸ受入'),
                'width' => '63',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/arrow-curve-000-left.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:doAccept([order_detail_id], '[urlencode:order_no]')",
                'showCondition' => "([accepted_exist] == 0 && " . ($form['gen_readonly'] ? "false" : "true") . ")",
                'helpText_noEscape' => _g("リンクをクリックすると、その注文に対する受入の登録を行います。注文書通りの数量・注文納期で受入" .
                        "したものとして登録されます。注文納期や数量を変更したい場合は受入登録画面で行ってください。"),
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('進捗'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/chart-up.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:showOrderProgress('[urlencode:order_no]','[order_date]','[order_detail_dead_line]')",
                'visible' => $this->isDetailMode,
            ),
        );

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $form['gen_columnArray'] = array(
            //  ******* 以下、ヘッダモードのみ ******
            array(
                'label' => _g('明細行数'),
                'field' => 'detail_count',
                'width' => '60',
                'type' => 'numeric',
                'visible' => !$this->isDetailMode,
            ),
            //  ******* 以下、明細モードのみ ******
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '80',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、なおかつ所要量計算結果画面から発行されたオーダーである場合のみ表示されます。MRP品目は受注とオーダーの結びつきがないため、受注番号を表示できません。'),
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            //  ******* ここまで ******

            array(
                'label' => _g('発注日'),
                'field' => 'order_date',
                'type' => 'date',
                'width' => '90',
                'align' => 'center',
            ),
            //  ******* 以下、明細モードのみ ******
            array(
                'label' => _g('注文納期'),
                'field' => 'order_detail_dead_line',
                'type' => 'date',
                'visible' => $this->isDetailMode,
            ),
            //  ******* ここまで ******
            array(
                'label' => _g('発注先コード'),
                'field' => 'customer_no',
                'width' => '120',
                'hide' => true,
            ),
            array(
                'label' => _g('発注先名'),
                'field' => 'customer_name',
                'width' => '200',
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
                'label' => _g('発送先コード'),
                'field' => 'delivery_partner_no',
                'width' => '120',
                'hide' => true,
            ),
            array(
                'label' => _g('発送先名'),
                'field' => 'delivery_partner_name',
                'hide' => true,
            ),
            array(
                'label' => _g('受入状況'),
                'field' => 'completed',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('発注残'),
                'field' => 'remained_qty',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('発注残額'),
                'field' => 'remained_amount',
                'type' => 'numeric',
                'hide' => true,
            ),
            //  ******* 以下、明細モードのみ ******
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('品目マスタ'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/application-form.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:showItemMaster('[item_id]')",
                'hide' => true,
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('メーカー型番'),
                'field' => 'item_sub_code',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'item_remarks_1',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'item_remarks_2',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'item_remarks_3',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'item_remarks_4',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'item_remarks_5',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('数量'),
                'field' => 'order_detail_quantity',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => sprintf(_g('発注単価(%s)'), $keyCurrency),
                'field' => 'item_price',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => sprintf(_g('発注金額(%s)'), $keyCurrency),
                'field' => 'amount',
                'type' => 'numeric',
                'width' => '100',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('注文書数量'),
                'field' => 'show_quantity',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
            ),
            array(
                'label' => _g('発注単位'),
                'field' => 'order_measure',
                'width' => '60',
                'align' => 'left',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => sprintf(_g('注文書単価(%s)'), $keyCurrency),
                'field' => 'show_price',
                'type' => 'numeric',
                'width' => '100',
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
                'label' => _g('発注単価(外貨)'),
                'field' => 'foreign_currency_item_price',
                'type' => 'numeric',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('注文書単価(外貨)'),
                'field' => 'foreign_currency_show_price',
                'type' => 'numeric',
                'width' => '100',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('発注金額(外貨)'),
                'field' => 'foreign_currency_order_amount',
                'type' => 'numeric',
                'width' => '100',
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            array(
                'label' => _g('注文明細備考'),
                'field' => 'remarks',
                'width' => '250', // なるべくのばす
                'visible' => $this->isDetailMode,
                'hide' => true,
            ),
            // ****** 以下、ヘッダ・明細共通 ******
            array(
                'label' => _g('担当者コード'),
                'field' => 'worker_code',
                'width' => '70',
                'sameCellJoin' => true,
                'parentColumn' => 'order_id_for_user',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者名'),
                'field' => 'worker_name',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'order_id_for_user',
                'hide' => true,
            ),
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
                'width' => '70',
                'sameCellJoin' => true,
                'parentColumn' => 'order_id_for_user',
                'hide' => true,
            ),
            array(
                'label' => _g('部門名'),
                'field' => 'section_name',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'order_id_for_user',
                'hide' => true,
            ),
            array(
                'label' => _g('帳票テンプレート'),
                'field' => 'template_partner_order',
                'sameCellJoin' => true,
                'parentColumn' => 'order_id_for_user',
                'helpText_noEscape' => _g("取引先マスタの [帳票(注文書)] です。指定されている場合はそのテンプレートが使用されます。未指定の場合、テンプレート設定画面で選択されたテンプレートが使用されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'remarks_header',
                'width' => '250', // なるべくのばす
                'sameCellJoin' => true,
                'parentColumn' => 'order_id_for_user',
                'hide' => true,
            ),
        );
    }

}
