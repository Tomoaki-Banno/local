<?php

class Partner_Accepted_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'accepted___order_no',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'order_seiban',
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
                'field' => 'item_master___item_group_id',
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
                'label' => _g('発送先コード/名'),
                'field' => 'delivery_partner_no',
                'field2' => 'delivery_partner_name',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者コード/名'),
                'field' => 'worker_code',
                'field2' => 'worker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('入庫ロケーション'),
                'type' => 'select',
                'field' => 'location_id',
                'options' => $option_location_group,
                'hide' => true,
            ),
            array(
                'label' => _g('ロット番号'),
                'field' => 'accepted___lot_no',
                'hide' => true,
            ),
            array(
                'label' => _g('部門コード/名'),
                'field' => 'section_code',
                'field2' => 'section_name',
                'hide' => true,
            ),
            array(
                'label' => _g('受入日'),
                'type' => 'dateFromTo',
                'field' => 'accepted_date',
                'defaultFrom' => date('Y-m-01'),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('検収日'),
                'type' => 'dateFromTo',
                'field' => 'inspection_date',
                'rowSpan' => 2,
                'hide' => true,
            ),
            array(
                'label' => _g('受入備考'),
                'field' => 'accepted___remarks',
                'ime' => 'on',
                'hide' => true,
            ),
        );
        
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("日次受入額（当月）") => self::_getPreset("5", "gen_all", "accepted_date_day", ""),
                _g("月次受入額（今年）") => self::_getPreset("7", "gen_all", "accepted_date_month", ""),
                _g("受入額 前年対比") => self::_getPreset("0", "accepted_date_month", "accepted_date_year", ""),
                _g("発注先受入ランキング（今年）") => self::_getPreset("7", "gen_all", "customer_name", "order by field1 desc"),
                _g("品目受入ランキング（今年）") => self::_getPreset("7", "gen_all", "item_name", "order by field1 desc"),
                _g("担当者受入ランキング（今年）") => self::_getPreset("7", "gen_all", "worker_name", "order by field1 desc"),
                _g("部門受入ランキング（今年）") => self::_getPreset("7", "gen_all", "section_name", "order by field1 desc"),
                _g("発注先 - 品目（今年）") => self::_getPreset("7", "customer_name", "item_name", ""),
                _g("発注先別月次受入額（今年）") => self::_getPreset("7", "accepted_date_month", "customer_name", ""),
                _g("品目別月次受入額（今年）") => self::_getPreset("7", "accepted_date_month", "item_name", ""),
                _g("品目別月次受入数量（今年）") => self::_getPreset("7", "accepted_date_month", "item_name", "", "accepted_quantity"),
                _g("担当者別月次受入額（今年）") => self::_getPreset("7", "accepted_date_month", "worker_name", ""),
                _g("部門別月次受入額（今年）") => self::_getPreset("7", "accepted_date_month", "section_name", ""),
                _g("データ入力件数（今年）") => self::_getPreset("7", "accepted_date_month", "gen_record_updater", "", "gen_record_updater", "count"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby = "", $value = "accepted_amount", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "accepted_date", "dp" => $datePattern),
                    
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
                accepted_id
                ,accepted_date
                ,inspection_date
                ,accepted.lot_no
                ,customer_no
                ,customer_name
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,delivery_partner_no
                ,delivery_partner_name
                ,order_id_for_user
                ,accepted.order_no
                ,order_header.order_date
                ,order_detail.item_id
                ,order_detail.item_code
                ,order_detail.item_name
                ,t_item_group_1.item_group_code as item_group_code_1
                ,t_item_group_1.item_group_name as item_group_name_1
                ,t_item_group_2.item_group_code as item_group_code_2
                ,t_item_group_2.item_group_name as item_group_name_2
                ,t_item_group_3.item_group_code as item_group_code_3
                ,t_item_group_3.item_group_name as item_group_name_3
                ,maker_name
                ,spec
                ,rack_no
                ,item_master.comment as item_remarks_1
                ,item_master.comment_2 as item_remarks_2
                ,item_master.comment_3 as item_remarks_3
                ,item_master.comment_4 as item_remarks_4
                ,item_master.comment_5 as item_remarks_5
                ,currency_name
                ,accepted.accepted_quantity
                ,order_detail_quantity
                ,measure
                ,accepted_price
                ,accepted_amount
                ,accepted_tax
                ,accepted.foreign_currency_rate
                ,foreign_currency_accepted_price
                ,foreign_currency_accepted_amount
                ,accepted.use_by
                ,accepted.remarks
                ,order_seiban
                ,stock_seiban
                ,received_number
                ,case when order_detail_completed then '" . _g("完了") . "' else '' end as order_detail_completed
                ,location_id
                ,location_name
                ,case when accepted.location_id =-1 then '-1' else coalesce(location_code,'') end as location_code_csv
                ,case when order_detail_completed then 1 else null end as completed_csv
                ,payment_date
                ,worker_code
                ,worker_name
                ,section_code
                ,section_name
                ,order_header.remarks_header as order_remarks

                ,case
                    when accepted.payment_report_timing = 0 then '" . _g('受入日') . "'
                    when accepted.payment_report_timing = 1 then '" . _g('検収日') . "' end as timing_show
                ,case accepted.rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '' end as rounding_show
                ,accepted.tax_rate

                ,coalesce(accepted.record_update_date, accepted.record_create_date) as gen_record_update_date
                ,coalesce(accepted.record_updater, accepted.record_creator) as gen_record_updater

            from
                accepted
                /* 注文の明細カスタム項目を受入に出すためには、order_detailをサブクエリにせずそのままJOINする必要がある */
                left join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                left join order_header on order_detail.order_header_id = order_header.order_header_id
                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join (select customer_id as cid, customer_no as delivery_partner_no, customer_name as delivery_partner_name from customer_master) as t_delivery_partner on order_header.delivery_partner_id = t_delivery_partner.cid
                left join worker_master on order_header.worker_id = worker_master.worker_id
                left join section_master on order_header.section_id = section_master.section_id
                left join item_master on order_detail.item_id = item_master.item_id
                left join (select location_id as lid, location_code, location_name from location_master) as t_loc on accepted.location_id = t_loc.lid
                left join (select seiban as s2, received_number from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id) as t_rec on accepted.order_seiban = t_rec.s2 and accepted.order_seiban<>''
                left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id
                left join item_group_master as t_item_group_1 on item_master.item_group_id = t_item_group_1.item_group_id
                left join item_group_master as t_item_group_2 on item_master.item_group_id_2 = t_item_group_2.item_group_id
                left join item_group_master as t_item_group_3 on item_master.item_group_id_3 = t_item_group_3.item_group_id
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
                and order_header.classification = 1
            [Orderby]
        ";

        $this->orderbyDefault = 'accepted_date desc, order_id_for_user, accepted.order_no, location_id';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("order_header", "", "Partner_Order", "accepted_id", true),
            array("customer_master", "", "", "accepted_id"),
            array("worker_master", "", "", "accepted_id"),
            array("section_master", "", "", "accepted_id"),
            array("item_master", "", "", "accepted_id"),
        );        
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("受入");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）");
        $form['gen_allowUpdateCheck'] = false;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('受入日'),
                'field' => 'accepted_date',
            ),
            array(
                'label' => _g('検収日'),
                'field' => 'inspection_date',
            ),
            array(
                'label' => _g('受入数量'),
                'field' => 'accepted_quantity',
            ),
            array(
                'label' => _g('入庫ロケーションコード'),
                'addLabel' => sprintf(_g('(空欄：「%s」、-1：「(品目の標準ロケ)」)'), _g(GEN_DEFAULT_LOCATION_NAME)),
                'field' => 'location_code',
                'exportField' => 'location_code_csv',
            ),
            array(
                'label' => _g('支払予定日'),
                'field' => 'payment_date',
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
                'label' => _g('完了'),
                'addLabel' => _g('(1なら完了)'),
                'field' => 'order_detail_completed',
                'exportField' => 'completed_csv',
            ),
            array(
                'label' => _g('受入備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("注文受入登録");
        $form['gen_menuAction'] = "Menu_Partner";
        $form['gen_listAction'] = "Partner_Accepted_List";
        $form['gen_editAction'] = "Partner_Accepted_Edit";
        $form['gen_idField'] = 'accepted_id';
        $form['gen_idFieldForUpdateFile'] = "accepted.accepted_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("注文受入");

        $form['gen_isClickableTable'] = "true";

        $form['gen_checkAndDoLinkArray'] = array(
            array(
                'id' => 'bulkInspectionDate',
                'value' => _g('一括検収登録'),
                'onClick' => "javascript:bulkInspection();",
            ),
        );

        $form['gen_goLinkArray'] = array(
            array(
                'id' => 'bulkEdit',
                'value' => _g('一括受入登録'),
                'onClick' => "javascript:location.href='index.php?action=Partner_Accepted_BulkEdit'",
            ),
            array(
                'id' => 'barcodeAccept',
                'value' => _g('バーコード登録'),
                'onClick' => "javascript:gen.modal.open('index.php?action=Partner_Accepted_BarcodeEdit')",
            ),
        );

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("注文受入一覧 印刷"),
                'link' => "javascript:gen.list.printReport('Partner_Accepted_Report','print_check')",
                'reportEdit' => 'Partner_Accepted_Report'
            ),
        );

        $form['gen_javascript_noEscape'] = "
            function bulkInspection(){
                var inputDate = '';
                var frm = gen.list.table.getCheckedPostSubmit('check');
                if (frm.count == 0) {
                   alert('" . _g("一括検収するデータを選択してください。") . "');
                } else {
                    while(true) {
                    	inputDate = window.prompt('" . _g('検収日を入力してください（空欄にすると検収日の登録を削除します）') . "\\n" .
                            _g('仕入計上基準が“検収日”の場合、検収日のレートが適用されます。') . "', gen.date.getCalcDateStr(0));
                    	if (inputDate===null) return;
                    	if (inputDate=='' || gen.date.isDate(inputDate)) break;
                        alert('" . _g('日付が正しくありません。') . "');
                    }
                    if (inputDate == '') {
                        if (!confirm('" . _g('日付が指定されていませんので、検収日の登録が削除されます。本当に実行してよろしいですか？') . "')) {
                            return;
                        }
                    }
                    var postUrl = 'index.php?action=Partner_Accepted_BulkInspection';
                    postUrl += '&inspection_date=' + inputDate;
                    frm.submit(postUrl);
                    // 画面更新とwaitダイアログ表示。listUpdateによるAjax更新はBulkInspectionクラスの処理が終わるまで
                    // session_start()で足止めになるので、結果として処理が終わるまでダイアログが出たままとなる。
                    listUpdate(null, false);
                }
            }
            
            function showItemMaster(itemId) {
                gen.modal.open('index.php?action=Master_Item_Edit&item_id=' + itemId);
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#f9bdbd" => "'[accepted_quantity]'<'0'"     // マイナス伝票
        );

        $form['gen_colorSample'] = array(
            "f9bdbd" => array(_g("ピンク"), _g("赤伝票")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Partner_Accepted_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g("印刷"),
                'name' => 'print_check',
                'type' => 'checkbox',
            ),
            array(
                'label' => _g("検収"),
                'name' => 'check',
                'type' => 'checkbox',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('受入日'),
                'field' => 'accepted_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('検収日'),
                'field' => 'inspection_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
                'width' => '70',
                'align' => 'center',
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
                'label' => _g('発注日'),
                'field' => 'order_date',
                'type' => 'date',
                'hide' => true,
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
                'label' => _g('品目グループコード1'),
                'field' => 'item_group_code_1',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ名1'),
                'field' => 'item_group_name_1',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループコード2'),
                'field' => 'item_group_code_2',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ名2'),
                'field' => 'item_group_name_2',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループコード3'),
                'field' => 'item_group_code_3',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ名3'),
                'field' => 'item_group_name_3',
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
            // 以前は「製番（オーダー）」と「製番（計画）」に分かれていたが、計画登録で製番品目の登録ができなくなった
            // ため、両者が異なることはなくなった。ag.cgi?page=ProjectDocView&pid=1574&did=227601
            array(
                'label_noEscape' => _g('製番'),
                'field' => 'order_seiban',
                'width' => '100',
                'type' => 'data',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '80',
                'align' => 'center',
                'helpText_noEscape' => _g('製番品目で、なおかつ所要量計算結果画面から発行されたオーダーである場合のみ表示されます。MRP品目は受注とオーダーの結びつきがないため、受注番号を表示できません。'),
                'hide' => true,
            ),
            array(
                'label' => _g('入庫ロケーション'),
                'field' => 'location_name',
                'hide' => true,
            ),
            array(
                'label' => _g('受入数'),
                'field' => 'accepted_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('発注数'),
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
                'label' => _g('受入単価'),
                'field' => 'accepted_price',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('税率'),
                'field' => 'tax_rate',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('仕入計上基準'),
                'field' => 'timing_show',
                'width' => '90',
                'align' => 'center',
            ),
            array(
                'label' => _g('端数処理'),
                'field' => 'rounding_show',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('受入金額'),
                'field' => 'accepted_amount',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('消費税額'),
                'field' => 'accepted_tax',
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
                'label' => _g('受入単価(外貨)'),
                'field' => 'foreign_currency_accepted_price',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('受入金額(外貨)'),
                'field' => 'foreign_currency_accepted_amount',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('支払予定日'),
                'field' => 'payment_date',
                'width' => '80',
                'type' => 'date',
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
                'label' => _g('完了'),
                'field' => 'order_detail_completed',
                'width' => '50',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者コード'),
                'field' => 'worker_code',
                'width' => '80',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者名'),
                'field' => 'worker_name',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
                'width' => '80',
                'hide' => true,
            ),
            array(
                'label' => _g('部門名'),
                'field' => 'section_name',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('受入備考'),
                'field' => 'remarks',
                'hide' => true,
                'helpText_noEscape' => _g("デフォルトでは注文登録の「注文明細備考」（「注文備考」ではありません）が引き継がれます。編集画面で内容を修正することもできます。")
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'order_remarks',
                'hide' => true,
                'helpText_noEscape' => _g("注文登録の「注文備考」（「注文明細備考」ではありません）が表示されます。この画面で内容を修正することはできません。")
            ),
        );
    }

}
