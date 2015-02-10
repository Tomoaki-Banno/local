<?php

class Manufacturing_Received_List extends Base_ListBase
{
    
    function setSearchCondition(&$form)
    {
        global $gen_db;
 
        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label'=>_g('受注番号'),
                'field'=>'received_number',
            ),
            array(
                'label'=>_g('客先注番'),
                'field'=>'customer_received_number',
                'hide'=>true,
            ),
            array(
                'label'=>_g('見積番号'),
                'field'=>'estimate_number',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注製番'),
                'field'=>'seiban',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注確定度'),
                'type'=>'select',
                'field'=>'guarantee_grade',
                'options'=>array(''=>_g("(すべて)"), '0'=>_g('確定'), '1'=>_g('予約')),
                'hide'=>true,
            ),
            array(
                'label'=>_g('得意先コード/名'),
                'field'=>'customer_master___customer_no',
                'field2'=>'customer_master___customer_name',
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 'customer_master___customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
                'hide' => true,
            ),
             array(
                'label'=>_g('品目コード/名'),
                'field'=>'item_code',
                'field2'=>'item_name',
            ),
            array(
                'label'=>_g('品目グループ'),
                'field'=>'item_group_id',
                'type'=>'select',
                'options'=>$option_item_group,
                'hide'=>true,
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
                'label'=>_g('発送先コード/名'),
                'field'=>'t_delivery_customer___customer_no',
                'field2'=>'t_delivery_customer___customer_name',
                'hide'=>true,
            ),
            array(
                'label'=>_g('担当者コード/名'),
                'field'=>'worker_code',
                'field2'=>'worker_name',
                'hide'=>true,
            ),
            array(
                'label'=>_g('部門コード/名'),
                'field'=>'section_code',
                'field2'=>'section_name',
                'hide'=>true,
            ),
            array(
                'label' => _g('合計金額'),
                'type' => 'numFromTo',
                'field' => 'amount_header',
                'hide' => true,
                'helpText_noEscape' => _g('受注番号ごとの「受注額」の合計です。')
            ),
            array(
                'label'=>_g('受注備考1'),
                'field'=>'remarks_header',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注備考2'),
                'field'=>'remarks_header_2',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注備考3'),
                'field'=>'remarks_header_3',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注明細備考1'),
                'field'=>'received_detail___remarks',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注明細備考2'),
                'field'=>'received_detail___remarks_2',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注日'),
                'type'=>'dateFromTo',
                'field'=>'received_date',
                'size'=>'80',
                'rowSpan'=>2,
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注納期'),
                'type'=>'dateFromTo',
                'field'=>'dead_line',
                'size'=>'80',
                'rowSpan'=>2,
                'hide'=>true,
            ),
            array(
                'label'=>_g('完了分の表示'),
                'type'=>'select',
                'field'=>'completed_status',
                'options'=>Gen_Option::getTrueOrFalse('search-show'),
                'nosql'=>'true',
                'default'=>'false',
            ),
            array(
                'label'=>_g('印刷状況'),
                'type'=>'select',
                'field'=>'printed',
                'options'=>Gen_Option::getPrinted('search'),
                'nosql'=>'true',
                'default'=>'0',
            ),
            array(
                'label'=>_g('明細の表示'),
                'type'=>'select',
                'field'=>'show_detail',
                'options'=>Gen_Option::getTrueOrFalse('search-show'),
                'nosql'=>'true',
                'default'=>'true',
                'onChange_noEscape' =>'onShowDetailChange()',
            ),
            array(
                'label' => _g('理論在庫数の表示'),
                'type' => 'select',
                'field' => 'show_stock',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
                'nosql' => 'true',
                'default' => 'false',
                'hide' => true,
                'helpText_noEscape' => _g('“表示する”を選択すると、リスト表示の項目に「理論在庫数」を含めている場合、現在の理論在庫数を計算して表示します。')
                . _g('「理論在庫数」を表示する必要がなければ、“表示しない”を選択した方が表示速度が速くなります。'),
            ),
            array(
                'label'=>_g('在庫引当状態'),
                'type'=>'select',
                'field'=>'seiban_change_status',
                'options'=>array(""=>_g("(すべて)"), "1"=>_g("未引当"), "2"=>_g("引当済")),
                'nosql'=>'true',
            ),
            array(
                'label'=>_g('最終更新日時'),
                'type'=>'dateTimeFromTo',
                'field'=>'gen_record_update_date',
                'size'=>'120',
                'rowSpan'=>2,
                'hide'=>true,
                'helpText_noEscape'=>_g('日本時間で表示されます。'),
            ),
        );
        // 表示条件クリアの指定がされていたときは、完了データの表示を「する」にしておく。
        // 進捗画面のリンク等からレコード指定でこの画面を開いたときのため。
        if (isset($form['gen_searchConditionClear'])) {
            $form['gen_search_completed_status'] = 'true';
        }
        
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("日次受注額（当月）") => self::_getPreset("5", "gen_all", "received_date_day", ""),
                _g("月次受注額（今年）") => self::_getPreset("7", "gen_all", "received_date_month", ""),
                _g("受注額 前年対比") => self::_getPreset("0", "received_date_month", "received_date_year", ""),
                _g("品目別 受注残数") => self::_getPreset("0", "dead_line_day", "item_name", "", "remained_qty", "sum", "false"),
                _g("品目別 受注残額") => self::_getPreset("0", "dead_line_day", "item_name", "", "remained_amount", "sum", "false"),
                _g("得意先別 受注残数") => self::_getPreset("0", "dead_line_day", "customer_name", "", "remained_qty", "sum", "false"),
                _g("得意先別 受注残額") => self::_getPreset("0", "dead_line_day", "customer_name", "", "remained_amount", "sum", "false"),
                _g("得意先受注額ランキング（今年）") => self::_getPreset("7", "gen_all", "customer_name", "order by field1 desc"),
                _g("得意先グループ1受注額ランキング（今年）") => self::_getPreset("7", "gen_all", "customer_group_name_1", "order by field1 desc"),
                _g("品目受注額ランキング（今年）") => self::_getPreset("7", "gen_all", "item_name", "order by field1 desc"),
                _g("担当者受注額ランキング（今年）") => self::_getPreset("7", "gen_all", "worker_name", "order by field1 desc"),
                _g("部門受注額ランキング（今年）") => self::_getPreset("7", "gen_all", "section_name", "order by field1 desc"),
                _g("得意先 - 品目（今年）") => self::_getPreset("7", "customer_name", "item_name", ""),
                _g("得意先別 月次受注額（今年）") => self::_getPreset("7", "received_date_month", "customer_name", ""),
                _g("得意先グループ1月次受注額（今年）") => self::_getPreset("7", "received_date_month", "customer_group_name_1", ""),
                _g("品目別 月次受注額（今年）") => self::_getPreset("7", "received_date_month", "item_name", ""),
                _g("品目別 月次受注数量（今年）") => self::_getPreset("7", "received_date_month", "item_name", "", "received_quantity"),
                _g("担当者別 月次受注額（今年）") => self::_getPreset("7", "received_date_month", "worker_name", ""),
                _g("部門別 月次受注額（今年）") => self::_getPreset("7", "received_date_month", "section_name", ""),
                _g("データ入力件数（今年）") => self::_getPreset("7", "received_date_month", "gen_record_updater", "", "gen_record_updater", "count"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby, $value = "amount", $method = "sum", $completedStatus = "true")
    {
        return
            array(
                "data" => array(
                    array("f" => "received_date", "dp" => $datePattern),
                    array("f" => "completed_status", "v" => $completedStatus),
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
        $this->isDetailMode = (@$form['gen_search_show_detail'] != "false" || isset($form['gen_csvMode']));
    }

    function setQueryParam(&$form)
    {
        $whereAdd = "";
        if ($form['gen_search_completed_status'] == "false") {
            $whereAdd = " and (not(delivery_completed) or delivery_completed is null)"; // 「含める」（true）のときはtrueもfalseも
        }
        if (@$form['gen_search_seiban_change_status']=="1") {    // 未引当のみ
            $whereAdd .= " and received_detail.received_quantity > coalesce(seiban_change_qty,0)";
        }
        if (@$form['gen_search_seiban_change_status']=="2") {    // 引当済のみ
            $whereAdd .= " and received_detail.received_quantity <= coalesce(seiban_change_qty,0)";
        }

        if ($this->isDetailMode && $form['gen_search_show_stock'] == "true") {
            // temp_stockテーブルにデータを取得
            Logic_Stock::createTempStockTable(
                    null        // $stockDate                    この日以前の最終棚卸日から、この日までがデータ取得の対象になる
                    , null      // $itemId                       指定：　その品目のみ、　null：　全品目分を個別に取得
                    , null      // $seiban                       指定：　その製番のみ、　null：　全製番分を個別に取得、  'sum'：　全製番分を合計
                    , "sum"     // $locationId                   指定：　そのロケのみ、　null：　全ロケ分を個別に取得、　'sum'：　全ロケ分を合計
                    , "sum"     // $isGetAvailable               有効在庫および未確定データ（オーダー残、受注計画残、引当、使用予定）を取得するか。falseだと速い
                    , false     // $isIncludePartnerStock        Pロケ分を含めるかどうか
                    , false     // $isUsePlanAllMinus            use_planを将来分まで差し引くか（MRPとStocklistではtrue、HistoryとFlowではfalse）
                    , false     // $isExceptStockDateInventory   stockDate当日の棚卸を計算から除外するかどうか。棚卸差数の計算用のモード
            );
        }

        $this->selectQuery = "
             select
                received_header.received_header_id
        ";
        if (!$this->isDetailMode) {
            // ヘッダモード
            $this->selectQuery .= "
                ,count(received_detail.*) as detail_count
                ,max(received_number) as received_number
                ,max(customer_received_number) as customer_received_number
                ,max(t_estimate.estimate_number) as estimate_number
                ,max(customer_master.customer_no) as customer_no
                ,max(customer_master.customer_name) as customer_name
                ,max(t_delivery_customer.customer_no) as delivery_customer_no
                ,max(t_delivery_customer.customer_name) as delivery_customer_name
                ,max(received_date) as received_date
                ,max(worker_code) as worker_code
                ,max(worker_name) as worker_name
                ,max(section_code) as section_code
                ,max(section_name) as section_name
                ,max(remarks_header) as remarks_header
                ,max(remarks_header_2) as remarks_header_2
                ,max(remarks_header_3) as remarks_header_3
                ,max(amount_header) as amount_header

                ,sum(received_quantity * product_price) as amount
                ,sum(received_quantity * sales_base_cost) as sales_base_cost
                ,sum(received_quantity * (product_price - sales_base_cost)) as sales_gross_margin
                ,case when min(cast(delivery_completed as int))=1 then '" . _g("完") . "' else
                    '" . _g("未(残") . " ' || (sum(coalesce(received_quantity,0) - coalesce(delivery_quantity,0))) || ')' end as delivery
                ,case when min(cast(delivery_completed as int))=1 then 'false' else 'true' end as is_delete
                ,coalesce(sum(delivery_quantity),0) as delivery_quantity
                ,case when min(cast(delivery_completed as int))=1 then 0
                    else sum(coalesce(received_quantity,0) - coalesce(delivery_quantity,0)) end as remained_qty
                ,case when min(cast(delivery_completed as int))=1 then 0
                    else sum((coalesce(received_quantity,0) - coalesce(delivery_quantity,0)) * product_price) end as remained_amount
                ,case when min(cast(delivery_completed as int))=1 and min(coalesce(bill_header_id,0)) <> 0 then 'done' else '' end as bill
                ,max(coalesce(guarantee_grade,0)) as guarantee_grade
                ,case when max(coalesce(guarantee_grade,0))=0 then '" . _g("確定") . "' else '" . _g("予約") . "' end as guarantee_grade_show

                ,max(t_customer_group_1.customer_group_code) as customer_group_code_1
                ,max(t_customer_group_1.customer_group_name) as customer_group_name_1
                ,max(t_customer_group_2.customer_group_code) as customer_group_code_2
                ,max(t_customer_group_2.customer_group_name) as customer_group_name_2
                ,max(t_customer_group_3.customer_group_code) as customer_group_code_3
                ,max(t_customer_group_3.customer_group_name) as customer_group_name_3

                ,max(customer_master.remarks) as customer_remarks_1
                ,max(customer_master.remarks_2) as customer_remarks_2
                ,max(customer_master.remarks_3) as customer_remarks_3
                ,max(customer_master.remarks_4) as customer_remarks_4
                ,max(customer_master.remarks_5) as customer_remarks_5

                /* foreign_currency */
                ,sum(received_quantity * foreign_currency_product_price) as foreign_currency_amount
                ,sum(received_quantity * foreign_currency_sales_base_cost) as foreign_currency_sales_base_cost
                ,sum(received_quantity * (product_price - foreign_currency_sales_base_cost)) as foreign_currency_sales_gross_margin

                /*  最終更新情報（検索対応）*/
                ,max(gen_record_update_date) as gen_record_update_date
                ,max(gen_record_updater) as gen_record_updater
            ";
        } else {
            // 明細モード
            $this->selectQuery .= "
                ,received_number
                ,customer_received_number
                ,t_estimate.estimate_number
                ,line_no
                ,seiban
                ,customer_master.customer_no
                ,t_delivery_customer.customer_no as delivery_customer_no
                ,customer_master.customer_name
                ,t_delivery_customer.customer_name as delivery_customer_name
                ,received_date
                ,worker_code
                ,worker_name
                ,section_code
                ,section_name
                ,remarks_header
                ,remarks_header_2
                ,remarks_header_3

                ,received_detail.received_detail_id
                ,received_detail.item_id
                ,item_code
                ,item_name
                ,maker_name
                ,spec
                ,rack_no
                ,order_class
                ,received_quantity
                ,measure
                ,product_price
                ,dead_line
                ,coalesce(guarantee_grade,0) as guarantee_grade
                ,case when coalesce(guarantee_grade,0)=0 then '" . _g("確定") . "' else '" . _g("予約") . "' end as guarantee_grade_show
                ,received_quantity * product_price as amount
                ,received_quantity * sales_base_cost as sales_base_cost
                ,received_quantity * (product_price - sales_base_cost) as sales_gross_margin
                ,received_detail.remarks
                ,received_detail.remarks_2
                ,case when received_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed
                ,case when delivery_completed then '" . _g("完") . "' else
                    '" . _g("未(残") . " ' || (coalesce(received_quantity,0) - coalesce(delivery_quantity,0)) || ')' end as delivery
                ,case when delivery_completed then 'false' else 'true' end as is_delete
                ,case when delivery_completed then 0
                    else coalesce(received_quantity,0) - coalesce(delivery_quantity,0) end as remained_qty
                ,case when delivery_completed then 0
                    else (coalesce(received_quantity,0) - coalesce(delivery_quantity,0)) * product_price end as remained_amount
                ,use_plan_quantity
                ,coalesce(delivery_quantity,0) as delivery_quantity
                ,case when delivery_completed and coalesce(bill_header_id,0) <> 0 then 'done' else '' end as bill                

                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3

                ,lot_no
                ,case when item_master.order_class <> 2 then '' else case when received_detail.received_quantity > coalesce(seiban_change_qty,0) then '" . _g("未") . "' else '" . _g("済") . "' end end as lot_status
                
                ,case when exists (select 1 from bom_master where bom_master.item_id = received_detail.item_id) then '" . _g("あり") . "' else '' end as in_bom

                ,item_master.comment as item_remarks_1
                ,item_master.comment_2 as item_remarks_2
                ,item_master.comment_3 as item_remarks_3
                ,item_master.comment_4 as item_remarks_4
                ,item_master.comment_5 as item_remarks_5
                ,customer_master.remarks as customer_remarks_1
                ,customer_master.remarks_2 as customer_remarks_2
                ,customer_master.remarks_3 as customer_remarks_3
                ,customer_master.remarks_4 as customer_remarks_4
                ,customer_master.remarks_5 as customer_remarks_5

                /* foreign_currency */
                ,currency_name
                ,foreign_currency_rate
                ,foreign_currency_product_price
                ,foreign_currency_product_price * received_quantity as foreign_currency_amount
                ,received_quantity * foreign_currency_sales_base_cost as foreign_currency_sales_base_cost
                ,received_quantity * (foreign_currency_product_price - foreign_currency_sales_base_cost) as foreign_currency_sales_gross_margin

                /* for csv */
                ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end as product_price_for_csv
                ,case when foreign_currency_id is null then sales_base_cost else foreign_currency_sales_base_cost end as sales_base_cost_for_csv
                ,null as delivery_regist_for_csv

                /* for stock */
                " . ($form['gen_search_show_stock'] == "true" ? "
                    ,logical_stock_quantity
                " : "
                    ,0 as logical_stock_quantity
                ") . "

                /* 最終更新情報（検索対応）*/
                ,gen_record_update_date
                ,gen_record_updater
            ";
        }
        $this->selectQuery .= "
            from
                item_master
                inner join received_detail on item_master.item_id = received_detail.item_id
                inner join received_header on received_header.received_header_id = received_detail.received_header_id
                left join (select received_header_id, sum(received_quantity * product_price) as amount_header from received_detail
                    group by received_header_id) as t_amount on received_header.received_header_id = t_amount.received_header_id
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join customer_master as t_delivery_customer on received_header.delivery_customer_id = t_delivery_customer.customer_id
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join section_master on received_header.section_id = section_master.section_id
                left join currency_master on received_detail.foreign_currency_id = currency_master.currency_id
                left join (select estimate_header_id as ehid,estimate_number
                    from estimate_header) as t_estimate on received_header.estimate_header_id = t_estimate.ehid

                /* 出庫ロット（製番引当） */
                left join
                (
                    SELECT
                        seiban_change.item_id
                        ,seiban_change.dist_seiban
                        ,string_agg(t_ach_acc.lot_no || ' (' || cast(seiban_change.quantity as text) || ')', ',') as lot_no
                        ,sum(seiban_change.quantity) as seiban_change_qty
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

                /* 引当数 */
                left join (
                    select
                        use_plan.received_detail_id,
                        coalesce(SUM(use_plan.quantity),0)+coalesce(MAX(T0.delivery_qty),0) as use_plan_quantity
                    from
                        use_plan
                        left join
                            (select
                                received_detail_id,
                                SUM(free_stock_quantity) as delivery_qty
                            from
                                delivery_detail
                            group by
                                received_detail_id)
                            as T0 on use_plan.received_detail_id=T0.received_detail_id
                    where
                        use_plan.received_detail_id is not null
                        AND use_plan.quantity <> 0
                        /* 引当の完了調整レコードを除く。詳しくは Logic_Reserve::getReserveQuantity 参照 */
                        /* AND (use_plan.completed_adjust_delivery_id is null) 仕様変更に伴い完了調整レコードも含める */
                    group by
                        use_plan.received_detail_id
                ) as T1 on received_detail.received_detail_id = T1.received_detail_id

                /* 納品済み数 */
                left join (
                    select
                        received_detail_id
                        ,SUM(delivery_quantity) as delivery_quantity
                        /* 請求済み判断用 */
                        ,min(delivery_header.bill_header_id) as bill_header_id
                    from
                        delivery_detail
                        inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                    group by
                        received_detail_id
                ) as T2 on received_detail.received_detail_id = T2.received_detail_id

                /* 最終更新情報用（検索対応） */
                left join (
                    select
                        received_detail_id
                        ,coalesce(received_detail.record_update_date, received_detail.record_create_date) as gen_record_update_date
                        ,coalesce(received_detail.record_updater, received_detail.record_creator) as gen_record_updater
                    from
                        received_detail
                ) as T4 on received_detail.received_detail_id = T4.received_detail_id

                /* 理論在庫用 */
                " . ($this->isDetailMode && $form['gen_search_show_stock'] == "true" ? "
                    left join (select item_id, logical_stock_quantity from temp_stock as t_stock
                        where exists (select item_id from item_master where order_class = 1 and t_stock.item_id = item_master.item_id)
                        ) as T5 on item_master.item_id = T5.item_id
                " : "") . "
            [Where]
                {$whereAdd}
             	" . ($form['gen_search_printed']=='1' ? ' and not coalesce(received_printed_flag,false)' : '') . "
             	" . ($form['gen_search_printed']=='2' ? ' and received_printed_flag' : '') . "
            ";
            if (!$this->isDetailMode) {
                $this->selectQuery .= "
                group by
                    received_header.received_header_id
            ";
        }
        $this->selectQuery .= "
            [Orderby]
        ";
        $this->orderbyDefault = "received_number desc" . ($this->isDetailMode ? ", line_no" : "");
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("customer_master", "", "", "received_header_id"),
            array("worker_master", "", "", "received_header_id"),
            array("section_master", "", "", "received_header_id"),
        );        
        if ($this->isDetailMode) {
            $this->customColumnTables[] = array("item_master", "", "", "received_detail_id");
        }
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("受注");
        $form['gen_importMsg_noEscape'] =
            _g("※データは新規登録されます。（既存データの上書きはできません）") . "<br>" .
            _g("　受注番号が既存の場合はエラーになります。"). "<br><br>" .
            _g("※受注番号が連続したデータは複数の明細行としてインポートされます。") . "<br>" .
            _g("　ファイル内で同一の受注番号が連続しない場合は重複エラーになります。");
        $form['gen_allowUpdateCheck'] = false;  // 納品済み品目の上書き、品目変更などの問題があるので、上書きは不許可とした

        // 通知メール（成功時のみ）
        $form['gen_csvAlertMail_id'] = 'manufacturing_received_new';   // Master_AlertMail_Edit冒頭を参照
        $form['gen_csvAlertMail_title'] = _g("受注登録");
        $form['gen_csvAlertMail_body'] = _g("受注データがCSVインポートされました。");    // インポート件数等が自動的に付加される

        $form['gen_csvArray'] = array(
                array(
                    'label'=>_g('受注番号'),
                    'field' => 'received_number',
                    'header' => true,             // これを指定すると、連続するレコードでこのカラムの値が同じ場合に、同一header内の明細データとして扱われる
                    'table' => 'received_header', // headerフラグが指定されているとき用。親テーブル
                    'id' => 'received_header_id', // headerフラグが指定されているとき用。親ID
                    'unique' => true,             // これを指定すると、インポート時にCSVファイル内での重複がチェックされる（上記のケースを除く）
                ),
                array(
                    'label' => _g('客先注番'),
                    'field' => 'customer_received_number',
                ),
                array(
                    'label' => _g('得意先コード'),
                    'field' => 'customer_no',
                ),
                array(
                    'label' => _g('発送先コード'),
                    'field' => 'delivery_customer_no',
                ),
                array(
                    'label' => _g('品目コード'),
                    'field' => 'item_code',
                ),
                array(
                    'label' => _g('受注日'),
                    'field' => 'received_date',
                    'isDate' => true,   // yyyymmdd、yy/mm/dd, yyyy-mm-dd などの形式も受け付ける
                ),
                array(
                    'label' => _g('数量'),
                    'field' => 'received_quantity',
                ),
                array(
                    'label' => _g('受注単価'),
                    'field' => 'product_price',
                    'exportField' => 'product_price_for_csv',
                ),
                array(
                    'label' => _g('販売原単価'),
                    'field' => 'sales_base_cost',
                    'exportField' => 'sales_base_cost_for_csv',
                ),
                array(
                    'label' => _g('受注納期'),
                    'field' => 'dead_line',
                    'isDate' => true,   // yyyymmdd、yy/mm/dd, yyyy-mm-dd などの形式も受け付ける
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
                    'label' => _g('受注確定度'),
                    'addLabel' => _g('(0[確定]／1[予約])'),
                    'field' => 'guarantee_grade',
                ),
                array(
                    'label' => _g('受注明細備考1'),
                    'field' => 'remarks',
                ),
                array(
                    'label' => _g('受注明細備考2'),
                    'field' => 'remarks_2',
                ),
                array(
                    'label' => _g('受注備考1'),
                    'field' => 'remarks_header',
                ),
                array(
                    'label' => _g('受注備考2'),
                    'field' => 'remarks_header_2',
                ),
                array(
                    'label' => _g('受注備考3'),
                    'field' => 'remarks_header_3',
                ),
                array(
                    'label' => _g('同時に納品を登録'),
                    'addLabel' => _g("(インポート専用。「1」なら納品登録する)"),
                    'field' => 'delivery_regist',
                    'exportField' => 'delivery_regist_for_csv',
                ),
            );
    }

    function setViewParam(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("受注登録");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Received_List";
        $form['gen_editAction'] = "Manufacturing_Received_Edit";
        $form['gen_deleteAction'] = "Manufacturing_Received_Delete";
        $form['gen_idField'] = ($this->isDetailMode ? "received_detail_id" : "received_header_id");
        $form['gen_idFieldForUpdateFile'] = "received_header.received_header_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_onLoad_noEscape'] = "onLoad()";
        $form['gen_pageHelp'] = _g("受注");

        $form['gen_isClickableTable'] = "true";

        //  モードにより動的に列を切り替える場合、モードごとに列情報（列順、列幅、ソートなど）を別々に保持できるよう、次の設定が必要。
        $form['gen_columnMode'] = ($this->isDetailMode ? "detail" : "list");

        $form['gen_checkAndGoLinkArray'] = array(
            array(
                'id' => 'seibanExpandButton',
                'value' => _g('製番展開'),
                'onClick' => "javascript:seibanExpand();",
            ),
        );

        $form['gen_goLinkArray'] = array(
            array(
                'id' => 'seibanExpandButton',
                'value' => _g('一括ロット引当'),
                'onClick' => "javascript:location.href='index.php?action=Manufacturing_Received_BulkLotEdit'",
            ),
        );

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("出荷指示書 印刷"),
                'link' => "javascript:gen.list.printReport('Manufacturing_Received_Report".($this->isDetailMode ? "&detail=true" : "")."','check')",
                'reportEdit' => 'Manufacturing_Received_Report'
            ),
        );
        
        // クイック納品用の処理。 遷移するEntryクラスでgen_page_request_idが必要
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;

        // リンク。
        // なるべく表示条件をクリアした状態で表示
        $form['gen_javascript_noEscape'] = "
            function goProgress(seiban, from, to) {
                window.open('index.php?action=Progress_SeibanProgress_List&gen_searchConditionClear&gen_search_seiban=' + seiban + '&gen_search_date_from=' + from + '&gen_search_date_to=' + to + '&gen_search_mode=detail','progress');
            }

            function goBaseCost(seiban, date) {
                window.open('index.php?action=Manufacturing_BaseCost_List&gen_searchConditionClear&gen_search_seiban=' + seiban,'basecost');
            };

            function goEasyMrp(id) {
                window.open('index.php?action=Manufacturing_Received_EasyMrp&gen_searchConditionClear&gen_search_received_detail_id=' + id,'easymrp');
            };

            function lotEntry(seiban, status) {
                if (status=='" . _g("済") . "') {
                    window.open('index.php?action=Stock_SeibanChange_List&gen_search_dist_seiban='+seiban+'&gen_search_change_date_from=&gen_search_change_date_to=');
                } else {
                    location.href = 'index.php?action=Manufacturing_Received_LotEdit&gen_search_seiban='+seiban;
                }
            }
        ";

        // イベント処理
        $form['gen_javascript_noEscape'] .= "
            function onLoad() {
               onShowDetailChange();
            }
            // 「明細の表示」変更イベント
            //  明細の表示「表示する」の時のみ、印刷状況を有効にする。
            //  印刷済列が明細表示の時のみ可視状態になるため。
            //  「表示しない」のときは印刷状況を「すべて」に固定する
            function onShowDetailChange() {
               var elm = $('#gen_search_show_detail');
               if (elm.val() == 'true') {    // 「表示する」のときは印刷状況有効
                   $('#gen_search_printed').attr('selectedIndex',0);
                   gen.ui.alterDisabled($('#gen_search_printed'), false);
               } else {
                   gen.pin.turnOff('Manufacturing_Received_List', 'gen_search_printed', '');
                   $('#gen_search_printed').val(0);
                   gen.ui.alterDisabled($('#gen_search_printed'), true);
               }
            }

            // 製番オーダー展開
            function seibanExpand() {
                var frm = gen.list.table.getCheckedPostSubmit('chk_expand');
                if (frm.count == 0) {
                    alert('" . _g("製番展開するデータを選択してください。") . "');
                } else {
                    var postUrl = 'index.php?action=Manufacturing_SeibanExpand_Edit';
                    frm.add('isFirst', 'true');
                    frm.submit(postUrl, null);
                }
            }
            
            function showItemMaster(itemId) {
                gen.modal.open('index.php?action=Master_Item_Edit&item_id=' + itemId);
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#999999" => "'[bill]'=='done'",                // 請求済み
            "#d7d7d7" => "'[delivery]'=='" . _g("完") . "'", // 納品済み
            "#aee7fa" => "'[delivery_quantity]'>0",         // 一部納品済み
            "#fae0a6" => "'[guarantee_grade]'=='1'",        // 予約
        );
        
        $form['gen_colorSample'] = array(
            "fae0a6" => array(_g("イエロー"), _g("予約")),
            "aee7fa" => array(_g("ブルー"), _g("一部納品済み")),
            "d7d7d7" => array(_g("シルバー"), _g("納品済み")),
            "999999" => array(_g("ブラック"), _g("請求済み")),
        );
        
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
                'deleteAction' => 'Manufacturing_Received_BulkDelete' . ($this->isDetailMode ? '&detail=true' : ''),
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " && ('[delivery_quantity]'=='0' || '[delivery_quantity]'=='') && '[is_delete]'=='true'",
            ),
            array(
                'label' => _g("印刷"),
                'name' => 'check',
                'type' => 'checkbox',
                // 09iでは確定受注かつ未納のレコードだけ印刷できた（出荷指示書としてはその仕様が妥当）。
                // 10iでは受注リストとしても使用できるよう、その縛りをはずした。
                //'showCondition' => "'[delivery]'!='" . _g("完") . "' and '[guarantee_grade]'=='0'",
            ),
        );

        if (!$this->isDetailMode) {
            // ヘッダモード
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '90',
                'align' => 'center',
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('客先注番'),
                'field' => 'customer_received_number',
                'width' => '90',
                'align' => 'center',
                'hide' => true,
            );

            $form['gen_columnArray'] = array(
                array(
                    'label' => _g('見積番号'),
                    'field' => 'estimate_number',
                    'width' => '110',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('得意先コード'),
                    'field' => 'customer_no',
                    'width' => '110',
                    'hide' => true,
                ),
                array(
                    'label' => _g('得意先名'),
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
                    'label' => _g('受注日'),
                    'field' => 'received_date',
                    'type' => 'date',
                ),
                array(
                    'label' => _g('明細行数'),
                    'field' => 'detail_count',
                    'width' => '60',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('合計金額'),
                    'field' => 'amount_header',
                    'type' => 'numeric',
                ),
                array(
                    'label' => _g('販売原価'),
                    'field' => 'sales_base_cost',
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
                    'label' => _g('納品'),
                    'field' => 'delivery',
                    'width' => '80',
                    'align' => 'center',
                    'hide' => true,
                ),
                // 表示内容としては「納品」と重なっているが、「納品」の「未（残100）」といった
                // 表示では数量の集計ができず不便、という意見がありこの欄をあらたに設けた。
                array(
                    'label' => _g('受注残'),
                    'field' => 'remained_qty',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注残額'),
                    'field' => 'remained_amount',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('合計金額(外貨)'),
                    'field' => 'foreign_currency_amount',
                    'type' => 'numeric',
                    'width' => '100',
                    'hide' => true,
                ),
                array(
                    'label' => _g('販売原価(外貨)'),
                    'field' => 'foreign_currency_sales_base_cost',
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
                    'label' => _g('受注確定度'),
                    'field' => 'guarantee_grade_show',
                    'width' => '70',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先コード'),
                    'field' => 'delivery_customer_no',
                    'width' => '110',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先名'),
                    'field' => 'delivery_customer_name',
                    'hide' => true,
                ),
                array(
                    'label' => _g('担当者コード'),
                    'field' => 'worker_code',
                    'width' => '110',
                    'hide' => true,
                ),
                array(
                    'label' => _g('担当者名'),
                    'field' => 'worker_name',
                    'hide' => true,
                ),
                array(
                    'label' => _g('部門コード'),
                    'field' => 'section_code',
                    'width' => '110',
                    'hide' => true,
                ),
                array(
                    'label' => _g('部門名'),
                    'field' => 'section_name',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注備考1'),
                    'field' => 'remarks_header',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注備考2'),
                    'field' => 'remarks_header_2',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注備考3'),
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
            );
        } else {
            // 明細モード
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('展開'),
                'name' => 'chk_expand',
                'type' => 'checkbox',
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " && ('[delivery_quantity]'=='0' || '[delivery_quantity]'=='') && '[is_delete]'=='true'"
                . " && '[order_class]'=='0'",
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('印刷済'),
                'field' => 'printed',
                'width' => '47',
                'align' => 'center',
                'cellId' => 'check_[id]_printed',   // 印刷時書き換え用
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('客先注番'),
                'field' => 'customer_received_number',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'received_number',
                'hide' => true,
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('見積番号'),
                'field' => 'estimate_number',
                'width' => '90',
                'align' => 'center',
                'parentColumn' => 'received_number',
                'sameCellJoin' => true,
                'hide' => true,
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('行'),
                'field' => 'line_no',
                'width' => '45',
                'align' => 'center',
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('進捗'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/chart-up.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:goProgress('[urlencode:seiban]','[received_date]','[dead_line]')",
                'helpText_noEscape' => _g('クリックすると別ウィンドウで受注別進捗画面が開きます。'),
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('実績原価'),
                'width' => '60',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/currency-yen.png' class='gen_cell_img'>",
                'align' => 'center',
                'showCondition' => "'[order_class]'=='0'",
                'link' => "javascript:goBaseCost('[urlencode:seiban]','[received_date]')",
                'helpText_noEscape' => _g('クリックすると別ウィンドウで製番別原価画面が開きます。') . '<br>' . _g('製番品目のみリンクが表示されます（MRP品目の原価を見ることはできません）。') . '<br>' . _g('子品目を含め、関連するすべての製造実績・注文受入が登録された状態でなければ、正確な原価は表示されません。'),
                'hide' => true,
            );

            $form['gen_columnArray'] = array(
                array(
                    'label' => _g('得意先コード'),
                    'field' => 'customer_no',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'width' => '110',
                    'hide' => true,
                ),
                array(
                    'label' => _g('得意先名'),
                    'field' => 'customer_name',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                ),
                array(
                    'label' => _g('取引先グループコード1'),
                    'field' => 'customer_group_code_1',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名1'),
                    'field' => 'customer_group_name_1',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループコード2'),
                    'field' => 'customer_group_code_2',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名2'),
                    'field' => 'customer_group_name_2',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループコード3'),
                    'field' => 'customer_group_code_3',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('取引先グループ名3'),
                    'field' => 'customer_group_name_3',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
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
                    'label'=>_g("ロット"),
                    'field'=>'lot_status',
                    'type'=>'data',
                    'width'=>50,
                    'align'=>'center',
                    'link'=>"javascript:lotEntry('[seiban]','[lot_status]')",
                    'hide'=>true,
                    'helpText_noEscape'=>_g("品目マスタ「管理区分」が「ロット」の品目だけが対象です。ロット引当の状況を表示します。") . "<br><br>"
                        ._g("受注に対してロットがまだ引き当てられていない場合は「未」と表示されます。クリックするとロット引当処理を行うことができます。") . "<br>"
                        ._g("個別にロットを指定する必要がない場合、一括ロット引当処理を行うこともできます。その処理においては、引き当てるロットは消費期限順に自動的に割り振られます。リスト情報の「一括ロット引当」ボタンをクリックしてください。") . "<br><br>"
                        ._g("受注に対してロットがすでに引き当てられている場合は「済」と表示されます。クリックすると製番引当登録画面の該当レコードが表示されます。") . "<br>"
                        ._g("製番引当を修正もしくは削除すれば、ロット引当を修正・解除することができます。") . "<br>"
                        ._g("引き当てられているロット番号は、このリストの「出庫ロット」列に表示されます。")
                ),
                array(
                    'label'=>_g('出庫ロット'),
                    'field'=>'lot_no',
                    'width'=>'200',
                    'hide'=>true,
                    'helpText_noEscape'=>_g("品目マスタ「管理区分」が「ロット」の品目だけが対象です。引き当てられたロットを表示します。") . "<br><br>"
                        ._g("ロットは実績および受入登録の際に生成されます。受注に対してロットを引き当てる方法については、このリストの「ロット」列のチップヘルプをご覧ください。")
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
                    'label' => _g('受注日'),
                    'field' => 'received_date',
                    'type' => 'date',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                ),
                array(
                    'label' => _g('受注納期'),
                    'field' => 'dead_line',
                    'type' => 'date',
                ),
                array(
                    'label' => _g('数量'),
                    'field' => 'received_quantity',
                    'type' => 'numeric',
                ),
                array(
                    'label' => _g('理論在庫数'),
                    'field' => 'logical_stock_quantity',
                    'type' => 'numeric',
                    'showCondition' => ($form['gen_search_show_stock'] == "true" ? "true" : "false") . " && '[order_class]'=='1'",
                    'helpText_noEscape' => _g('表示条件の「理論在庫の表示」で“表示する”を選択すると、[在庫リスト]と同じ「理論在庫数」が表示されます。') . "<br>" . _g('表示の対象は管理区分がMRPの品目のみで、製番の品目は表示されません。'),
                    'hide' => true,
                ),
                array(
                    'label' => _g('単位'),
                    'field' => 'measure',
                    'type' => 'data',
                    'width' => '35',
                ),
                array(
                    'label' => _g('受注単価'),
                    'field' => 'product_price',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注額'),
                    'field' => 'amount',
                    'type' => 'numeric',
                ),
                array(
                    'label' => _g('販売原価'),
                    'field' => 'sales_base_cost',
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
                    'label' => _g('引当数'),
                    'field' => 'use_plan_quantity',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('納品'),
                    'field' => 'delivery',
                    'width' => '80',
                    'align' => 'center',
                    'hide' => true,
                ),
                // 表示内容としては「納品」と重なっているが、「納品」の「未（残100）」といった
                // 表示では数量の集計ができず不便、という意見がありこの欄をあらたに設けた。
                array(
                    'label' => _g('受注残'),
                    'field' => 'remained_qty',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注残額'),
                    'field' => 'remained_amount',
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
                    'label' => _g('受注単価(外貨)'),
                    'field' => 'foreign_currency_product_price',
                    'type' => 'numeric',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注額(外貨)'),
                    'field' => 'foreign_currency_amount',
                    'type' => 'numeric',
                    'width' => '100',
                    'hide' => true,
                ),
                array(
                    'label' => _g('販売原価(外貨)'),
                    'field' => 'foreign_currency_sales_base_cost',
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
                    'label' => _g('受注確定度'),
                    'field' => 'guarantee_grade_show',
                    'width' => '70',
                    'align' => 'center',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先コード'),
                    'field' => 'delivery_customer_no',
                    'width' => '110',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('発送先名'),
                    'field' => 'delivery_customer_name',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('担当者コード'),
                    'field' => 'worker_code',
                    'width' => '110',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('担当者名'),
                    'field' => 'worker_name',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('部門コード'),
                    'field' => 'section_code',
                    'width' => '110',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('部門名'),
                    'field' => 'section_name',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注備考1'),
                    'field' => 'remarks_header',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注備考2'),
                    'field' => 'remarks_header_2',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注備考3'),
                    'field' => 'remarks_header_3',
                    'sameCellJoin' => true,
                    'parentColumn' => 'received_header_id',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注明細備考1'),
                    'field' => 'remarks',
                    'hide' => true,
                ),
                array(
                    'label' => _g('受注明細備考2'),
                    'field' => 'remarks_2',
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
                array(
                    'label' => _g('子品目（構成表マスタ）'),
                    'field' => 'in_bom',
                    'align' => 'center',
                    'width' => '100',
                    'hide' => true,
                ),
            );
        }
    }

}
