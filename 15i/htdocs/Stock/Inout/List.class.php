<?php

class Stock_Inout_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
            ),
            array(
                'label' => _g('日付'), // カレンダーとセレクタが重なるとIE6で表示が乱れる。位置に注意
                'field' => 'item_in_out_date',
                'type' => 'dateFromTo',
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('ロケーション'),
                'field' => 'item_in_out___location_id',
                'type' => 'select',
                'options' => $option_location_group,
                'hide' => true,
            ),
            array(
                'label' => _g('入出庫備考'),
                'field' => (@$form['classification'] == 'use' ? "t_ach___remarks" : "item_in_out___remarks"),
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'type' => 'hidden',
                'field' => 'classification',
            ),
            // 受払画面のリンクではidが指定される
            array(
                'type' => 'hidden',
                'field' => 'item_in_out_id',
            ),
        );
        // 支給先検索
        if (@$form['classification'] == "payout") {
            $form['gen_searchControlArray'][] = array(
                'label' => _g('支給先コード/名'),
                'field' => 'partner_no',
                'field2' => 'customer_name',
            );
            $form['gen_searchControlArray'][] = array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            );
        }
        // 使用数リスト
        if (@$form['classification'] == "use") {
            $form['gen_searchControlArray'][] = array(
                'label' => _g('オーダー番号'),
                'field' => 't_ach_order___order_no',
            );
        }
        // このクラスの特別処理。classificationの決定
        if (@$form['classification'] == "in" || @$form['classification'] == "out" ||
                @$form['classification'] == "payout" ||
                @$form['classification'] == "use") {
            // URLでパラメータclassificationが渡されているときは、それを使う
            $form['gen_search_classification'] = $form['classification'];
        } else {
            // URLでパラメータclassificationが渡されていないとき（ページングのとき）は、検索条件から読み出す
            $this->searchConditionDefault["gen_search_classification"] = "in";
        }

        //  同じクラスを引数切り替えで複数のページとして扱う場合、ページごとに検索条件や列情報（列順、列幅、ソートなど）を別々に保持できるよう、次の設定が必要。
        //  （gen_columnModeはsetViewParam()で設定するが、gen_pageModeはListBaseでの処理順の関係で、setSearchCondition()で
        //   行う必要がある。）
        $form['gen_pageMode'] = @$form['gen_search_classification'];

        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("品目別 日次数量（当月）") => self::_getPreset("5", @$form['gen_search_classification'], "item_in_out_date_day", "item_name"),
                _g("品目別 月次数量（当年）") => self::_getPreset("7", @$form['gen_search_classification'], "item_in_out_date_month", "item_name"),
                _g("ロケ別 数量（当月）") => self::_getPreset("5", @$form['gen_search_classification'], "location_name", "item_name"),
                _g("ロケ別 数量（当年）") => self::_getPreset("7", @$form['gen_search_classification'], "location_name", "item_name"),
            );
    }
    
    function _getPreset($datePattern, $classification, $horiz, $vert, $orderby = "", $value = "item_in_out_quantity", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "item_in_out_date", "dp" => $datePattern),
                    array("f" => "classification", "v" => $classification),
                    
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
        // 検索条件から読み出したclassificationを、パラメータclassificationに設定する。
        // この処理が関係あるのはURLでパラメータclassificationが指定されなかったとき（ページングのとき）だけ。
        // このクラス内ではclassificationを使用している箇所はないとはいえ、設定しておかないとメニュー表示が乱れる。
        $form['classification'] = $form['gen_search_classification'];
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                item_in_out_id
                ,item_in_out_date
                ,item_code
                ,item_name
                ,location_code
                ,location_name
                ,item_in_out_quantity
                ,measure
                ,t_detail.order_no
                " . ($form['classification'] == 'out' ? "
                    ,item_in_out.seiban
                    ,item_in_out.stock_amount
                    " : "") . "
                " . ($form['classification'] == 'use' ? "
                    ,t_ach_order.order_no as order_no_for_use
                    ,parent_item_code
                    " : "") . "
                " . ($form['classification'] == 'payout' ? "
                    ,item_in_out.item_price
                    ,item_in_out.item_price * item_in_out_quantity as payout_amount
                    ,partner_no
                    ,customer_name
                    ,item_in_out.without_stock
                    ,case when item_in_out.without_stock = 1 then 'yes' else '' end as without_stock_show
                    " : "") . "
                ," . ($form['classification'] == 'use' ? "t_ach.remarks" : "item_in_out.remarks") . " as remarks
                ,'{$form['classification']}' as classification

                ,coalesce(item_in_out.record_update_date, item_in_out.record_create_date) as gen_record_update_date
                ,coalesce(item_in_out.record_updater, item_in_out.record_creator) as gen_record_updater

                ,item_master.spec as item_spec
                ,item_master.comment as item_remarks_1
                ,item_master.comment_2 as item_remarks_2
                ,item_master.comment_3 as item_remarks_3
                ,item_master.comment_4 as item_remarks_4
                ,item_master.comment_5 as item_remarks_5

            from
                item_in_out
                left join item_master on item_in_out.item_id = item_master.item_id
                left join location_master on item_in_out.location_id = location_master.location_id
                left join (select order_detail_id as oid, order_no from order_detail) as t_detail on item_in_out.order_detail_id = t_detail.oid
                left join (select customer_id as partner_id, customer_no as partner_no, customer_name from customer_master) as t_customer on item_in_out.partner_id = t_customer.partner_id
                " . ($form['classification'] == 'use' ? "
                    left join (select achievement_id as aid, order_detail_id as oid, remarks from achievement) as t_ach on item_in_out.achievement_id = t_ach.aid
                    left join (select order_detail_id as oid, order_no from order_detail) as t_ach_order on t_ach.oid = t_ach_order.oid
                    left join (select item_id as iid, item_code as parent_item_code from item_master) as t_parent_item on item_in_out.parent_item_id = t_parent_item.iid
                " : "") . "
            [Where]
                /* 受入データは入庫画面に表示しないようにした。入庫画面で受入のitem_in_outを修正されると、acceptedと矛盾するため */
                " . ($form['classification'] != 'payout' && $form['classification'] != 'use' ? "and accepted_id is null" : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'item_in_out_date desc,item_code';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "item_in_out_id"),
            array("location_master", "", "", "item_in_out_id"),
        );        
    }

    function setCsvParam(&$form)
    {
        // 使用数（use）はsetViewParamでCSVを無効にしている

        $form['gen_importLabel'] = $form['classification'] == "payout" ? _g("支給") : _g("入出庫");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）") . "<br><br>";
        if ($form['classification'] == "payout") {
            $form['gen_importMsg_noEscape'] .= _g("※フォーマットは次のとおりです。") . "<br>" .
                    _g("　　　種別,日付,品目コード,（ロケーションコード）,数量,（入出庫備考）,") . "<br>" .
                    _g("　　　発注先コード,支給単価,1なら在庫から引き落とさない") . "<br><br>" .
                    _g("　　　（　）内は空欄にすることもできます。 ");
        } else {
            $form['gen_importMsg_noEscape'] .= _g("※入庫・出庫を同時に登録可能です。フォーマットは次のとおりです。") . "<br>" .
                    _g("　　　種別,日付,品目コード,（ロケーションコード）,数量,（入出庫備考）") . "<br><br>" .
                    _g("　　　（　）内は空欄にすることもできます。") . "<br><br>" .
                    _g("※入庫の場合は、種別を in  と入力してください。") . "<br>" .
                    _g("※出庫の場合は、種別を out と入力してください。");
        }
        $form['gen_allowUpdateCheck'] = false;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('種別'),
                'addLabel' => $form['classification'] == "payout" ? _g("(payout)") : _g('（in:入庫、out:出庫）'),
                'field' => 'classification',
            ),
            array(
                'label' => _g('日付'),
                'field' => 'item_in_out_date',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => ($form['classification'] == "payout" ? _g('出庫ロケーションコード') : _g("ロケーションコード")),
                'field' => 'location_code',
            ),
            array(
                'label' => _g('数量'),
                'field' => 'item_in_out_quantity',
            ),
            array(
                'label' => _g('入出庫備考'),
                'field' => 'remarks',
            ),
        );
        switch ($form['classification']) {
            case "out":
                $form['gen_csvArray'][] = array(
                    'label' => _g('製番'),
                    'field' => 'seiban',
                );
                $form['gen_csvArray'][] = array(
                    'label' => _g('出庫金額'),
                    'field' => 'stock_amount',
                );
                break;
            case "payout":
                $form['gen_csvArray'][] = array(
                    'label' => _g('発注先コード'),
                    'field' => 'partner_no',
                );
                $form['gen_csvArray'][] = array(
                    'label' => _g('支給単価'),
                    'field' => 'item_price',
                );
                $form['gen_csvArray'][] = array(
                    'label' => _g('1なら在庫から引き落とさない'),
                    'field' => 'without_stock',
                );
                break;
        }
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = Logic_Inout::classificationToTitle($form['gen_search_classification'], false);
        $form['gen_listAction'] = "Stock_Inout_List&classification={$form['classification']}";
        $form['gen_editAction'] = "Stock_Inout_Edit&classification={$form['classification']}";
        $form['gen_deleteAction'] = "Stock_Inout_Delete&classification={$form['classification']}";
        $form['gen_idField'] = 'item_in_out_id';
        $form['gen_idFieldForUpdateFile'] = "item_in_out.item_in_out_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        switch ($form['classification']) {
            case "in":
                $form['gen_pageHelp'] = _g("入庫登録");
                break;
            case "out":
                $form['gen_pageHelp'] = _g("出庫登録");
                break;
            case "payout":
                $form['gen_pageHelp'] = _g("支給");
                break;
            default:
                break;
        }

        // このクラスでは上記のようにaction名にclassificationを入れて画面を区別しているが、
        // エクセル出力の際に画面ごとの列表示/非表示設定を反映するにはこの設定が必要。
        $form['gen_pageMode'] = $form['classification'];

        $form['gen_csvAddParam_noEscape'] = "&classification={$form['classification']}";

        $start_date = Logic_SystemDate::getStartDateString();
        if (!isset($form['gen_search_item_in_out_date_from'])
                || strtotime($form['gen_search_item_in_out_date_from']) < strtotime($start_date)) {
            $form['gen_message_noEscape'] = _g("ロックされている月のデータは修正・削除を行えません。");
        }

        // 使用数管理は読み取り専用
        if ($form['classification'] == "use") {
            $form['gen_hideNewRecordButton'] = true;
            $form['gen_noCsv'] = true;
        } else {
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
                    'deleteAction' => 'Stock_Inout_BulkDelete',
                    // readonlyであれば表示しない
                    'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " and '[item_in_out_date]' >= '$start_date'",
                ),
            );
        }

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('日付'),
                'field' => 'item_in_out_date',
                'type' => 'date',
                'width' => '80',
                'align' => 'center',
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
                'label' => ($form['classification'] == "payout" ? _g('出庫ロケーション') : _g("ロケーション")),
                'field' => 'location_name',
                'hide' => true,
            ),
            array(
                'label' => _g('数量'),
                'field' => 'item_in_out_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '90',
                'align' => 'center',
                'visible' => ($form['classification'] == "out"), // 出庫画面のみ
            ),
            array(
                'label' => _g('出庫金額'),
                'field' => 'stock_amount',
                'width' => '90',
                'type' => 'numeric',
                'helpText_noEscape' => _g("製番が指定されている場合のみ表示されます。詳細は編集画面の「出庫金額」のチップヘルプをご覧ください。"),
                'visible' => ($form['classification'] == "out"), // 出庫画面のみ
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no_for_use',
                'width' => '90',
                'align' => 'center',
                'visible' => ($form['classification'] == "use"), // 使用数画面のみ
            ),
            array(
                'label' => _g('支給単価'),
                'field' => 'item_price',
                'width' => '70',
                'type' => 'numeric',
                'visible' => ($form['classification'] == "payout"), // 支給画面のみ
                'hide' => true,
            ),
            array(
                'label' => _g('支給金額'),
                'field' => 'payout_amount',
                'width' => '70',
                'type' => 'numeric',
                'visible' => ($form['classification'] == "payout"), // 支給画面のみ
                'hide' => true,
            ),
            array(
                'label' => _g('支給先コード'),
                'field' => 'partner_no',
                'visible' => ($form['classification'] == "payout"), // 支給画面のみ
            ),
            array(
                'label' => _g('支給先名'),
                'field' => 'customer_name',
                'visible' => ($form['classification'] == "payout"), // 支給画面のみ
            ),
            array(
                'label' => _g('引落さない'),
                'field' => 'without_stock_show',
                'width' => '70',
                'align' => 'center',
                'visible' => ($form['classification'] == "payout"), // 支給画面のみ
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'width' => '90',
                'align' => 'center',
                'visible' => ($form['classification'] == "payout"), // 支給画面のみ
            ),
            array(
                'label' => _g('入出庫備考'),
                'field' => 'remarks',
                'helpText_noEscape' => ($form['classification'] == 'use' ? _g("実績登録の備考が表示されています。") : ""),
                'hide' => true,
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'item_spec',
                'helpText_noEscape' => _g("品目マスタの「仕様」が表示されます。"),
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
        );
    }

}