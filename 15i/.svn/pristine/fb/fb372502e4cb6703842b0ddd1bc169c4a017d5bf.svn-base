<?php

class Partner_Subcontract_List extends Base_ListBase
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
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('親オーダー番号'),
                'field' => 'subcontract_parent_order_no',
                'hide' => true,
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
                'label' => _g('発注先コード/名'),
                'field' => 'customer_no',
                'field2' => 'customer_name',
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 'customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
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
                'label' => _g('工程名'),
                'field' => 'subcontract_process_name',
                'hide' => true,
            ),
            array(
                'label' => _g('発行日'),
                'type' => 'dateFromTo',
                'field' => 'order_date',
                'defaultFrom' => date('Y-m-01'),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('外注納期'),
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
                'label' => _g('外製指示備考'),
                'field' => 'remarks_header',
                'ime' => 'on',
                'hide' => true,
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
                _g("日次外製額（当月）") => self::_getPreset("5", "gen_all", "order_date_day", ""),
                _g("月次外製額（今年）") => self::_getPreset("7", "gen_all", "order_date_month", ""),
                _g("外製額 前年対比") => self::_getPreset("0", "order_date_month", "order_date_year", ""),
                _g("品目別 受入残数") => self::_getPreset("0", "order_detail_dead_line_day", "item_name", "", "remained_qty", "sum", "false"),
                _g("品目別 受入残額") => self::_getPreset("0", "order_detail_dead_line_day", "item_name", "", "remained_amount", "sum", "false"),
                _g("発注先別 受入残数") => self::_getPreset("0", "order_detail_dead_line_day", "customer_name", "", "remained_qty", "sum", "false"),
                _g("発注先別 受入残額") => self::_getPreset("0", "order_detail_dead_line_day", "customer_name", "", "remained_amount", "sum", "false"),
                _g("発注先外製ランキング（今年）") => self::_getPreset("7", "gen_all", "customer_name", "order by field1 desc"),
                _g("品目外製ランキング（今年）") => self::_getPreset("7", "gen_all", "item_name", "order by field1 desc"),
                _g("担当者外製ランキング（今年）") => self::_getPreset("7", "gen_all", "worker_name", "order by field1 desc"),
                _g("部門外製ランキング（今年）") => self::_getPreset("7", "gen_all", "section_name", "order by field1 desc"),
                _g("発注先 - 品目（今年）") => self::_getPreset("7", "customer_name", "item_name", ""),
                _g("発注先別月次外製額（今年）") => self::_getPreset("7", "order_date_month", "customer_name", ""),
                _g("品目別月次外製額（今年）") => self::_getPreset("7", "order_date_month", "item_name", ""),
                _g("品目別月次外製数量（今年）") => self::_getPreset("7", "order_date_month", "item_name", "", "order_detail_quantity"),
                _g("担当者別月次外製額（今年）") => self::_getPreset("7", "order_date_month", "worker_name", ""),
                _g("部門別月次外製額（今年）") => self::_getPreset("7", "order_date_month", "section_name", ""),
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
    }

    function setQueryParam(&$form)
    {
        global $gen_db;
        
        // 所要量計算結果の取り込み
        $orderHeaderIdList = "";
        if (isset($form['mrp'])) {
            $orderHeaderIdArray = Logic_Order::mrpToOrder(2);

            if (is_array($orderHeaderIdArray)) {
                $orderHeaderIdList = join($orderHeaderIdArray, ",");    // 配列をカンマ区切り文字列にする

                $_SESSION['gen_order_list_for_mrp_mode'] = $orderHeaderIdList;

                Gen_Log::dataAccessLog(_g("外製指示登録"), _g("新規"), _g("所要量計算結果からの一括発行"));
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
                ,order_detail_id
                ,order_no
                ,seiban
                ,received_number
                ,order_detail.item_id
                ,order_detail.item_code
                ,order_detail.item_name
                ,maker_name
                ,spec
                ,rack_no
                ,item_master.comment as item_remarks_1
                ,item_master.comment_2 as item_remarks_2
                ,item_master.comment_3 as item_remarks_3
                ,item_master.comment_4 as item_remarks_4
                ,item_master.comment_5 as item_remarks_5
                ,customer_master.customer_no
                ,customer_name
                ,customer_master.template_subcontract
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,order_detail_quantity
                ,order_measure
                ,multiple_of_order_measure
                ,measure
                ,coalesce(accepted_quantity,0) as accepted_quantity    /* linkConditionのためにcoalesce */
                ,order_date
                ,order_detail_dead_line
                ,worker_code
                ,worker_name
                ,section_code
                ,section_name
                ,order_header.remarks_header
                ,case when order_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed
                ,case when order_detail_completed then '" . _g("完") . "' else
                    '未(残 ' || (coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) || ')' end as completed
                ,case when order_detail_completed then 0
                    else coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0) end as remained_qty
                ,case when order_detail_completed then 0
                    else (coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) * item_price end as remained_amount
                ,case when t_ach.odi is null and t_acc.odi is null then 0 else 1 end as achievement_exist
                ,case when temp_bom_change.odi is null then '' else '" . _g("未反映") . "' end as modified
                ,case when t_item_order.partner_class = 2 then '" . _g("有") . "' else '" . _g("無") . "' end as payout
                ,item_price
                /* 子品目支給元ロケ（CSVエクスポート用） */
                /* 規定ロケ(id=0)の場合は「0」。標準ロケ（id=-1）、外製工程（id=null）の場合は空欄 */
                ,case when order_detail.payout_location_id = 0 then '0' else location_code end as payout_location_code
                ,coalesce(order_amount, order_detail_quantity * item_price) as amount
                ,subcontract_parent_order_no
                ,subcontract_process_name
                ,subcontract_process_remarks_1
                ,subcontract_process_remarks_2
                ,subcontract_process_remarks_3
                ,subcontract_ship_to
                ,coalesce(subcontract_parent_order_no, order_no) as order_no_for_progress
                ,coalesce(sub_order_date, order_date) as order_date_for_progress
                ,coalesce(sub_dead_line, order_detail_dead_line) as dead_line_for_progress
                ,alarm_flag
                /* 前工程が完了している行に色をつける。第一工程の場合は無条件で色をつける */
                ,case when t_order_process.op_seq = 0 or order_detail.order_detail_quantity - coalesce(before_accepted_quantity, before_achievement_quantity) <= 0 then true else false end as before_completed

                -- foreign_currency
                ,currency_name
                ,foreign_currency_rate
                ,foreign_currency_item_price
                ,coalesce(foreign_currency_order_amount, foreign_currency_item_price * order_detail_quantity) as foreign_currency_order_amount

                -- for csv
                ,case when foreign_currency_id is null then item_price else foreign_currency_item_price end as item_price_for_csv

                ,coalesce(order_detail.record_update_date, order_detail.record_create_date) as gen_record_update_date
                ,coalesce(order_detail.record_updater, order_detail.record_creator) as gen_record_updater
            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                inner join (select order_detail_id as id2, case when order_detail_completed then 'true'
                   else 'false' end as completed_status from order_detail) as t0
                   on order_detail.order_detail_id = t0.id2
                left join item_master on order_detail.item_id = item_master.item_id
                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join worker_master on order_header.worker_id = worker_master.worker_id
                left join section_master on order_header.section_id = section_master.section_id
                left join location_master on order_detail.payout_location_id = location_master.location_id
                left join (select seiban as s2, received_number from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id) as t_rec on order_detail.seiban = t_rec.s2
                left join (select order_detail_id as odi from achievement group by order_detail_id) as t_ach on order_detail.order_detail_id = t_ach.odi
                left join (select order_detail_id as odi from accepted group by order_detail_id) as t_acc on order_detail.order_detail_id = t_acc.odi
                left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id

                /* この外製指示が内製の1工程として発行された場合・・ */
                left join (select order_detail_id as op_oid, machining_sequence as op_seq, order_process_no from order_process) as t_order_process
                    on order_detail.subcontract_order_process_no = t_order_process.order_process_no
                /* 進捗リンク用に親オーダー（製造指示書）を取得する */
                left join (select order_detail_id as sub_oid, order_date as sub_order_date, order_detail_dead_line as sub_dead_line
                    from order_detail inner join order_header on order_detail.order_header_id = order_header.order_header_id) as t_sub_detail
                    on t_order_process.op_oid = t_sub_detail.sub_oid

                left join temp_bom_change
                    on order_detail.order_detail_id = temp_bom_change.odi

                " . (is_numeric(@$form['gen_search_parent_item_id']) ?
                        " inner join (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on order_detail.item_id = t_exp.exp_item_id " : "") . "

                /* 支給有無の判断用。partner_class = 2なら支給あり */
                left join
                    (select item_id, order_user_id, max(partner_class) as partner_class from item_order_master where partner_class=2 group by item_id, order_user_id) as t_item_order
                    on order_detail.item_id = t_item_order.item_id and order_header.partner_id = t_item_order.order_user_id

                /* 以前は次の3つのleft joinをもっと上のほうに置いていたが、表示条件によってはかなり遅くなることがあった */
                /* 一番後ろに持ってくることで速くなった（藤沢15iで日付指定すると14sec程度だったの2sec程度に）*/
                
                /* 前工程の完了状況を取得する (before_accepted_quantity, before_achievement_quantity) */
                left join (select order_detail_id as op_oid, machining_sequence as op_seq, process_id, order_process_no from order_process) as t_before_process
                    on t_order_process.op_oid = t_before_process.op_oid and t_order_process.op_seq - 1 = t_before_process.op_seq
                left join (select subcontract_order_process_no, sum(accepted_quantity) as before_accepted_quantity
                    from order_detail group by subcontract_order_process_no) as t_before_order_accepted
                    on t_before_process.order_process_no = t_before_order_accepted.subcontract_order_process_no
                left join (select order_detail_id as ac_oid, process_id, sum(achievement_quantity) as before_achievement_quantity
                    from achievement group by order_detail_id, process_id) as t_before_order_achievement
                    on t_before_process.op_oid = t_before_order_achievement.ac_oid
                    and t_before_process.process_id = t_before_order_achievement.process_id
            [Where]
                and order_header.classification in (2)
             	" . ($form['gen_search_printed'] == '1' ? ' and not coalesce(order_printed_flag,false)' : '') . "
             	" . ($form['gen_search_printed'] == '2' ? ' and order_printed_flag' : '') . "
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
        $this->selectQuery .=
                " [Orderby]";

        $this->orderbyDefault = 'order_date desc, order_no, order_header.order_header_id';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "order_detail_id"),
            array("customer_master", "", "", "order_header_id"),
            array("worker_master", "", "", "order_header_id"),
            array("section_master", "", "", "order_header_id"),
            array("location_master", "", "", "order_detail_id"),
        );        
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("外製指示登録");
        $form['gen_importMsg_noEscape'] = _g("※フォーマットは次のとおりです。") . "<br>" .
                _g("　　オーダー番号, 製番, 発行日, 外注納期, 発注先コード, 品目コード, 数量, 発注単価, 単位, 手配単位倍数, ") . "<br>" .
                _g("　　担当者コード, 部門コード, 子品目支給元ロケコード, 外製指示備考") . "<br><br>" .
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
                'label' => _g('発行日'),
                'field' => 'order_date',
            ),
            array(
                'label' => _g('外注納期'),
                'field' => 'order_detail_dead_line',
            ),
            array(
                'label' => _g('発注先コード'),
                'field' => 'partner_no',
                'exportField' => 'customer_no',
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
                'label' => _g('発注単価'),
                'field' => 'item_price',
                'exportField' => 'item_price_for_csv',
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
                'label' => _g('担当者コード'),
                'field' => 'worker_code',
            ),
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
            ),
            array(
                'label' => _g('子品目支給元ロケコード'),
                'field' => 'payout_location_code',
            ),
            array(
                'label' => _g('外製指示備考'),
                'field' => 'remarks_header',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("外製指示登録");
        $form['gen_menuAction'] = "Menu_Partner";
        // ここはあえて「&gen_restore_search_condition=true」が必要。
        // MRP取込モードで絞り込み条件検索したとき、取込モードが解除されてしまうのを避けるため。
        $form['gen_listAction'] = "Partner_Subcontract_List&gen_restore_search_condition=true";
        $form['gen_editAction'] = "Partner_Subcontract_Edit";
        $form['gen_deleteAction'] = "Partner_Subcontract_Delete";
        $form['gen_idField'] = 'order_header_id';
        $form['gen_idFieldForUpdateFile'] = "order_header.order_header_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("外製指示登録");

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("外製指示書 印刷"),
                'link' => "javascript:gen.list.printReport('Partner_Subcontract_Report','check')",
                'reportEdit' => 'Partner_Subcontract_Report'
            ),
            array(
                'label' => _g("外製指示書(リスト) 印刷"),
                'link' => "javascript:gen.list.printReport('Partner_Subcontract_Report3','check')",
                'reportEdit' => 'Partner_Subcontract_Report3'
            ),
            array(
                'label' => _g("払出表 印刷"),
                'link' => "javascript:gen.list.printReport('Partner_Subcontract_Report2','check')",
                'reportEdit' => 'Partner_Subcontract_Report2'
            ),
            array(
                'label' => _g("払出表(発注先別) 印刷"),
                'link' => "javascript:gen.list.printReport('Partner_Subcontract_Report4','check')",
                'reportEdit' => 'Partner_Subcontract_Report4'
            ),
        );

        //  簡易受入で遷移するEntryクラスでgen_page_request_idが必要
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;

        $form['gen_javascript_noEscape'] = "
            function divide(orderDetailId) {
                gen.modal.open('index.php?action=Partner_Subcontract_DivideEdit&order_detail_id=' + orderDetailId);
            }

            // オーダー別進捗表示
            function showOrderProgress(orderNo, from , to) {
                window.open('index.php?action=Progress_OrderProgress_List'
                + '&gen_searchConditionClear'
                + '&gen_search_order_no=' + orderNo
                + '&gen_search_date_from=' + from
                + '&gen_search_date_to=' + to);
            }

            // 簡易受入
            function doAccept(orderDetailId, orderNo) {
                if (!confirm('" . _g("オーダー番号 %orderNo の外製受入登録を行います。指示どおりの数量・外注納期で受入したものとして登録されます。\\n※数量や外注納期を変更したい場合は、外製受入登録画面で登録を行ってください。\\n登録を行ってもよろしいですか？") . "'.replace('%orderNo',orderNo))) return;
                var url = 'index.php?action=';
                url += 'Partner_SubcontractAccepted_Entry&outsourcing_mode=true';
                url += '&easy_mode=true'
                + '&order_detail_id=' + orderDetailId
                + '&gen_page_request_id={$reqId}';
                location.href = url;
            }
            
            function showItemMaster(itemId) {
                gen.modal.open('index.php?action=Master_Item_Edit&item_id=' + itemId);
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[completed]'=='" . _g("完") . "'", // 完了行の色付け
            "#aee7fa" => "'[accepted_quantity]'>0", // 一部完了行の色付け
            "#99ff99" => "'[before_completed]'=='t'", // 前工程完了の色付け
            "#f9bdbd" => "'[alarm_flag]'=='t'", // アラーム（間に合わない品目）の色付け
        );
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("受入完了")),
            "aee7fa" => array(_g("ブルー"), _g("一部受入済み")),
            "99ff99" => array(_g("グリーン"), _g("前工程完了")),
            "f9bdbd" => array(_g("ピンク"), _g("所要量計算でLTと休業日を無視して外注納期調整したオーダー")),
        );

        $form['gen_dataMessage_noEscape'] = "";
        $form['gen_message_noEscape'] = "";
        if (isset($form['mrp'])) {
            $form['gen_message_noEscape'] .=
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
                'label' => _g('コピー'),
                'type' => 'copy',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Partner_Subcontract_BulkDelete',
                'beforeAction' => 'Partner_Order_AjaxPrintedCheck', // 印刷済チェック
                // 以前は"[accepted_quantity] == 0"で判断していたが、これだと数量0の受入が存在していたときに正しく判断できないので変更
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " and [achievement_exist] == 0",
            ),
            array(
                'label' => _g("印刷"),
                'name' => 'check',
                'type' => 'checkbox',
            ),
            array(
                'label' => _g('印刷済'),
                'width' => '50',
                'align' => 'center',
                'field' => 'printed',
                'cellId' => 'check_[id]_printed', // 印刷時書き換え用
                'helpText_noEscape' => _g("印刷（発行）したデータは「印刷済」と表示されます。") . "<br>" . _g("未印刷であっても、データとしては確定扱いです（正式なオーダーとして所要量計算等で考慮されます）。") . "<br>" . _g("印刷済データを修正すると未印刷に戻ります。"),
            ),
            array(
                'label' => _g('ｵｰﾀﾞｰ番号'),
                'field' => 'order_no',
                'width' => '77',
                'type' => 'data',
                'align' => 'center',
            ),
            array(
                'label' => _g('ｸｲｯｸ受入'),
                'width' => '63',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/arrow-curve-000-left.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:doAccept([order_detail_id], '[urlencode:order_no]', '[class]')",
                // 以前は"[accepted_quantity] == 0"で判断していたが、これだと数量0の受入が存在していたときに正しく判断できないので変更
                'showCondition' => "([achievement_exist] == 0 && " . ($form['gen_readonly'] ? "false" : "true") . ")",
                'helpText_noEscape' => _g("リンクをクリックすると、その外製指示に対する受入の登録を行います。") .
                _g("指示通りの数量・外注納期で受入したものとして登録されます。") .
                _g("外注納期や数量を変更したい場合は外製受入登録画面で行ってください。"),
            ),
            array(
                'label' => _g('進捗'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/chart-up.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:showOrderProgress('[urlencode:order_no_for_progress]','[order_date_for_progress]','[dead_line_for_progress]')",
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '80',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、なおかつ所要量計算結果画面から発行されたオーダーである場合のみ表示されます。MRP品目は受注とオーダーの結びつきがないため、受注番号を表示できません。'),
                'hide' => true,
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'type' => 'data',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'width' => '120',
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
                'label' => _g('数量'),
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
                'label' => _g('受入済数'),
                'field' => 'accepted_quantity',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('発注単価'),
                'field' => 'item_price',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('発注金額'),
                'field' => 'amount',
                'type' => 'numeric',
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
                'label' => _g('発注単価(外貨)'),
                'field' => 'foreign_currency_item_price',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('発注金額(外貨)'),
                'field' => 'foreign_currency_order_amount',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('発行日'),
                'field' => 'order_date',
                'type' => 'date',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('外注納期'),
                'field' => 'order_detail_dead_line',
                'type' => 'date',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('製造状況'),
                'field' => 'completed',
                'width' => '80',
                'align' => 'center',
                'hide' => true,
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
            array(
                'label' => _g('支給'),
                'field' => 'payout',
                'width' => '40',
                'align' => 'center',
                // 本来は実際の支給動作（各オーダーの登録時点のマスタに基づく動作）を表示できるといいのだが、
                // それには Partner_Subcontract_AjaxPayoutMode の「編集モード」のようなロジックを組まねば
                // ならず、データ量が多いときに表示が遅くなることが懸念される。
                // そのため、ここでは単に現時点のマスタ情報を表示するにとどめた。
                'helpText_noEscape' => _g('品目マスタにおいて、品目の手配区分が「外製（支給あり）」になっているときは「有」、それ以外のときは「無」と表示されます。') . '<br><br>' .
                _g("ここで表示されるのは現在のマスタの状態であることにご注意ください。各オーダーの実際の動作は、オーダー登録時点のマスタの状態に基づきます。") . '<br>' .
                _g("したがって、オーダー登録後にマスタの手配区分を変更した場合、ここに表示されている情報と実際の動作が異なる場合があります。") . '<br><br>' .
                _g("各オーダーの実際の支給動作については、オーダーの編集画面の上方に表示されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('構成変更'),
                'field' => 'modified',
                'width' => '80',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者'),
                'field' => 'worker_name',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('部門'),
                'field' => 'section_name',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('親オーダー番号'),
                'field' => 'subcontract_parent_order_no',
                'width' => '100',
                'helpText_noEscape' => _g('この外製指示書が製造指示書の外製工程として発行された場合に、その製造指示書のオーダー番号を表示します。'),
                'hide' => true,
            ),
            array(
                'label' => _g('工程'),
                'field' => 'subcontract_process_name',
                'width' => '100',
                'helpText_noEscape' => _g('この外製指示書が製造指示書の外製工程として発行された場合に、その工程名を表示します。'),
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ1'),
                'field' => 'subcontract_process_remarks_1',
                'width' => '100',
                'helpText_noEscape' => _g('この外製指示書が製造指示書の外製工程として発行された場合に、品目マスタの工程タブの「工程メモ」を表示します。'),
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ2'),
                'field' => 'subcontract_process_remarks_2',
                'width' => '100',
                'helpText_noEscape' => _g('この外製指示書が製造指示書の外製工程として発行された場合に、品目マスタの工程タブの「工程メモ」を表示します。'),
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ3'),
                'field' => 'subcontract_process_remarks_3',
                'width' => '100',
                'helpText_noEscape' => _g('この外製指示書が製造指示書の外製工程として発行された場合に、品目マスタの工程タブの「工程メモ」を表示します。'),
                'hide' => true,
            ),
            array(
                'label' => _g('発送先'),
                'field' => 'subcontract_ship_to',
                'width' => '100',
                'helpText_noEscape' => _g('この外製指示書が製造指示書の外製工程として発行された場合に、次工程のオーダー先（自社もしくは外製先）を表示します。'),
                'hide' => true,
            ),
            array(
                'label' => _g('帳票テンプレート'),
                'field' => 'template_subcontract',
                'helpText_noEscape' => _g("取引先マスタの [帳票(外製指示書)] です。指定されている場合はそのテンプレートが使用されます。未指定の場合、テンプレート設定画面で選択されたテンプレートが使用されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('外製指示備考'),
                'field' => 'remarks_header',
                'hide' => true,
            ),
        );
    }

}