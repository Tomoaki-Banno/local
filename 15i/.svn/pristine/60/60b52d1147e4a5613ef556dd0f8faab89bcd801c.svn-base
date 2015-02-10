<?php

class Stock_StockFlow_List extends Base_ListBase
{

    var $from;
    var $to;
    var $isShowSeiban;
    var $isShowLocation;
    var $isShowLot;
    var $isShowChild;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $startDay = date('Y-m-01');
        $nextYear = date('Y', strtotime('+1 month'));
        $nextMonth = date('m', strtotime('+1 month'));
        $endDay = date('Y-m-d', mktime(0, 0, 0, $nextMonth, 0, $nextYear));

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
                'type' => 'select',
                'field' => 'item_group_id',
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
                'label' => _g('製番別表示'),
                'type' => 'select',
                'field' => 'show_seiban',
                'options' => Gen_Option::getTrueOrFalse('search'),
                'nosql' => true,
                'default' => 'false',
                'onChange_noEscape' => 'onShowSeibanChange()',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'nosql' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('未確定データ'),
                'field' => 'available_stock',
                'type' => 'select',
                'nosql' => true,
                'options' => array('true' => _g('含める(有効在庫)'), 'false' => _g('含めない(理論在庫)')),
                'default' => 'true',
                'helpText_noEscape' => _g('ロケ別表示を行うときは「含める」を選択できません（有効在庫の表示はできません）。'),
            ),
            array(
                'label' => _g('ロケ別表示'),
                'field' => 'show_location',
                'type' => 'select',
                'options' => Gen_Option::getTrueOrFalse('search'),
                'nosql' => true,
                'default' => 'false',
                'onChange_noEscape' => 'onShowLocationChange()',
            ),
            array(
                'label' => _g('ロケーション'),
                'field' => 'location_id',
                'type' => 'select',
                'options' => $option_location_group,
                'nosql' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('サプライヤー在庫'),
                'field' => 'partner_stock',
                'type' => 'select',
                'nosql' => true,
                'options' => Gen_Option::getTrueOrFalse('search-include'),
                'default' => 'true',
            ),
            array(
                'label' => _g('日付モード'),
                'field' => 'date_mode',
                'type' => 'select',
                'options' => array('0' => _g('日次'), '1' => _g('週次'), '2' => _g('月次')),
                'default' => '0',
                'nosql' => true,
                'helpText_noEscape' => _g('リスト横軸の日付間隔を指定します。') . "<br><br>"
                    . _g('「週次」の場合は日曜日から土曜日までが1週とみなされます。') . "<br><br>"
                    . _g('「週次」の場合は日曜日から土曜日、「月次」の場合は1日から末日までとなるよう、日付範囲が自動的に修正されます。'),
            ),
            array(
                'label' => _g("期間"),
                'field' => 'date',
                'type' => 'dateFromTo',
                'defaultFrom' => $startDay, // 今月1日から
                'defaultTo' => $endDay, // 今月末日まで
                'nosql' => true,
                'rowSpan' => 2,
                'helpText_noEscape' => _g("リスト横軸の日付の期間を指定します。") . "<br><br>"
                    . _g("日付モードによって最大期間が決まっており、この期間を超えた分は表示されません。") . "<br>"
                    . _g("日次： 100日") . "<br>"
                    . _g("週次： 1年") . "<br>"
                    . _g("月次： 3年"),
            ),
            array(
                'label' => _g('親品目'),
                'field' => 'parent_item_id',
                'type' => 'dropdown',
                'size' => '150',
                'dropdownCategory' => 'item',
                'nosql' => true,
                'rowSpan' => 2,
                'hide' => true,
            ),
            array(
                'label' => _g('表示対象'),
                'field' => 'show_mode',
                'type' => 'select',
                'nosql' => true,
                'options' => array('0' => _g('すべて'), '1' => _g('在庫変動があるレコードのみ'), '2' => _g('在庫があるレコードのみ'), '3' => _g('在庫数 < 安全在庫数')),
                'default' => '1',
                'helpText_noEscape' =>
                    "<b>" . _g("在庫変動があるレコードのみ") . "</b>: <br>" . _g("期間内に在庫数の変動（入庫もしくは出庫）があるレコードのみを表示します。在庫に動きのないレコードは表示されません。") . "<br><br>" .
                    "<b>" . _g("在庫があるレコードのみ") . "</b>: <br>" . _g("前在庫、もしくは終了日の在庫数が0より大きいレコードのみを表示します。前在庫と終了日の在庫数がともに0以下のレコードは表示されません。") . "<br><br>" .
                    "<b>" . _g("在庫数 < 安全在庫数") . "</b>: <br>" . _g("表示期間中に1日でも「在庫数 < 安全在庫数」となるレコードのみを表示します。「在庫数」とは、表示条件 [未確定データ] が [含める] のときは有効在庫数、[含めない] のときは理論在庫数を指します。"),
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        // 検索条件（日付）に不正な値が指定されたとき、正しい値に変換しておく。
        // デフォルト値の設定についてはここではなく後のsetSearchConditionメソッド
        // で行っていることに注意。
        $startDay = date('Y-m-01');
        $nextYear = date('Y', strtotime('+1 month'));
        $nextMonth = date('m', strtotime('+1 month'));
        $endDay = date('Y-m-d', mktime(0, 0, 0, $nextMonth, 0, $nextYear));

        // 第3引数は空欄やnullを許可するかどうか。
        ////   空欄は許可できないが（SQLエラーになる）、単純にfalseにするとExcelモードのときに日付指定が無視されてしまう。
        ////   （Excelモードでは日付指定をCookieから復元するが、ここではまだ復元されていないため変換対象になってしまう）
        ////   そこで空欄は非許可（強制変換）、nullは許可（スルーしてあとでCookieから復元）としている。
        // rev. 20120928 画面表示上のデータをExcel出力するのであれば、日付をコンバートしてもよいはず。
        $converter->notDateStrToValue('gen_search_date_from', $startDay, isset($form['gen_datePattern_gen_search_date'])
                && $form['gen_datePattern_gen_search_date'] == "" && isset($form['gen_search_date_from']));       // 今月1日から
        $converter->notDateStrToValue('gen_search_date_to', $endDay, isset($form['gen_datePattern_gen_search_date'])
                && $form['gen_datePattern_gen_search_date'] == "" && isset($form['gen_search_date_to']));         // 今月末日まで

        $converter->dateSort('gen_search_date_from', 'gen_search_date_to');

        // 日数制限
        switch (@$form['gen_search_date_mode']) {
            case '1':
                // 週次
                $limit = 365;
                break;
            case '2':
                // 月次
                $limit = 365 * 3;
                break;
            default:
                // 日次
                $limit = 100;
                break;
        }
        $converter->dateSpan('gen_search_date_from', 'gen_search_date_to', $limit);
    }

    function beforeLogic(&$form)
    {
        set_time_limit(100);

        // 日付モードによって開始日・終了日を修正
        // （ちなみに日数制限はここではなくConverterで行っている）
        $this->from = $form['gen_search_date_from'];
        $this->to = $form['gen_search_date_to'];
        switch ($form['gen_search_date_mode']) {
            case '1':   // 週次： 日曜日から土曜日までとなるように修正
                $this->from = date('Y-m-d', strtotime($this->from . ' -' . date('w', strtotime($this->from)) . ' day'));
                $this->to = date('Y-m-d', strtotime($this->to . ' +' . (6 - date('w', strtotime($this->to))) . ' day'));
                break;
            case '2':   // 月次： 1日から月末日までとなるように修正
                $this->from = date('Y-m-01', strtotime($this->from));
                $this->to = date('Y-m-t', strtotime($this->to));
                break;
        }
        $form['gen_search_date_from'] = $this->from;
        $form['gen_search_date_to'] = $this->to;

        $this->isShowSeiban = ($form['gen_search_show_seiban'] == 'true');
        $this->isShowLocation = ($form['gen_search_show_location'] == 'true');
        $this->isShowLot = (@$form['gen_search_show_lot'] == 'true');

        $this->isShowChild = (@$form['gen_search_show_child'] == 'true');

        $seiban = $this->getParam($this->isShowSeiban, false, @$form['gen_search_seiban']);
        $locationId = $this->getParam($this->isShowLocation, true, @$form['gen_search_location_id']);
        $lotId = $this->getParam($this->isShowLot, true, @$form['gen_search_lot_id']);
        $isIncludePartnerStock = (@$form['gen_search_partner_stock'] == 'true');

        // temp_inout に情報取得
        Logic_Stock::createTempInoutTable(
                $this->from
                , $this->to
                , null
                , $seiban
                , $locationId
                , $lotId
                , $isIncludePartnerStock
                , true
                , false
                , false
                , true
        );
    }

    // 検索パラメータを、ロジックに渡せる形に整形する。
    function getParam($isShow, $isNumeric, $param)
    {
        if ($isNumeric) {
            if (is_numeric($param))
                return $param;
        } else {
            if ($param != '')
                return $param;
        }
        return null;
    }

    // SQL組み立て補助
    function getSql($category, $categoryOrder, $isStock, $field, $inField, $outField, $beforeStockField, $dateMode, $showMode)
    {
        $sql = "
        select
            t1.item_id
            ,'{$category}' as category
            ,{$categoryOrder} as category_order
              /* 製番やロケ列が非表示でもカラムはつくっておく。ソート対象に指定されていた場合のエラーを回避するため */
            ," . ($this->isShowSeiban ? "seiban" : "null as seiban") . "
            ," . ($this->isShowLocation ? "location_id" : "null as location_id") . "
            ," . ($this->isShowLot ? "lot_id" : "null as lot_id") . "
            ," . ($isStock ? "sum(before)" : "cast(null as numeric)") . " as before_stock
            ";
            switch ($dateMode) {
                case '1':
                    $addStr = " +1 week";
                    break;
                case '2':
                    $addStr = " +1 month";
                    break;
                default :
                    $addStr = " +1 day";
                    break;
            }
            for ($day = strtotime($this->from); $day <= strtotime($this->to); $day = strtotime(date('Y-m-d', $day) . $addStr)) {        // 86400sec = 1day
                $spanFrom = date('Y-m-d', $day);
                $spanEnd = date('Y-m-d', strtotime($spanFrom . $addStr . ' -1 day'));
                $spanFromShort = date('Ymd', $day);

                if ($isStock) {
                    // 在庫数
                    $sql .= "
                        ,sum(case when date <= '{$spanEnd}' then qty else 0 end) + coalesce(sum(before),0) as day_{$spanFromShort}
                        ,'' as desc_{$spanFromShort}
                    ";
                } else {
                    // 入出庫数
                    $sql .= "
                        ,sum(case when date between '{$spanFrom}' and '{$spanEnd}' then qty end) as day_{$spanFromShort}
                        ,substr(string_agg(case when date between '{$spanFrom}' and '{$spanEnd}' then description end, ''),1,1000) as desc_{$spanFromShort}
                    ";    // 月次モードではかなり多くなる可能性があるため、1000文字制限
                }
            }
            // 入出庫合計列
            if ($isStock) {
                $sql .= ",null as total_qty";
            } else {
                $sql .= ",sum(case when date between '{$this->from}' and '{$this->to}' then qty end) as total_qty";
            }
            $sql .= ($showMode == "3" ? ",sum(case when date <= '{$spanEnd}' then qty else 0 end) + coalesce(sum(before),0) as stock_{$spanFromShort}, max(safety_stock) as safety_{$spanFromShort}" : "");
            $sql .= "
        from
            (select
                date
                ,temp_inout.item_id
                " . ($this->isShowSeiban ? ",seiban" : "") . "
                " . ($this->isShowLocation ? ",location_id" : "") . "
                " . ($this->isShowLot ? ",lot_id" : "") . "
                ,sum(case when id=-1 then {$beforeStockField} else 0 end) as before
                ,sum({$field}) as qty
                ,string_agg(case when {$field} <> 0 then cast(date as text) || '　　' || description || '　　' || {$field} || '<br>' else '' end,'') as description
                " . ($showMode == '1' || $showMode == '2' ? ",sum({$inField}) as in_qty ,sum({$outField}) as out_qty" : "") . "
            from
                temp_inout
            group by
                date,
                temp_inout.item_id
                " . ($this->isShowSeiban ? ",seiban" : "") . "
                " . ($this->isShowLocation ? ",location_id" : "") . "
                " . ($this->isShowLot ? ",lot_id" : "") . "
            ) as t1
            " . ($showMode == "3" ? "left join item_master on t1.item_id = item_master.item_id" : "") . "
        group by
            t1.item_id
            " . ($this->isShowSeiban ? ",seiban" : "") . "
            " . ($this->isShowLocation ? ",location_id" : "") . "
            " . ($this->isShowLot ? ",lot_id" : "") . "
        " . ($showMode == '1' ? "
                HAVING
                    sum(case when date between '{$this->from}' and '{$this->to}' then in_qty end) <> 0
                    or sum(case when date between '{$this->from}' and '{$this->to}' then out_qty end) <> 0
                " : "") .
            ($showMode == '2' ? "
                HAVING
                    sum(before) > 0
                    or sum(case when date <= '{$this->to}' then coalesce(in_qty,0) - coalesce(out_qty,0) else 0 end) + coalesce(sum(before),0) > 0
                " : "");
        if ($showMode == "3") {
            $sql .= "HAVING 1=0 ";
            for ($day = strtotime($this->from); $day <= strtotime($this->to); $day = strtotime(date('Y-m-d', $day) . $addStr)) {        // 86400sec = 1day
                $spanFrom = date('Y-m-d', $day);
                $spanEnd = date('Y-m-d', strtotime($spanFrom . $addStr . ' -1 day'));
                $spanFromShort = date('Ymd', $day);
                $sql .= " or sum(case when date <= '{$spanEnd}' then qty else 0 end) + coalesce(sum(before),0) < max(safety_stock)";
            }
        }

        return $sql;
    }

    function setQueryParam(&$form)
    {
        if (!$this->isShowLocation && @$form['gen_search_available_stock'] == 'true') {
            // 有効在庫
            $inField = "coalesce(in_qty,0)+coalesce(in_plan_qty,0)";
            $outField = "coalesce(out_qty,0)+coalesce(out_plan_qty,0)";
            $stockField = "available_stock_quantity";
        } else {
            // 理論在庫
            $inField = "in_qty";
            $outField = "out_qty";
            $stockField = "logical_stock_quantity";
        }

        $parentItemId = @$form['gen_search_parent_item_id'];

        // 親品目が指定されている場合はtemp_bom_expandテーブルを準備
        if (is_numeric($parentItemId)) {
            Logic_Bom::expandBom($parentItemId, 0, false, false, false);
        }

        $this->selectQuery = "
            select
                t1.*
                ,item_code
                ,item_name
                ,safety_stock
                ,item_group_id
                ,measure
                ,maker_name
                ,spec
                ,rack_no
                ,comment
                ,comment_2
                ,comment_3
                ,comment_4
                ,comment_5
                /* ロケ列が非表示でもカラムはつくっておく。ソート対象に指定されていた場合のエラーを回避するため */
                " . ($this->isShowLocation ? ",location_name " : ",null as location_name") . "
                " . ($this->isShowLot ? ",lot_no " : ",null as lot_no") . "
            from (
                " . $this->getSql("入", 1, false, $inField, $inField, $outField, $stockField, $form['gen_search_date_mode'], $form['gen_search_show_mode']) . "
                UNION ALL
                " . $this->getSql("出", 2, false, $outField, $inField, $outField, $stockField, $form['gen_search_date_mode'], $form['gen_search_show_mode']) . "
                UNION ALL
                " . $this->getSql("在", 3, true, "coalesce({$inField},0) - coalesce({$outField},0)", $inField, $outField, $stockField, $form['gen_search_date_mode'], $form['gen_search_show_mode']) . "
                ) as t1
                inner join
                   item_master on t1.item_id = item_master.item_id
                   " . ($this->isShowLocation ? " left join location_master on t1.location_id = location_master.location_id" : "") . "
                   " . ($this->isShowLot ? " left join lot_master on t1.lot_id = lot_master.lot_id" : "") . "
                   " . (is_numeric(@$form['gen_search_parent_item_id']) ?
                    " inner join (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on t1.item_id = t_exp.exp_item_id " : "") . "
            [Where]
                /* 非表示品目・ダミー品目は表示しない */
                and not coalesce(item_master.end_item, false)
                and not coalesce(item_master.dummy_item, false)
            [Orderby]
        ";

        $this->orderbyDefault = "item_code";
        if ($this->isShowSeiban)
            $this->orderbyDefault .= ",seiban";
        if ($this->isShowLocation)
            $this->orderbyDefault .= ",location_id";
        if ($this->isShowLot)
            $this->orderbyDefault .= ",lot_id";
        $this->orderbyDefault .= ",category_order";
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "item_id"),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("在庫推移リスト");
        $form['gen_menuAction'] = "Menu_Stock";
        $form['gen_listAction'] = "Stock_StockFlow_List";
        $form['gen_idField'] = 'item_id';
        $form['gen_excel'] = "true";
        $form['gen_onLoad_noEscape'] = "onLoad()";
        $form['gen_pageHelp'] = _g("在庫推移リスト");

        // 集計行を非表示。この画面は一品目に「入」「出」「在」の3行があり、集計行が無意味なため
        $form['gen_aggregateRowHeight'] = "0";

        $form['gen_javascript_noEscape'] = "
            function onLoad() {
               onShowLocationChange();
               onShowSeibanChange();
               onShowLotChange();
            }
            // 「ロケ別表示」変更イベント
            //  ロケ別表示のときのみ、ロケ指定を有効にする。合計表示のときもロケ指定できてもいいように思えるが、
            //  データ取得ロジック(beforeLogic参照)のパラメータが「合計かロケ指定か」という形なので、この制限が必要
            //  ロケ別のときは未確定データを「含めない」に固定する
            function onShowLocationChange() {
               var elm = $('#gen_search_show_location');
               if (elm.val() == 'true') {    // 「表示する」のときはロケ指定有効
                   gen.ui.alterDisabled($('#gen_search_location_id'), false);
                   $('#gen_search_available_stock').val('false');
                   gen.ui.alterDisabled($('#gen_search_available_stock'), true);
               } else {
                   $('#gen_search_location_id').attr('selectedIndex',0);
                   gen.ui.alterDisabled($('#gen_search_location_id'), true);
                   gen.ui.alterDisabled($('#gen_search_available_stock'), false);
               }
            }
            // 「製番別表示」変更イベント
            function onShowSeibanChange() {
               var elm = $('#gen_search_show_seiban');
               if (elm.val() == 'true') {    // 「表示する」のときは製番指定有効
                   gen.ui.alterDisabled($('#gen_search_seiban'), false);
               } else {
                   $('#gen_search_seiban').val('');
                   gen.ui.alterDisabled($('#gen_search_seiban'), true);
               }
            }
            // 「ロット別表示」変更イベント
            function onShowLotChange() {
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#ccffff" => "'[category]'=='在'",
        );

        $form['gen_colorSample'] = array(
            "fdbd2c" => array(_g("オレンジ"), _g("在庫切れ")),
            "f9bdbd" => array(_g("ピンク"), _g("安全在庫数未満")),
            "facea6" => array(_g("ベージュ"), _g("受注納期（未納品）")),
            "53d4c7" => array(_g("グリーン"), _g("未確定データ")),
        );

        $form['gen_alterColorDisable'] = "true";
        $this->pageRecordCountUnit = 3;

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'width' => '100',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'width' => '120',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
                'visible' => ($form['gen_search_show_seiban'] == 'true'),
            ),
            array(
                'label' => _g('ロケーション'),
                'field' => 'location_name',
                'width' => '90',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
                'visible' => ($form['gen_search_show_location'] == 'true'),
            ),
            array(
                'label' => _g('ロット'),
                'field' => 'lot_no',
                'width' => '50',
                'align' => 'center',
                'sameCellJoin' => true,
                'visible' => (@$form['gen_search_show_lot'] == 'true'),
            ),
            array(
                'label' => _g('安全在庫数'),
                'field' => 'safety_stock',
                'type' => 'numeric',
                'width' => '60',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'align' => 'center',
                'width' => '35',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'comment',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'comment_2',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'comment_3',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'comment_4',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'comment_5',
                'sameCellJoin' => true,
                'parentColumn' => "item_code",
                'hide' => true,
            ),
            array(
                'label' => _g('種類'),
                'field' => 'category',
                'width' => '40',
                'align' => 'center',
            ),
            array(
                'label' => _g('合計'),
                'field' => 'total_qty',
                'width' => '70',
                'type' => 'numeric',
                'zeroToBlank' => true,
            ),
        );

        $form['gen_columnArray'][] = array(
            'label' => _g('前在庫'),
            'field' => 'before_stock',
            'width' => '70',
            'type' => 'numeric',
        );

        $from = strtotime($this->from);
        $to = strtotime($this->to);

        switch ($form['gen_search_date_mode']) {
            case '1':
                $addStr = " +1 week";
                break;
            case '2':
                $addStr = " +1 month";
                break;
            default :
                $addStr = " +1 day";
                break;
        }
        for ($date = $from; $date <= $to; $date = strtotime(date('Y-m-d', $date) . $addStr)) {
            $fieldName = 'day_' . date('Ymd', $date);
            $descFieldName = 'desc_' . date('Ymd', $date);
            switch ($form['gen_search_date_mode']) {
                case '1':
                    $label = date('m-d', $date);
                    break;
                case '2':
                    $label = date('Y-m', $date);
                    break;
                default :
                    $label = date('m-d', $date) . "(" . Gen_String::weekdayStr(date('Y-m-d', $date)) . ")";
                    break;
            }
            $form['gen_columnArray'][] = array(
                'label' => $label,
                'field' => $fieldName,
                'width' => '70',
                'type' => 'numeric',
                'denyMove' => true, // 日付列は列順序固定。日付範囲を変更したときの表示乱れを防ぐため
                'colorCondition' => array("#fdbd2c" => "('[category]'=='在' && '[{$fieldName}]' < 0)",
                    "#f9bdbd" => "('[category]'=='在' && '[{$fieldName}]' < [safety_stock])",
                    "#facea6" => "!(strpos('[{$descFieldName}]', '" . _g('受注') . "[')===false)",
                    "#53d4c7" => "!(strpos('[{$descFieldName}]', '" . _g('製造指示書') . "')===false) " .
                    "|| !(strpos('[{$descFieldName}]', '" . _g('外製発注') . "')===false) " .
                    "|| !(strpos('[{$descFieldName}]', '" . _g('注文書') . "')===false) " .
                    "|| !(strpos('[{$descFieldName}]', '" . _g('計画') . "')===false) " .
                    "|| !(strpos('[{$descFieldName}]', '" . _g('使用予約') . "')===false) " .
                    "",
                ),
                'tooltip_noEscape' => "[{$descFieldName}]",
                'zeroToBlank' => true,
            );
        }
    }

}
