<?php

class Manufacturing_Achievement_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        
        $wasterField = array();
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            $wasterField[] = "waster_code_{$i}";
            $wasterField[] = "waster_name_{$i}";
        }

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('工程コード/名'),
                'field' => 'process_code',
                'field2' => 'process_name',
            ),
            array(
                'label' => _g('製造日'), // カレンダーがセレクタと重なるとIE6で表示が乱れるので位置に注意
                'type' => 'dateFromTo',
                'field' => 'achievement_date',
                'defaultFrom' => date('Y-m-01'),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'order_seiban',
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
                'label' => _g('作業者コード/名'),
                'field' => 'worker_code',
                'field2' => 'worker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('ロット番号'),
                'field' => 'lot_no',
                'hide' => true,
            ),
            array(
                'label' => _g('使用ロット番号'),
                'field' => 'use_lot_no',
                'hide' => true,
            ),
            array(
                'label' => _g('不適合理由コード/名'),
                'field' => 'waster_code',
                'fieldArray' => $wasterField,
                'hide' => true,
            ),
            array(
                'label' => _g('設備コード/名'),
                'field' => 'equip_code',
                'field2' => 'equip_name',
                'hide' => true,
            ),
            array(
                'label' => _g('入庫ロケーション'),
                'type' => 'select', // HTMLのセレクタ
                'field' => 'location_id',
                'options' => $option_location_group,
                'hide' => true,
            ),
            array(
                'label' => _g('実績備考'),
                'field' => 'achievement___remarks',
                'ime' => 'on',
                'hide' => true,
            ),
        );
    
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("品目別日次製造数（当月）") => self::_getPreset("5", "achievement_date_day", "item_name"),
                _g("品目別月次製造数（当年）") => self::_getPreset("7", "achievement_date_month", "item_name"),
                _g("工程別日次製造数（当月）") => self::_getPreset("5", "achievement_date_day", "process_name"),
                _g("工程別月次製造数（当年）") => self::_getPreset("7", "achievement_date_month", "process_name"),
                _g("設備 - 品目（当年）") => self::_getPreset("7", "equip_name", "item_name"),
                _g("作業者 - 品目（当年）") => self::_getPreset("7", "worker_name", "item_name"),
                _g("品目 - 不適合1（当年）") => self::_getPreset("7", "waster_name_1", "item_name", "", "waster_qty"),
                _g("作業者 - 不適合1（当年）") => self::_getPreset("7", "waster_name_1", "worker_name", "", "waster_qty"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby = "", $value = "achievement_quantity", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "achievement_date", "dp" => $datePattern),
                    
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
        $this->selectQuery = "
            select
                achievement.order_header_id
                ,achievement.order_detail_id
                ,achievement.achievement_id
                ,achievement.achievement_date
                ,achievement.lot_no
                ,achievement.use_lot_no
                ,achievement.use_by
                ,achievement.item_id
                ,item_master.item_code
                ,item_master.item_name
                ,item_master.maker_name
                ,item_master.spec
                ,item_master.rack_no
                ,achievement.achievement_quantity
                ,achievement.remarks
                ,achievement.order_seiban
                ,achievement.stock_seiban
                ,received_number
                ,received_customer_no
                ,received_customer_name
                ,received_estimate_number
                ,received_worker_name
                ,received_section_name
                ,order_detail.order_no
                ,achievement.product_price
                ,work_minute
                ,case when achievement.achievement_quantity <> 0 then 
                    achievement.work_minute / achievement.achievement_quantity
                    end as work_minute_per_unit 
                ,break_minute
                ,achievement.location_id
                ,case when order_detail_completed or order_process.process_completed then '" . _g("完了") . "' else '' end as order_detail_completed
                ,order_process.machining_sequence +1 as machining_sequence
                ,process_name
                ,location_name
                ,section_code
                ,section_name
                ,equip_code
                ,equip_name
                ,worker_code
                ,worker_name
                ,waster_qty
                ,measure
                ,item_process_master.default_work_minute
                ,achievement.achievement_quantity * item_process_master.default_work_minute as default_work_minute_total
                ,order_process.charge_price
                ,achievement.work_minute * order_process.charge_price as workcost
                ,item_process_master.overhead_cost
                ,item_process_master.process_remarks_1
                ,item_process_master.process_remarks_2
                ,item_process_master.process_remarks_3
                ,to_char(begin_time,'HH24:MI') as begin_time
                ,to_char(end_time,'HH24:MI') as end_time
                ,coalesce(order_process.order_process_no,'') as order_process_no
                ,case when achievement.location_id =-1 then '-1' else coalesce(location_code,'') end as location_code_csv
                ,case when achievement.child_location_id =-1 then '-1' else coalesce(child_location_code,'') end as child_location_code_csv
                ,work_minute as work_minute_csv
                ,break_minute as break_minute_csv
                ,case when order_detail_completed or order_process.process_completed then 1 else null end as completed_csv
                ,achievement.cost_1
                ,achievement.cost_2
                ,achievement.cost_3
                ,item_master.comment
                ,item_master.comment_2
                ,item_master.comment_3
                ,item_master.comment_4
                ,item_master.comment_5
        ";
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            $this->selectQuery .= "
                ,waster_code_{$i}
                ,waster_name_{$i}
                ,waster_quantity_{$i}
            ";
        }
        $this->selectQuery .= "

                ,coalesce(achievement.record_update_date, achievement.record_create_date) as gen_record_update_date
                ,coalesce(achievement.record_updater, achievement.record_creator) as gen_record_updater

            from
                achievement
                inner join item_master on achievement.item_id = item_master.item_id
                left join item_process_master on achievement.item_id = item_process_master.item_id and achievement.process_id = item_process_master.process_id
                left join (select order_detail_id, order_no, order_detail_completed, seiban from order_detail) as order_detail
                   on achievement.order_detail_id = order_detail.order_detail_id
                left join (select location_id as lid, location_code, location_name from location_master) as t_loc on achievement.location_id = t_loc.lid
                left join (select location_id as lid, location_code as child_location_code, location_name as child_location_name from location_master) as t_child_loc on achievement.child_location_id = t_child_loc.lid
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
                ) as t_rec on achievement.order_seiban = t_rec.s2
                left join order_process on achievement.order_detail_id = order_process.order_detail_id and achievement.process_id = order_process.process_id
                left join process_master on order_process.process_id = process_master.process_id
                left join section_master on achievement.section_id = section_master.section_id
                left join equip_master on achievement.equip_id = equip_master.equip_id
                left join worker_master on achievement.worker_id = worker_master.worker_id
                left join (select achievement_id as aid, sum(waster_quantity) as waster_qty from waster_detail group by achievement_id) as t_waster on achievement.achievement_id = t_waster.aid
                left join (
                    select
                        achievement_id
                        ";
                        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
                            $this->selectQuery .= "
                                ,MAX(case when line_number = {$i} then waster_code end) as waster_code_{$i}
                                ,MAX(case when line_number = {$i} then waster_name end) as waster_name_{$i}
                                ,MAX(case when line_number = {$i} then waster_quantity end) as waster_quantity_{$i}
                            ";
                        }
                        $this->selectQuery .= "
                    from
                        waster_detail
                        inner join waster_master on waster_detail.waster_id = waster_master.waster_id
                    group by
                        achievement_id
                    ) as t_waster_detail
                    on achievement.achievement_id = t_waster_detail.achievement_id
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'achievement_date desc, order_no, machining_sequence';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "" , "achievement_id"),
            array("process_master", "", "", "achievement_id"),
            array("section_master", "", "", "achievement_id"),
            array("equip_master", "", "", "achievement_id"),
            array("worker_master", "", "", "achievement_id"),
        );        
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("実績");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）");
        $form['gen_allowUpdateCheck'] = false;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('製造日'),
                'field' => 'achievement_date',
            ),
            array(
                'label' => _g('製造開始時刻'),
                'field' => 'begin_time',
            ),
            array(
                'label' => _g('製造終了時刻'),
                'field' => 'end_time',
            ),
            array(
                'label' => _g('休憩時間（分）'),
                'field' => 'break_minute',
                'exportField' => 'break_minute_csv',
            ),
            array(
                'label' => _g('製造時間（分）'),
                'field' => 'work_minute',
                'exportField' => 'work_minute_csv',
            ),
            array(
                'label' => _g('製造数量'),
                'field' => 'achievement_quantity',
            ),
            array(
                'label' => _g('実績登録コード'),
                'field' => 'order_process_no',
            ),
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
            ),
            array(
                'label' => _g('設備コード'),
                'field' => 'equip_code',
            ),
            array(
                'label' => _g('作業者コード'),
                'field' => 'worker_code',
            ),
            array(
                'label' => _g('ロケーションコード(完成品入庫)'),
                'addLabel' => sprintf(_g('(空欄：「%s」、-1：「(標準受入ロケ)」)'), _g(GEN_DEFAULT_LOCATION_NAME)),
                'field' => 'location_code',
                'exportField' => 'location_code_csv',
            ),
            array(
                'label' => _g('ロケーションコード(使用部材出庫)'),
                'addLabel' => sprintf(_g('(空欄：「%s」、-1：「(各部材の標準使用ロケ)」)'), _g(GEN_DEFAULT_LOCATION_NAME)),
                'field' => 'child_location_code',
                'exportField' => 'child_location_code_csv',
            ),
            array(
                'label' => _g('ロット番号'),
                'field' => 'lot_no',
            ),
            array(
                'label' => _g('消費期限'),
                'field' => 'use_by',
            ),
            array(
                'label' => _g('使用ロット番号'),
                'field' => 'use_lot_no',
            ),
            array(
                'label' => _g('製造経費1'),
                'field' => 'cost_1',
            ),
            array(
                'label' => _g('製造経費2'),
                'field' => 'cost_2',
            ),
            array(
                'label' => _g('製造経費3'),
                'field' => 'cost_3',
            ),
            array(
                'label' => _g('完了'),
                'addLabel' => _g('(1なら完了)'),
                'field' => 'order_detail_completed',
                'exportField' => 'completed_csv',
            ),
            array(
                'label' => _g('実績備考'),
                'field' => 'remarks',
            ),
        );
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            $form['gen_csvArray'][] = array(
                'label' => _g('不適合理由コード' . $i),
                'field' => 'waster_code_' . $i,
            );
            $form['gen_csvArray'][] = array(
                'label' => _g('不適合数' . $i),
                'field' => 'waster_quantity_' . $i,
            );
        }
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("実績登録");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Achievement_List";
        $form['gen_editAction'] = "Manufacturing_Achievement_Edit";
        $form['gen_idField'] = 'achievement_id';
        $form['gen_idFieldForUpdateFile'] = "achievement.achievement_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("実績登録");

        $form['gen_isClickableTable'] = "true";

        $form['gen_goLinkArray'] = array(
            array(
                'id' => 'bulkEdit',
                'value' => _g('一括登録'),
                'onClick' => "javascript:location.href='index.php?action=Manufacturing_Achievement_BulkEdit'",
            ),
            array(
                'id' => 'barcodeAccept',
                'value' => _g('バーコード登録'),
                'onClick' => "javascript:gen.modal.open('index.php?action=Manufacturing_Achievement_BarcodeEdit')",
            )
        );

        $form['gen_javascript_noEscape'] = "
            function showItemMaster(itemId) {
                gen.modal.open('index.php?action=Master_Item_Edit&item_id=' + itemId);
            }
        ";
        
        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Manufacturing_Achievement_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('製造日'),
                'field' => 'achievement_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'width' => '80',
                'align' => 'center',
            ),
        );

        $form['gen_columnArray'] = array(
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
                'label' => _g('工順'),
                'field' => 'machining_sequence',
                'width' => '37',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('工程'),
                'field' => 'process_name',
                'hide' => true,
            ),
            // 以前は「製番（オーダー）」と「製番（計画）」に分かれていたが、計画登録で製番品目の登録ができなくなった
            // ため、両者が異なることはなくなった。ag.cgi?page=ProjectDocView&pid=1574&did=227601
            array(
                'label_noEscape' => _g('製番'),
                'field' => 'order_seiban',
                'width' => '100',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '80',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、なおかつ所要量計算結果画面から発行されたオーダーの実績のみ受注番号が表示されます。MRP品目は受注とオーダーの結びつきがないため、受注番号を表示できません。'),
                'hide' => true,
            ),
            array(
                'label' => _g('入庫ロケーション'),
                'field' => 'location_name',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('数量'),
                'field' => 'achievement_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('開始時刻'),
                'field' => 'begin_time',
                'width' => '60',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('終了時刻'),
                'field' => 'end_time',
                'width' => '60',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('休憩時間（分）'),
                'field' => 'break_minute',
                'width' => '80',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('製造時間（分）'),
                'field' => 'work_minute',
                'width' => '80',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('製造時間／個'),
                'field' => 'work_minute_per_unit',
                'width' => '80',
                'type' => 'numeric',
                'helpText_noEscape' => _g('製造時間（分） ÷ 製造数量 です。'),
                'hide' => true,
            ),
            array(
                'label' => _g('標準加工時間（分）'),
                'field' => 'default_work_minute_total',
                'type' => 'numeric',
                'width' => '120',
                'helpText_noEscape' => _g('品目マスタ「標準加工時間（分）」× 製造数量 です。'),
                'hide' => true,
            ),
            array(
                'label' => _g('標準加工時間／個'),
                'field' => 'default_work_minute',
                'type' => 'numeric',
                'width' => '120',
                'helpText_noEscape' => _g('品目マスタ「標準加工時間（分）」です。'),
                'hide' => true,
            ),
            array(
                'label' => _g('工賃'),
                'field' => 'charge_price',
                'type' => 'numeric',
                'helpText_noEscape' => _g('製造指示書作成時の工賃です。'),
                'hide' => true,
            ),
            array(
                'label' => _g('作業工賃'),
                'field' => 'workcost',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('固定経費'),
                'field' => 'overhead_cost',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('製造経費1'),
                'field' => 'cost_1',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('製造経費2'),
                'field' => 'cost_2',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('製造経費3'),
                'field' => 'cost_3',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('ロット番号'),
                'field' => 'lot_no',
                'width' => '80',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label'=>_g('消費期限'),
                'field'=>'use_by',
                'width'=>'80',
                'type'=>'date',
                'hide' => true,
            ),
            array(
                'label' => _g('設備'),
                'field' => 'equip_name',
                'hide' => true,
            ),
            array(
                'label' => _g('作業者'),
                'field' => 'worker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('部門'),
                'field' => 'section_name',
                'hide' => true,
            ),
            array(
                'label' => _g('不適合数'),
                'field' => 'waster_qty',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('使用ロット番号'),
                'field' => 'use_lot_no',
                'width' => '80',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('完了'),
                'field' => 'order_detail_completed',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g('受注得意先コード'),
                'field' => 'received_customer_no',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'hide' => true,
            ),
            array(
                'label' => _g('受注得意先名'),
                'field' => 'received_customer_name',
                'width' => '150',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'hide' => true,
            ),
            array(
                'label' => _g('見積書番号'),
                'field' => 'received_estimate_number',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'hide' => true,
            ),
            array(
                'label' => _g('受注担当者（自社）'),
                'field' => 'received_worker_name',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'hide' => true,
            ),
            array(
                'label' => _g('受注部門（自社）'),
                'field' => 'received_section_name',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、かつ受注製番と結び付けられたオーダーである場合のみ表示されます。'),
                'hide' => true,
            ),
            array(
                'label' => _g('実績備考'),
                'field' => 'remarks',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'comment',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'comment_2',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'comment_3',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'comment_4',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'comment_5',
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ1'),
                'field' => 'process_remarks_1',
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ2'),
                'field' => 'process_remarks_2',
                'hide' => true,
            ),
            array(
                'label' => _g('工程メモ3'),
                'field' => 'process_remarks_3',
                'hide' => true,
            ),
        );
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            $form['gen_columnArray'][] = array(
                'label' => _g('不適合理由コード') . $i,
                'field' => 'waster_code_' . $i,
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('不適合理由名') . $i,
                'field' => 'waster_name_' . $i,
                'hide' => true,
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('不適合数') . $i,
                'field' => 'waster_quantity_' . $i,
                'type' => 'numeric',
                'hide' => true,
            );
        }
    }

}
