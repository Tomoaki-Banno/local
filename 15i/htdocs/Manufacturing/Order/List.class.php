<?php

class Manufacturing_Order_List extends Base_ListBase
{
    var $isDetailMode;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = 'select process_id, process_name from process_master order by process_code';
        $option_process = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
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
                'label' => _g('受注番号'),
                'field' => 'received_number',
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
                'label' => _g('製造指示備考'),
                'field' => 'remarks_header',
                'hide' => true,
            ),
            array(
                'label' => _g('製造開始日'),
                'type' => 'dateFromTo',
                'field' => 'order_date',
                'defaultFrom' => date('Y-m-01'),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('製造納期'),
                'type' => 'dateFromTo',
                'field' => 'order_detail_dead_line',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('親品目'),
                'type' => 'select',
                'type' => 'dropdown',
                'field' => 'parent_item_id',
                'size' => '150',
                'dropdownCategory' => 'item',
                'nosql' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('完了分の表示'),
                'type' => 'select',
                'field' => 'completed_status',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
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
                'label' => _g('明細の表示'),
                'type' => 'select',
                'field' => 'show_detail',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
                'nosql' => 'true',
                'default' => 'false',
            ),
            array(
                'label' => _g('工程納期'),
                'type' => 'dateFromTo',
                'field' => 'process_dead_line',
                'rowSpan' => 2,
                'hide' => true,
            ),
            array(
                'label' => _g('工程'),
                'type' => 'select',
                'field' => 't_process___process_id',
                'options' => $option_process,
                'hide' => true,
            ),
            array(
                'label' => _g('工程完了分の表示'),
                'type' => 'select',
                'field' => 'process_completed_status',
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
        }
    
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("品目別日次製造数（残数。当月）") => self::_getPreset("5", "false", "order_detail_dead_line_day", "item_name", "", "remained_quantity"),
                _g("品目別日次製造数（完了含む。当月）") => self::_getPreset("5", "false", "order_detail_dead_line_day", "item_name"),
                _g("品目別月次製造数（残数。当年）") => self::_getPreset("7", "false", "order_detail_dead_line_month", "item_name", "", "remained_quantity"),
                _g("品目別月次製造数（完了含む。当年）") => self::_getPreset("7", "false", "order_detail_dead_line_month", "item_name"),
                _g("工程別日次製造数（完了含む。当月）") => self::_getPreset("5", "true", "order_detail_dead_line_day", "process_name", "", "achievement_total"),
                _g("工程別月次製造数（完了含む。当年）") => self::_getPreset("7", "true", "order_detail_dead_line_month", "process_name", "", "achievement_total"),
            );
    }
    
    function _getPreset($datePattern, $showDetail, $horiz, $vert, $orderby = "", $value = "order_detail_quantity", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "order_date", "dp" => "0"),
                    array("f" => "order_detail_dead_line", "dp" => $datePattern),
                    array("f" => "completed_status", "v" => "true"),
                    array("f" => "show_detail", "v" => $showDetail),
                    
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
        $this->isDetailMode = (@$form['gen_search_show_detail'] == "true");
    }

    function setQueryParam(&$form)
    {
        global $gen_db;
        
        // 所要量計算結果の取り込み
        $orderHeaderIdList = "";
        if (isset($form['mrp'])) {
            $orderHeaderIdArray = Logic_Order::mrpToOrder(1);

            if (is_array($orderHeaderIdArray)) {
                $orderHeaderIdList = join($orderHeaderIdArray, ",");    // 配列をカンマ区切り文字列にする

                $_SESSION['gen_order_list_for_mrp_mode'] = $orderHeaderIdList;

                Gen_Log::dataAccessLog(_g("製造指示登録"), _g("新規"), _g("所要量計算結果からの一括発行"));
            }
        }

        // 取込モードで、明細画面へ行ってから戻ったときに、モードを復元が解除
        // されてしまわないようにするためのsession処理
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

        // 構成変更の反映状況をテンポラリテーブルに取得。
        //   13iまではメインSQLの中でleft joinしていたが、データ量が多い場合にかなり遅くなることがあったので
        //   15iからテンポラリテーブル化した。
        //   なお、子品目に1つでもダミー品目が含まれている場合はチェック対象外とする。
        //   Edit画面ではダミー品目ありでも変更チェックしているので結果が食い違ってしまうが、
        //   Edit同様の処理をListで行おうとするとどうしても処理が重くなってしまうため、やむをえないと判断した。
        $query = "
            create temp table temp_bom_change as 
                select
                    order_detail_id as odi
                from
                    (select
                        order_child_item.order_detail_id
                    from
                        order_child_item
                        left join order_detail on order_child_item.order_detail_id=order_detail.order_detail_id
                        left join bom_master on order_detail.item_id = bom_master.item_id
                            and order_child_item.child_item_id=bom_master.child_item_id
                    where
                        order_child_item.quantity <> coalesce(bom_master.quantity,0)

                    union
                    select
                        order_detail.order_detail_id
                    from
                        bom_master
                        inner join order_detail on bom_master.item_id=order_detail.item_id
                        left join order_child_item on order_child_item.order_detail_id=order_detail.order_detail_id
                            and order_child_item.child_item_id=bom_master.child_item_id
                    where
                        order_child_item.order_detail_id is null
                    ) as t_och1

                    -- 子品目に1つでもダミー品目が含まれている場合はチェック対象外とする。
                    inner join (
                    select
                        order_detail_id as odi2
                    from
                        order_detail
                        inner join bom_master on order_detail.item_id = bom_master.item_id
                        inner join item_master on bom_master.child_item_id = item_master.item_id
                    group by
                        order_detail.order_detail_id
                    having
                        max(case when coalesce(dummy_item, false) then 1 else 0 end)=0
                    ) as t_not_dummy on t_och1.order_detail_id = t_not_dummy.odi2
                group by
                    order_detail_id
                ;
        ";
        $gen_db->query($query);
        
        $this->selectQuery = "
            select
                order_header.order_header_id
        ";
        if (!$this->isDetailMode) {
            // ヘッダモード
            $this->selectQuery .= "
                ,max(order_detail_id) as order_detail_id
                ,max(order_no) as order_no
                ,max(seiban) as seiban
                ,max(received_number) as received_number
                ,max(received_customer_no) as received_customer_no
                ,max(received_customer_name) as received_customer_name
                ,max(received_estimate_number) as received_estimate_number
                ,max(received_worker_name) as received_worker_name
                ,max(received_section_name) as received_section_name
                ,max(order_detail.item_id) as item_id
                ,max(order_detail.item_code) as item_code
                ,max(order_detail.item_name) as item_name
                ,max(maker_name) as maker_name
                ,max(spec) as spec
                ,max(rack_no) as rack_no
                ,max(order_detail_quantity) as order_detail_quantity
                ,max(coalesce(accepted_quantity,0)) as accepted_quantity     /* linkConditionのためにcoalesce */
                ,max(measure) as measure
                ,max(order_date) as order_date
                ,max(order_detail_dead_line) as order_detail_dead_line
                ,max(remarks_header) as remarks_header
                ,max(case when order_printed_flag = true then '" . _g("印刷済") . "' else '' end) as printed
                ,max(case when order_detail_completed then '" . _g("完") . "' else
                    '" . _g("未(残") . "' || (coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) || ')' end) as completed
                ,max(case when cast(order_detail_completed as int)=1 then 0
                    else coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0) end) as remained_quantity
                ,sum(waster_total) as waster_total
                ,max(case when t_ach.odi is null and t_acc.odi is null then 0 else 1 end) as achievement_exist
                ,max(case when temp_bom_change.odi is null then '' else '" . _g("未反映") . "' end) as modified
                ,max(case when alarm_flag then 1 else 0 end) as alarm_flag
                ,max(comment) as comment
                ,max(comment_2) as comment_2
                ,max(comment_3) as comment_3
                ,max(comment_4) as comment_4
                ,max(comment_5) as comment_5

                ,max(coalesce(order_detail.record_update_date, order_detail.record_create_date)) as gen_record_update_date
                ,max(coalesce(order_detail.record_updater, order_detail.record_creator)) as gen_record_updater
            ";
        } else {
            // 明細モード
            $this->selectQuery .= "
                ,order_detail_id
                ,order_no
                ,seiban
                ,received_number
                ,received_customer_no
                ,received_customer_name
                ,received_estimate_number
                ,received_worker_name
                ,received_section_name
                ,order_detail.item_id
                ,order_detail.item_code
                ,order_detail.item_name
                ,maker_name
                ,spec
                ,rack_no
                ,order_detail_quantity
                ,coalesce(accepted_quantity,0) as accepted_quantity     /* linkConditionのためにcoalesce */
                ,measure
                ,order_date
                ,order_detail_dead_line
                ,remarks_header
                ,case when order_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed
                ,case when order_detail_completed then '" . _g("完") . "' else
                    '" . _g("未(残") . "' || (coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) || ')' end as completed
                ,case when cast(order_detail_completed as int)=1 then 0
                    else coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0) end as remained_quantity
                ,case when t_ach.odi is null and t_acc.odi is null then 0 else 1 end as achievement_exist
                ,case when temp_bom_change.odi is null then '' else '" . _g("未反映") . "' end as modified
                ,case when alarm_flag then 1 else 0 end as alarm_flag
                ,comment
                ,comment_2
                ,comment_3
                ,comment_4
                ,comment_5

                ,order_process_no
                ,process_code
                ,process_name
                ,process_dead_line
                ,process_remarks_1
                ,process_remarks_2
                ,process_remarks_3
                ,achievement_total
                ,waster_total

                ,coalesce(order_detail.record_update_date, order_detail.record_create_date) as gen_record_update_date
                ,coalesce(order_detail.record_updater, order_detail.record_creator) as gen_record_updater
            ";
        }
        $this->selectQuery .= "
            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                inner join (
                    select 
                        order_detail_id as id2
                        ,case when order_detail_completed then 'true' else 'false' end as completed_status 
                    from 
                        order_detail
                ) as t0 on order_detail.order_detail_id = t0.id2
                left join (
                    select 
                        seiban as s2
                        ,max(received_number) as received_number
                        ,max(customer_master.customer_no) as received_customer_no
                        ,max(customer_master.customer_name) as received_customer_name
                        ,max(estimate_header.estimate_number) as received_estimate_number
                        ,max(worker_master.worker_name) as received_worker_name
                        ,max(section_master.section_name) as received_section_name
                    from 
                        received_detail 
                        inner join received_header on received_header.received_header_id = received_detail.received_header_id 
                        left join customer_master on received_header.customer_id = customer_master.customer_id 
                        left join estimate_header on received_header.estimate_header_id = estimate_header.estimate_header_id 
                        left join worker_master on received_header.worker_id = worker_master.worker_id 
                        left join section_master on received_header.section_id = section_master.section_id 
                    where 
                        seiban<>'' 
                    group by seiban
                ) as t_rec on order_detail.seiban = t_rec.s2
                left join (select order_detail_id as odi from achievement group by order_detail_id) as t_ach on order_detail.order_detail_id = t_ach.odi
                left join (select order_detail_id as odi from accepted group by order_detail_id) as t_acc on order_detail.order_detail_id = t_acc.odi
                left join item_master on order_detail.item_id = item_master.item_id
                " . (is_numeric(@$form['gen_search_parent_item_id']) ?
                        " inner join (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on order_detail.item_id = t_exp.exp_item_id " : "") . "
                left join (
                    select 
                        order_detail_id as odi
                        ,order_process_no
                        ,process_id
                        ,process_dead_line
                        ,process_remarks_1
                        ,process_remarks_2
                        ,process_remarks_3
                    from 
                        order_process
                ) as t_process on order_detail.order_detail_id = t_process.odi
                left join process_master on t_process.process_id = process_master.process_id
                left join (
                    select 
                        order_detail_id as odi
                        ,process_id as pid
                        ,sum(achievement_quantity) as achievement_total
                        ,max(waster_total) as waster_total
                    from 
                        achievement 
                        left join 
                            (select achievement_id, sum(waster_quantity) as waster_total from waster_detail group by achievement_id) as t_waster
                            on achievement.achievement_id = t_waster.achievement_id group by order_detail_id, process_id
                ) as t_achievement on order_detail.order_detail_id = t_achievement.odi and t_process.process_id = t_achievement.pid
                left join temp_bom_change
                    on order_detail.order_detail_id = temp_bom_change.odi

            [Where]
                and order_header.classification in (0)
             	" . ($form['gen_search_printed'] == '1' ? ' and not coalesce(order_printed_flag,false)' : '') . "
             	" . ($form['gen_search_printed'] == '2' ? ' and order_printed_flag' : '') . "
             	" . (isset($form['gen_search_process_completed_status']) && $form['gen_search_process_completed_status'] == "false" ? ' and coalesce(order_detail_quantity,0) > coalesce(achievement_total,0)' : '') . "
        ";
        if (isset($form['mrp'])) {
            // 所要量計算の結果取込モードの場合
            //    取り込まれたデータのみを表示
            if ($orderHeaderIdList == "") {
                $this->selectQuery .= " and 1=0";
            } else {
                $this->selectQuery .= " and order_header.order_header_id in ({$orderHeaderIdList})";
            }
        }
        if ($form['gen_search_completed_status'] == "false") {
            $this->selectQuery .= " and completed_status = 'false' "; // 「含める」（true）のときはtrueもfalseも
        }
        if (!$this->isDetailMode) {
            $this->selectQuery .= "
            group by
                order_header.order_header_id
            ";
        }
        $this->selectQuery .= "
            [Orderby]
        ";
        $this->orderbyDefault = 'order_date desc, order_no, order_header.order_header_id' . ($this->isDetailMode ? ", order_process_no" : "");
        if ($this->isDetailMode) {
            $this->customColumnTables = array(
                // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
                //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
                //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
                //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
                //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
                //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
                //          estimate_detail, received_detail, delivery_detail, order_detail
                array("item_master", "", "", "order_detail_id"),
                array("process_master", "", "", "order_detail_id"),
            );        
        }
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("製造指示登録");
        $form['gen_importMsg_noEscape'] = _g("※フォーマットは次のとおりです。") . "<br>" .
                _g("　　オーダー番号(新規登録時は空欄), 製番, 製造開始日, 製造納期, 品目コード, 数量, 製造指示備考") . "<br><br>" .
                _g("※新規登録の場合は、オーダー番号欄を空欄にしてください。") . "<br>" .
                _g("　（オーダー番号を指定して新規登録することはできません。）") . "<br>" .
                _g("　上書きの場合は、オーダー番号欄を入力してください。また、登録前に下の「上書き許可」をオンにしてください。");
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（オーダー番号が既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
            ),
            array(
                'label' => _g('製造開始日'),
                'field' => 'order_date',
            ),
            array(
                'label' => _g('製造納期'),
                'field' => 'order_detail_dead_line',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('数量'),
                'field' => 'order_detail_quantity',
            ),
            array(
                'label' => _g('製造指示備考'),
                'field' => 'remarks_header',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("製造指示登録");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        // ここはあえて「&gen_restore_search_condition=true」が必要。
        // MRP取込モードで絞り込み条件検索したとき、取込モードが解除されてしまうのを避けるため。
        $form['gen_listAction'] = "Manufacturing_Order_List&gen_restore_search_condition=true";
        $form['gen_editAction'] = "Manufacturing_Order_Edit";
        $form['gen_deleteAction'] = "Manufacturing_Order_Delete";
        $form['gen_idField'] = 'order_header_id';
        $form['gen_idFieldForUpdateFile'] = "order_header.order_header_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("製造指示");

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("製造指示書 印刷"),
                'link' => "javascript:gen.list.printReport('Manufacturing_Order_Report','check')",
                'reportEdit' => 'Manufacturing_Order_Report'
            ),
            array(
                'label' => _g("製造指示書（リスト） 印刷"),
                'link' => "javascript:gen.list.printReport('Manufacturing_Order_Report2','check')",
                'reportEdit' => 'Manufacturing_Order_Report2'
            ),
        );

        //  簡易受入で遷移するEntryクラスでgen_page_request_idが必要
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;

        $form['gen_javascript_noEscape'] = "
            function divide(orderDetailId) {
               gen.modal.open('index.php?action=Manufacturing_Order_DivideEdit&order_detail_id=' + orderDetailId);
            }

            function showOrderProgress(orderNo, from , to) {
               window.open('index.php?action=Progress_OrderProgress_List'
               + '&gen_searchConditionClear'
               + '&gen_search_order_no=' + orderNo
               + '&gen_search_order_no=' + orderNo
               + '&gen_search_date_from=' + from
               + '&gen_search_date_to=' + to
               , 'progress');
            }

            function doAccept(orderDetailId, orderNo, classStr) {
               if (!confirm('" . _g("オーダー番号 %orderNo の製造実績登録を行います。指示どおりの数量・製造納期で製造したものとして登録されます。製造時間は0となります。設備・作業者・部門・不適合数は登録されません。複数の工程が存在する場合は、最終工程の実績となります。\\n\\n※数量や製造納期を変更したり、製造時間を指定したり、「着手」や中間工程の登録を行いたい場合は、実績登録画面で登録を行ってください。\\n\\n登録を行ってもよろしいですか？") . "'.replace('%orderNo',orderNo))) return;
               var url = 'index.php?action=';
               url += 'Manufacturing_Achievement_Entry';
               url += '&easy_mode=true'
                + '&order_detail_id=' + orderDetailId
                + '&gen_page_request_id=" . h($reqId) . "';
               location.href = url;
            }
            
            function showItemMaster(itemId) {
                gen.modal.open('index.php?action=Master_Item_Edit&item_id=' + itemId);
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[completed]'=='" . _g("完") . "'", // 完了行の色付け
            // 一部完了行の色付け。
            // ag.cgi?page=ProjectDocView&ppid=1516&pbid=166873 により、製造数0でも不適合があれば該当とした
            "#aee7fa" => "'[accepted_quantity]'>0 || '[waster_total]'>0",
            // ちなみに、外製工程がある場合、最終以外の工程の実績が上がっただけでは
            // 色付けが行われない。その場合も色をつけたほうがいいかもしれないが、
            // 状況取得が複雑で、リストSQLが重くなりそうなのでやめた
            "#f9bdbd" => "'[alarm_flag]'=='1'", // アラーム（間に合わない品目）の色付け
        );

        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("製造完了")),
            "aee7fa" => array(_g("ブルー"), _g("一部製造済み")),
            "f9bdbd" => array(_g("ピンク"), _g("所要量計算でLTと休業日を無視して製造納期調整したオーダー")),
        );

        if (isset($form['mrp'])) {
            $form['gen_message_noEscape'] =
                    _g("今回の所要量計算により作成されたオーダーだけが表示されています。") . "<BR>" .
                    "<a href=\"index.php?action=Manufacturing_Mrp_List\">" . _g("所要量計算の結果に戻る") . "</a>";
        }

        $form['gen_isClickableTable'] = "true";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'field' => 'dummy_delete',  // sameCellJoin指定にはfieldが必要
                'width' => '42',
                'type' => 'delete_check',
                'deleteAction' => 'Manufacturing_Order_BulkDelete',
                'beforeAction' => 'Manufacturing_Order_AjaxPrintedCheck', // 印刷済チェック
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " and [achievement_exist] == 0",
                'sameCellJoin' => true,
                'parentColumn' => 'order_header_id',
                'align' => 'center',
            ),
            array(
                'label' => _g("印刷"),
                'name' => 'check',
                'type' => 'checkbox',
                'sameCellJoin' => true,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('印刷済'),
                'field' => 'printed',
                'width' => '47',
                'align' => 'center',
                'cellId' => 'check_[id]_printed', // 印刷時書き換え用
                'sameCellJoin' => true,
                'parentColumn' => 'order_header_id',
                'helpText_noEscape' => _g("未印刷であっても、データとしては確定扱いです（正式なオーダーとして所要量計算等で考慮されます）。") . "<br>" . _g("印刷済データを修正した場合、未印刷に戻ります。"),
            ),
            array(
                'label' => _g('ｵｰﾀﾞｰ番号'),
                'field' => 'order_no',
                'width' => '77',
                'align' => 'center',
                'sameCellJoin' => $this->isDetailMode,  // 以前は'true'固定だったが、sameCellJoinがtrueだとクロス集計の「値」の対象とできないため、モードにより変えるようにした。
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('ｸｲｯｸ実績'),
                'width' => '63',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/arrow-curve-000-left.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:doAccept([order_detail_id], '[urlencode:order_no]', '[class]')",
                'showCondition' => "([achievement_exist] == 0 && " . ($form['gen_readonly'] ? "false" : "true") . ")",
                'sameCellJoin' => true,
                'parentColumn' => 'order_header_id',
                'helpText_noEscape' => _g("リンクをクリックすると、その製造指示に対する製造実績（外製の場合は受入）の登録を行います。") . "<br>" .
                    _g("指示通りの数量・製造納期で製造したものとして登録されます。") . "<br>" .
                    _g("作業開始時刻・終了時刻・部門・作業者・設備・不適合数は登録されません。") . "<br>" .
                    _g("複数の工程がある場合、最終工程の実績だけが登録されます。") . "<br>" .
                    _g("製造納期や数量を変更したい場合や、中間工程の実績の登録を行いたい場合、また時刻や作業者、不適合数等を登録したい場合は実績登録画面で行ってください。"),
            ),
            array(
                'label' => _g('進捗'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/chart-up.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:showOrderProgress('[urlencode:order_no]','[order_date]','[order_detail_dead_line]')",
                'sameCellJoin' => true,
                'parentColumn' => 'order_header_id',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '80',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。MRP品目は受注とオーダーの結びつきがないため、受注番号を表示できません。'),
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'type' => 'data',
                'sameCellJoin' => $this->isDetailMode,  // 列 order_no のコメント参照
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('品目マスタ'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/application-form.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:showItemMaster('[item_id]')",
                'hide' => true,
                'sameCellJoin' => true,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
                'hide' => true,
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
                'hide' => true,
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'hide' => true,
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('数量'),
                'type' => 'numeric',
                'field' => 'order_detail_quantity',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('製造済数'),
                'type' => 'numeric',
                'field' => 'accepted_quantity',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('製造残数'),
                'type' => 'numeric',
                'field' => 'remained_quantity',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('製造開始日'),
                'field' => 'order_date',
                'type' => 'date',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('製造納期'),
                'type' => 'date',
                'field' => 'order_detail_dead_line',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
            ),
            array(
                'label' => _g('製造状況'),
                'field' => 'completed',
                'width' => '80',
                'align' => 'center',
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('受注得意先コード'),
                'field' => 'received_customer_no',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('受注得意先名'),
                'field' => 'received_customer_name',
                'width' => '150',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('見積書番号'),
                'field' => 'received_estimate_number',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('受注担当者（自社）'),
                'field' => 'received_worker_name',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('受注部門（自社）'),
                'field' => 'received_section_name',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'sameCellJoin' => $this->isDetailMode,
                'parentColumn' => 'order_header_id',
                'hide' => true,
            ),
        );
        if ($this->isDetailMode) {
            $form['gen_columnArray'][] = array(
                'label' => _g('工程番号'),
                'field' => 'order_process_no',
                'width' => '110',
                'align' => 'center',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('工程コード'),
                'field' => 'process_code',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('工程名'),
                'field' => 'process_name',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('工程実績数'),
                'type' => 'numeric',
                'field' => 'achievement_total',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('工程不適合数'),
                'type' => 'numeric',
                'field' => 'waster_total',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('工程納期'),
                'field' => 'process_dead_line',
                'type' => 'date',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('工程メモ1'),
                'field' => 'process_remarks_1',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('工程メモ2'),
                'field' => 'process_remarks_2',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('工程メモ3'),
                'field' => 'process_remarks_3',
                'colorCondition' => array(
                    "#cccccc" => "'[order_detail_quantity]' <= '[achievement_total]'",
                    "#66ffff" => "'[achievement_total]' > 0",
                ),
            );
        }
        $form['gen_columnArray'][] = array(
            'label' => _g('構成変更'),
            'field' => 'modified',
            'width' => '80',
            'align' => 'center',
            'sameCellJoin' => $this->isDetailMode,
            'parentColumn' => 'order_header_id',
            'hide' => true,
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('製造指示備考'),
            'field' => 'remarks_header',
            'sameCellJoin' => $this->isDetailMode,
            'parentColumn' => 'order_header_id',
            'hide' => true,
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('品目備考1'),
            'field' => 'comment',
            'sameCellJoin' => $this->isDetailMode,
            'parentColumn' => 'order_header_id',
            'hide' => true,
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('品目備考2'),
            'field' => 'comment_2',
            'sameCellJoin' => $this->isDetailMode,
            'parentColumn' => 'order_header_id',
            'hide' => true,
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('品目備考3'),
            'field' => 'comment_3',
            'sameCellJoin' => $this->isDetailMode,
            'parentColumn' => 'order_header_id',
            'hide' => true,
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('品目備考4'),
            'field' => 'comment_4',
            'sameCellJoin' => $this->isDetailMode,
            'parentColumn' => 'order_header_id',
            'hide' => true,
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('品目備考5'),
            'field' => 'comment_5',
            'sameCellJoin' => $this->isDetailMode,
            'parentColumn' => 'order_header_id',
            'hide' => true,
        );
    }

}
