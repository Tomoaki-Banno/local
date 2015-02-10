<?php

define("CHART_WIDTH", 700);
define("CHART_HEIGHT", 330);

class Stock_StockHistory_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        // 表示期間のtoだけ指定されているとき（在庫リストから日付指定で来たときはこの状態）
        if (@$form['gen_search_date_from'] == "" && @$form['gen_search_date_to'] != "") {
            $form['gen_search_date_from'] = date('Y-m-01', strtotime($form['gen_search_date_to']));
        }

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('品目'),
                'field' => 'temp_inout___item_id',
                'type' => 'dropdown',
                'size' => '150',
                'dropdownCategory' => 'item',
                'dropdownParam' => 'not_dummy', // ダミー品目の受払は正しく表示できない
                'rowSpan' => 2,
                'nosql' => true,
            ),
            array(
                'label' => _g("表示期間"),
                'field' => 'date',
                'type' => 'dateFromTo',
                'defaultFrom' => date('Y-m-01', strtotime('-3 month')),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'rowSpan' => 2,
                'nosql' => true,
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'notShowMatchBox' => true,
                'nosql' => true,
                'helpText_noEscape' => _g('空欄にするとフリー在庫と全ての製番在庫が対象になります。「製番なし」を対象にするときは「nothing」と入力します。'),
                'hide' => true,
            ),
            array(
                'label' => _g('ロット番号'),
                'field' => 'lot_no',
                'notShowMatchBox' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('ロケーション'),
                'field' => 'temp_inout___location_id',
                'type' => 'select',
                'options' => $option_location_group,
                'nosql' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('サプライヤー在庫'),
                'field' => 'include_partner_stock',
                'type' => 'select',
                'options' => Gen_Option::getTrueOrFalse('search-include'),
                'nosql' => true,
                'default' => 'true',
                'hide' => true,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        $fromDate = @$form['gen_search_date_from'];
        $toDate = @$form['gen_search_date_to'];
        if (!Gen_String::isDateString($fromDate))
            $fromDate = "1970-01-01";     // 指定されていなければずっと前から

        // 指定されていなければずっと先まで。（2038年以降は日付と認識されない）
        if (!Gen_String::isDateString($toDate))
            $toDate = "2037-12-31";
        $itemId = (isset($form['gen_search_temp_inout___item_id']) && Gen_String::isNumeric($form['gen_search_temp_inout___item_id']) ? $form['gen_search_temp_inout___item_id'] : -99999);  // 品目未指定でもいちおうtemp_inoutを作成しておく必要がある
        $seiban = (@$form['gen_search_seiban'] == 'nothing' ? '' : (@$form['gen_search_seiban'] == '' ? null : $form['gen_search_seiban']));
        $locationId = (!is_numeric(@$form['gen_search_temp_inout___location_id']) ? null : $form['gen_search_temp_inout___location_id']);
        $lotId = null;  // 未使用
        $isIncludePartnerStock = (@$form['gen_search_include_partner_stock'] == 'true');
        // temp_inout に情報取得
        Logic_Stock::createTempInoutTable($fromDate, $toDate, $itemId, $seiban, $locationId, $lotId, $isIncludePartnerStock, true, false, true, false);
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                temp_inout.*
                ,location_name
                ,measure
                ,lot_no
                ,use_by
                ,coalesce(date, '1970-01-01') as date_for_orderby     /* 並べ替えで先頭に持ってくるためのダミーデータ */
            from
                temp_inout
                left join item_master on temp_inout.item_id = item_master.item_id
                left join location_master on temp_inout.location_id = location_master.location_id
                /* ロット番号/消費期限が表示されるのはロット品目のみとする。
                    この制限がないと、製番品目の受入/実績でロット番号/消費期限を登録した場合に、同じ製番のオーダーすべてに同じロット番号/消費期限が
                    表示されてしまうことになる。*/
                left join (select stock_seiban, use_by, lot_no from achievement where stock_seiban <> ''
                     union select stock_seiban, use_by, lot_no from accepted where stock_seiban <> ''
                     ) as t_ach_acc on temp_inout.seiban = t_ach_acc.stock_seiban and temp_inout.seiban <>'' and item_master.order_class = 2
            [Where]
                " . (!is_numeric(@$form['gen_search_temp_inout___item_id']) ? " and 1=0" : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'id';   // id順に処理されているので、その順で読む必要がある
        $this->pageRecordCount = 20;
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "item_id"),
            array("location_master", "", "", "location_id"),
        );        
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("受払履歴");
        $form['gen_listAction'] = "Stock_StockHistory_List";
        $form['gen_idField'] = "dummy";
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("受払履歴");

        if (is_numeric(@$form['gen_search_temp_inout___item_id'])) {
            $query = "select item_code || ' (' || item_name || ')' from item_master where item_id = '{$form['gen_search_temp_inout___item_id']}'";
            $form['gen_excelShowArray'] = array(array(0, 2, _g("品目：") . ' ' . $gen_db->queryOneValue($query)));
            $form['gen_excelDetailRow'] = 4;
        } else {
            $form['gen_message_noEscape'] = "<font color='red'>" . _g("品目を指定してください。") . '</font>';
        }

        // リンク。
        // なるべく表示条件をクリアした状態で表示
        $form['gen_javascript_noEscape'] = "
            function goData(link) {
                window.open('index.php?action=' + link + '&gen_searchConditionClear','history');
            }
        ";

        // ロケ・ロットが指定されたときは、入庫予定・出庫予定・有効在庫を表示しない
        // 受注や引当にロケ・ロットの概念がないため
        $isShowInoutPlan = (!is_numeric(@$form['gen_search_temp_inout___location_id']) && !is_numeric(@$form['gen_search_lot_id']));
        if (!$isShowInoutPlan)
            $form['gen_dataMessage_noEscape'] = _g("ロケーションが(すべて)以外の時は、入庫予定・出庫予定・有効在庫は表示されません。");

        // 明細リスト
        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('日付'),
                'field' => 'date',
                'width' => '85',
                'type' => 'date',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('ロット番号'),
                'field' => 'lot_no',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('ロケーション'),
                'field' => 'location_name',
                'width' => '70',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('項目'),
                'field' => 'description',
                'width' => '280',
            ),
            array(
                'label' => _g('元データ'),
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/document-arrow.png' class='gen_cell_img'>",
                'link' => "javascript:goData('[urlencode:link]')",
                'showCondition' => "'[link]'!=''",
                'align' => 'center',
                'width' => '60',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('入庫数'),
                'field' => 'in_qty',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g("前在庫の行については、前回棚卸から表示期間前日までの合計入庫数（製番・ロケーション別）が表示されています。前回棚卸による棚卸調整分（差数）も含んでいることに注意してください。"),
            ),
            array(
                'label' => _g('出庫数'),
                'field' => 'out_qty',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g("前在庫の行については、前回棚卸から表示期間前日までの合計出庫数（製番・ロケーション別）が表示されています。"),
            ),
            array(
                'label' => _g('理論在庫数'),
                'field' => 'logical_stock_quantity',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#ffcc99" => "true"), // 色付け条件。常にtrueになるようにしている
                'helpText_noEscape' => _g("すべての製番・ロケーションの合計の理論在庫数が表示されています。"),
            ),
            array(
                'label' => _g('入庫予定数'),
                // ロケ・ロット指定されているときは表示しない
                'field' => ($isShowInoutPlan ? "in_plan_qty" : ""),
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#cccccc" => ($isShowInoutPlan ? "false" : "true")),
            ),
            array(
                'label' => _g('出庫予定数'),
                // ロケ・ロット指定されているときは表示しない
                'field' => ($isShowInoutPlan ? "out_plan_qty" : ""),
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#cccccc" => ($isShowInoutPlan ? "false" : "true")),
            ),
            array(
                'label' => _g('有効在庫数'),
                'width' => '70',
                'type' => 'numeric',
                // ロケ・ロット指定されているときは表示しない
                'field' => ($isShowInoutPlan ? "available_stock_quantity" : ""),
                'colorCondition' => array("#cccccc" => ($isShowInoutPlan ? "false" : "true"), "#ffffcc" => "true"), // 色付け条件。常にtrueになるようにしている
            ),
            array(
                'label' => _g('受払備考'),
                'field' => 'remarks',
            ),
            array(
                'label' => _g('登録ユーザー'),
                'field' => 'user_name',
            ),
        );
    }

}