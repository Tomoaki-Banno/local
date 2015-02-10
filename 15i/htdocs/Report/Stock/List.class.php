<?php

class Report_Stock_List extends Base_ListBase
{

    var $horizColumnText;
    var $vertColumnText;
    var $drillDownLinkParam;
    var $autoAddColumn;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        // セレクタ選択肢
        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        $this->locationArr = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        // ドリルダウン機能では、月に関係なく月末日が「31」と指定されている。これを実際の月末日に置き換える必要がある。
        $to = @$form['gen_search_date_to'];
        if (substr($to, -2) == "31") {
            $form['gen_search_date_to'] = date('Y-m-d', strtotime(date('Y-m-0', strtotime(str_replace("31", "1", $to) . " +1 month"))));
        }

        // グラフ縦軸選択肢
        // 交差比率は表示に非常に時間がかかることがあるので含めない
        $vertArr = array('amount' => _g("在庫金額"), 'qty' => _g('在庫数量'), 'available' => _g('有効在庫数'), 'in_qty' => _g('入庫数'), 'out_qty' => _g('出庫数'), 'in_amount' => _g('入庫金額'), 'out_amount' => _g('出庫金額'), 'turnoverspan' => _g('在庫回転日数'), 'turnover' => _g('在庫回転率'));
        $vertArr2 = array_merge(array('' => _g('(なし)')), $vertArr);

        $form['gen_searchControlArray'] = array(
            // ****** 横軸条件の追加は慎重に！！！ *******
            //  いまのところ、「品目」が最小単位であることを前提としている（期間は別として）。
            //  ひとつの品目が複数レコードに分かれるような条件（たとえばロケ）を設定する場合、SQL書き換えが必要。
            //  品目や品目グループの条件をSQL内に書き込んでいる箇所を参照のこと

            array(
                'label' => _g('グラフ横軸'),
                'type' => 'select',
                'field' => 'horiz',
                'nosql' => 'true',
                'options' => array('item_code' => _g('品目コード'), 'item_name' => _g('品目名'), 'month' => _g("月次"), 'date' => _g("日次"), 'location' => _g("ロケーション")),
            ),
            array(
                'label' => _g('グラフ縦軸1 (棒)'),
                'type' => 'select',
                'field' => 'vert',
                'nosql' => 'true',
                'options' => $vertArr,
            ),
            array(
                'label' => _g('グラフ縦軸2 (折線)'),
                'type' => 'select',
                'field' => 'vert2',
                'nosql' => 'true',
                'options' => $vertArr2,
            ),
            // ****** 表示条件の追加は慎重に！！！ *******
            //  表示条件を追加する場合、データ取得SQLを変更しなければならない。
            //  条件によっては、サブクエリ（粗利や出庫額の計算など）にもWhereしなければならない。よく考えること
            //  また、いまのところ「品目」が最小単位であることを前提としている（期間は別として）。
            //  ひとつの品目が表示・非表示に分かれる可能性があるような条件（たとえばロケ）を設定する場合、SQL書き換えが必要。
            //  品目や品目グループの条件をSQL内に書き込んでいる箇所を参照のこと

            array(
                'label' => _g('期間'),
                'type' => 'dateFromTo',
                'field' => 'date',
                // デフォルト：　過去1年
                'defaultFrom' => date('Y-m-01', strtotime(date('Y-m-01') . " -12 month")),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'nosql' => 'true',
                'rowSpan' => 2
            ),
            array(
                'label' => _g('品目グループ'),
                'type' => 'select',
                'field' => 'item_group_id',
                'options' => $option_item_group,
                'nosql' => true,
            ),
            array(
                'label' => _g('ロケーション'),
                'type' => 'select',
                'field' => 'location_id',
                'options' => $option_location,
                'nosql' => true,
            ),
            array(
                'label' => _g('サプライヤー在庫'),
                'type' => 'select',
                'field' => 'include_partner_stock',
                'options' => Gen_Option::getTrueOrFalse('search-include'),
                'nosql' => true,
                'default' => 'true',
            ),
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'size' => '150',
                'dropdownCategory' => 'item',
                'field' => 'item_id',
                'nosql' => true,
                'rowSpan' => 2
            ),
            array(
                'label' => _g('非表示品目'),
                'type' => 'select',
                'field' => 'include_end_item',
                'options' => Gen_Option::getTrueOrFalse('search-include'),
                'nosql' => true,
                'default' => 'false',
            ),
            array(
                'label' => _g('粗利と交差比率の表示'),
                'type' => 'select',
                'field' => 'show_profit',
                'options' => array('' => _g("表示しない"), '1' => _g('表示する')),
                'nosql' => 'true',
                'helpText_noEscape' => _g("表示すると、画面表示に相当な時間がかかる場合があります。どうしても必要でない限り、「表示しない」を選択してください。"),
            ),
            array(
                'label' => _g('グラフ表示'),
                'type' => 'select',
                'field' => 'showChart',
                'nosql' => 'true',
                'options' => array('' => _g('する'), '1' => _g("しない")),
            ),
       );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->dateSort('gen_search_date_from', 'gen_search_date_to');
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        global $gen_db;

        $from = $form['gen_search_date_from'];
        $to = $form['gen_search_date_to'];

        // 期間制限（後のdate_masterの処理を考慮して）
        if (strtotime($from) < strtotime(date('2005-01-01'))) {
            $from = "2005-01-01";
        }
        if (strtotime($to) < strtotime(date('2005-01-01'))) {
            $to = "2005-01-01";
        }

        // 横軸
        switch (@$form['gen_search_horiz']) {
            case 'date':        // 日
                $horizColumn = "to_char(date,'YYYY-MM-DD')";
                $this->horizColumnText = _g("日");
                $groupByColumn = "date";
                $groupKey = "date";
                $this->drillDownLinkParam = "gen_search_date_from=[groupkey]&gen_search_date_to=[groupkey]";
                $isDateMode = true;
                break;
            case 'month':       // 年月
                $horizColumn = "to_char(date_trunc('month',date),'YYYY-MM')";
                $this->horizColumnText = _g("年月");
                $groupByColumn = "date_trunc('month',date)";
                $groupKey = "to_char(date_trunc('month',date),'YYYY-MM')";
                $this->drillDownLinkParam = "gen_search_date_from=[groupkey]-01&gen_search_date_to=[groupkey]-31";
                $isDateMode = true;
                break;
            case 'location':    // ロケ
                $horizColumn = "max((case when location_id=0 then '" . _g(GEN_DEFAULT_LOCATION_NAME) . "' else location_name end))";
                $this->horizColumnText = _g("ロケーション名");
                $table = "temp_stock as t_stock";
                $dateColumn = "";
                $groupByColumn = "location_id";
                $groupKey = "location_id";
                $this->drillDownLinkParam = "gen_search_location_id=[groupkey]";
                $isDateMode = false;
                $form['gen_search_horiz'] = "location";
                break;
            case 'item_name':   // 品目名
                $horizColumn = "max(item_name)";
                $this->horizColumnText = _g("品目名");
                $table = "temp_stock as t_stock";
                $dateColumn = "";
                $groupByColumn = "item_id";
                $groupKey = "item_id";
                $this->drillDownLinkParam = "gen_search_item_id=[groupkey]";
                $isDateMode = false;
                $form['gen_search_horiz'] = "item_name";
                $this->autoAddColumn = array("item_code", _g("品目コード"));
                break;
            default:            // 品目コード
                $horizColumn = "max(item_code)";
                $this->horizColumnText = _g("品目コード");
                $table = "temp_stock as t_stock";
                $dateColumn = "";
                $groupByColumn = "item_id";
                $groupKey = "item_id";
                $this->drillDownLinkParam = "gen_search_item_id=[groupkey]";
                $isDateMode = false;
                $form['gen_search_horiz'] = "item_code";
                $this->autoAddColumn = array("item_name", _g("品目名"));
                break;
        }

        // 粗利と交差比率を表示するか
        $isShowProfit= (isset($form['gen_search_show_profit']) && $form['gen_search_show_profit'] == '1');

        // 縦軸
        //（引数の2番目以降は戻り値の受け取り用）
        $this->getVertParam(@$form['gen_search_vert'], $columnArr, $vertColumn, $chartTitle, $chartLegend, $chartBaloon, $isShowProfit);
        if (@$form['gen_search_vert2'] != "") {
            $this->getVertParam(@$form['gen_search_vert2'], $columnArr2, $vertColumn2, $chartTitle2, $chartLegend2, $chartBaloon2, $isShowProfit);
            $chartTitle .= ", " . $chartTitle2;
        }

        // 原価データの取得
        if ($isShowProfit) {
            Logic_BaseCost::getBaseCostReportData(
                ""          // 製番
                , ""        // 受注番号
                , $from
                , $to
                , ""        // 納品日From
                , ""        // 納品日To
                , ""        // 検収日From
                , ""        // 検収日To
                , ""        // 得意先id
                , ""        // 受注担当者id
                , ""        // 受注部門id
                , null
                , null
            );
        }

        // 在庫データの取得用
        $itemId = (is_numeric(@$form['gen_search_item_id']) ? $form['gen_search_item_id'] : null);
        $isIncludePartnerStock = (@$form['gen_search_include_partner_stock'] == 'true');

        // 最終日時点の在庫評価単価をテンポラリテーブル（temp_stock_price）に取得
        Logic_Stock::createTempStockPriceTable($to);

       // 在庫データとSQLの取得
        if ($isDateMode) {
            // ******** 横軸が 日 or 月 の場合 ********
            // 今回の表示範囲が日付基準テーブルになければ、日付レコードを作る
            Gen_Date::makeDateMaster($from, $to);

            // 入出庫データの取得
            Logic_Stock::createTempInoutTable(
                $from
                , $to
                , $itemId
                , null      // seiban
                , (is_numeric(@$form['gen_search_location_id']) ? $form['gen_search_location_id'] : null)
                , null      // lot
                , $isIncludePartnerStock // サプライヤー在庫を含めるかどうか
                , true      // use_planを含めるか
                , false     // use_plan の全期間分差し引くかどうか。flowにあわせてfalse
                , true      // 在庫数(logical, available)を製番/ロケ/ロットに分けず、品目合計で取得するか
                , true      // これをtrueにすると、理論在庫数・有効在庫数の計算が一日単位になる（一日の最後の行だけ計算されるようになる）
            );

            // 日ごとの在庫数と金額を計算する。
            //  temp_inoutの中にlogical_stock_quantityがあるものの、そこには品目の入出庫がない日のレコードが
            //  入っていないという問題がある。（入出庫がない日も、前日の数量を引き継いだレコードが必要）
            //  logical_stock_quantityを基に、date_masterを軸にして全日分のレコードを取得する方法もあるが、
            //  temp_inoutが品目別であるため、品目数が多いときにとても時間がかかる。
            //  そこで、ここではあらためて日ごとの在庫数を再計算している。全品目の合計値として計算するため、
            //  品目数が多くてもパフォーマンスの問題は小さい。
            $query = "
                /* 日ごとの入出庫合計数・金額を計算（全品目合計） */
                create temp table temp_inout_sum as
                select
                    {$groupByColumn} as date
                    ,count(distinct date) as date_count

                    /* 以下の３つは、次のupdate queryで在庫数計算に使用するための項目。*/
                    /* 期間中の純粋な入出庫数ではない（期間前分が含まれている） ので、他の用途に使用するときは注意 */
                    ,coalesce(sum(in_qty_with_before),0) - coalesce(sum(out_qty_with_before),0) as inout_qty_with_before
                    ,coalesce(sum(in_qty_with_before),0) - coalesce(sum(out_qty_with_before),0) + coalesce(sum(in_plan_qty_with_before),0) - coalesce(sum(out_plan_qty_with_before),0) as inout_available_qty_with_before
                    ,coalesce(sum(in_qty_with_before * stock_price),0) - coalesce(sum(out_qty_with_before * stock_price),0) as inout_amount_with_before

                    ,cast(0 as numeric) as logical_stock_quantity
                    ,cast(0 as numeric) as available_stock_quantity
                    ,cast(0 as numeric) as stock_amount

                    ,coalesce(sum(in_qty),0) as in_qty
                    ,coalesce(sum(out_qty),0) as out_qty
                    ,coalesce(sum(in_qty * stock_price),0) as in_amount
                    ,coalesce(sum(out_qty * stock_price),0) as out_amount

                from
                    date_master
                    left join (
                        select
                            coalesce(date,'{$from}') as temp_date /* 前在庫は期間の初日に計上 */
                            , in_qty as in_qty_with_before
                            , out_qty as out_qty_with_before
                            , in_plan_qty as in_plan_qty_with_before
                            , out_plan_qty as out_plan_qty_with_before
                            , temp_stock_price.stock_price
                            /* 期間中の純粋な入出庫数（前在庫分を含まない） */
                            , case when date is not null then in_qty end as in_qty
                            , case when date is not null then out_qty end as out_qty
                            , dummy_item
                        from
                            temp_inout
                            inner join item_master on temp_inout.item_id = item_master.item_id
                            /* ロケの絞り込みは temp_inout の作成時点で行っている */
                            " . (is_numeric(@$form['gen_search_item_id']) ? " and (temp_inout.item_id='{$form['gen_search_item_id']}')" : "") . "
                            " . (is_numeric(@$form['gen_search_item_group_id']) ? " and (item_group_id='{$form['gen_search_item_group_id']}' or item_group_id_2='{$form['gen_search_item_group_id']}' or item_group_id_3='{$form['gen_search_item_group_id']}')" : "") . "
                            " . (@$form['gen_search_include_end_item'] == 'false' ? " and not coalesce(item_master.end_item, false)" : "") . "
                            left join temp_stock_price on temp_inout.item_id = temp_stock_price.item_id
                        ) as t0
                        on date_master.date = t0.temp_date
                where
                    date_master.date between '{$from}'::date and '{$to}'::date
                    /* ダミー品目は表示しない */
                    and not coalesce(dummy_item, false)
                group by
                    {$groupByColumn}
                order by
                    date;

                /* 入出庫数を累積して日ごとの在庫数と金額を計算 */
                update temp_inout_sum
                set logical_stock_quantity = (select sum(inout_qty_with_before) from temp_inout_sum as t1 where temp_inout_sum.date >= t1.date)
                ,available_stock_quantity = (select sum(inout_available_qty_with_before) from temp_inout_sum as t1 where temp_inout_sum.date >= t1.date)
                ,stock_amount = (select sum(inout_amount_with_before) from temp_inout_sum as t1 where temp_inout_sum.date >= t1.date);
            ";
            $gen_db->query($query);

            // グラフ, 表 共通のSQL。グラフでは「show」が横軸、「data1」が縦軸に表示される
            $this->selectQuery = "
            select
                {$horizColumn} as show
                ,{$vertColumn} as data1
                " . (@$vertColumn2 != "" ? ",{$vertColumn2} as data2" : "") . "
                " . (isset($this->autoAddColumn) ? ",max({$this->autoAddColumn[0]}) as autoadd" : "") . "
                ,{$groupKey} as groupkey
            	";
            foreach ($columnArr as $key => $value) {
                $this->selectQuery .= ",{$value} as {$key}";
            }
            $this->selectQuery .=
                    (array_key_exists("in_amount", $columnArr) ? "" : ",sum(in_amount) as in_amount") .
                    (array_key_exists("out_amount", $columnArr) ? "" : ",sum(out_amount) as out_amount") .
                    (array_key_exists("before_amount", $columnArr) ? "" : ",coalesce(sum(stock_amount),0) - coalesce(sum(in_amount),0) + coalesce(sum(out_amount),0) as before_amount") .
                    (!$isShowProfit ? "" : ",sum(profit) as profit") .
                    (array_key_exists("date_count", $columnArr) ? "" : ",max(date_count) as date_count") .
                    "
            from
                temp_inout_sum as t_stock
                " .
                ($isShowProfit ? "
                    /* 粗利(profit)。製番のみ。いちおう受注日基準。*/
                    /* temp_base_cost.profit を使用しているので、未納品分は受注額、納品済分は納品額に対する粗利となる。 */
                    /* ロケ別には出せない。したがって表示条件や横軸にロケが指定されている場合、粗利や交差比率は正しく出ない */
                    left join (
                        select
                            sum(case when order_class=0 then profit end) as profit
                            ,{$groupByColumn} as received_date
                        from (
                            select
                                received_detail_id as rid
                                ,max(temp_base_cost.item_id) as iid
                                ,max(received_date) as date
                                ,max(profit) as profit
                                ,max(order_class) as order_class
                            from
                                temp_base_cost
                                left join item_master on temp_base_cost.item_id = item_master.item_id
                            where 1=1
                            " . (is_numeric(@$form['gen_search_item_id']) ? " and (temp_base_cost.item_id='{$form['gen_search_item_id']}')" : "") . "
                            " . (is_numeric(@$form['gen_search_item_group_id']) ? " and (item_group_id='{$form['gen_search_item_group_id']}' or item_group_id_2='{$form['gen_search_item_group_id']}' or item_group_id_3='{$form['gen_search_item_group_id']}')" : "") . "
                            " . (@$form['gen_search_include_end_item'] == 'false' ? " and not coalesce(item_master.end_item, false)" : "") . "
                            group by received_detail_id
                            ) as t_base1
                        group by
                            {$groupByColumn}
                        ) as t_base
                        on t_stock.date = t_base.received_date
                " : "") . "
            [Where]
            group by
                {$groupByColumn}
            [Orderby]
            ";
        } else {
            // ******** 横軸が 品目/ロケ の場合 ********

            $isHorizLocation = ($form['gen_search_horiz'] == "location");

            // 期間末時点の在庫データの取得
            Logic_Stock::createTempStockTable(
                    $to
                    , $itemId
                    , "sum"
                    , (is_numeric(@$form['gen_search_location_id']) ? $form['gen_search_location_id'] : ($isHorizLocation ? null : "sum"))
                    , "sum"
                    , true // 有効在庫も取得
                    , $isIncludePartnerStock // サプライヤー在庫を含めるかどうか
                    , false                  // use_plan の全期間分差し引くかどうか。flowにあわせてfalse
            );

            // グラフ, 表 共通のSQL。グラフでは「show」が横軸、「data1」が縦軸に表示される
            $this->selectQuery = "
                select
                    {$horizColumn} as show
                    ,{$vertColumn} as data1
                    " . (@$vertColumn2 != "" ? ",$vertColumn2 as data2" : "") . "
                    " . (isset($this->autoAddColumn) ? ",max({$this->autoAddColumn[0]}) as autoadd" : "") . "
                    ,{$groupKey} as groupkey
            ";
            foreach ($columnArr as $key => $value) {
                $this->selectQuery .= ",$value as $key";
            }
            $this->selectQuery .=
                    (array_key_exists("in_amount", $columnArr) ? "" : ",sum(in_amount) as in_amount") .
                    (array_key_exists("out_amount", $columnArr) ? "" : ",sum(out_amount) as out_amount") .
                    (array_key_exists("before_amount", $columnArr) ? "" : ",coalesce(sum(stock_amount),0) - coalesce(sum(in_amount),0) + coalesce(sum(out_amount),0) as before_amount") .
                    (!$isShowProfit ? "" : ",sum(profit) as profit") .
                    (array_key_exists("date_count", $columnArr) ? "" : ",max(date_count) as date_count") .
                    "
            from
                ( select
                    *
                    ,logical_stock_quantity * t_price.stock_price as stock_amount
                    , " . ((strtotime($to) - strtotime($from)) / (3600 * 24) + 1) . " as date_count
                  from
                    temp_stock
                    left join (select item_id as iid, item_code, item_name, item_group_id, item_group_id_2, item_group_id_3, end_item, dummy_item from item_master) as t_item on temp_stock.item_id = t_item.iid
                    left join (select item_id as iid, stock_price from temp_stock_price) as t_price on temp_stock.item_id = t_price.iid
                    " . ($isHorizLocation ? "left join (select location_id as lid, location_code, location_name from location_master) as t_location on temp_stock.location_id = t_location.lid" : "") . "
                    left join (
                        select
                            " . ($isHorizLocation ? 'location_master.location_id' : 'item_master.item_id') . " as inout_iid
                            ,in_qty
                            ,out_qty
                            ,in_amount
                            ,out_amount
                            ," . (!$isShowProfit || $isHorizLocation ? '0' : 'case when order_class=0 then profit end') . " as profit
                        from
                            " . ($isHorizLocation ? 'location_master' : 'item_master') . "

                            /* 入庫数 */
                            left join (
                                select
                                    " . ($isHorizLocation ? 'item_in_out.location_id' : 'item_in_out.item_id') . " as in_iid
                                    ,sum(item_in_out_quantity) as in_qty
                                    ,sum(item_in_out_quantity * temp_stock_price.stock_price) as in_amount
                                from
                                    item_in_out
                                    left join item_master on item_in_out.item_id = item_master.item_id
                                    left join temp_stock_price on item_in_out.item_id = temp_stock_price.item_id
                                where
                                    (item_in_out_date between '{$from}'::date and '{$to}'::date
                                    " . (is_numeric(@$form['gen_search_item_id']) ? " and (item_in_out.item_id='{$form['gen_search_item_id']}')" : "") . "
                                    " . (is_numeric(@$form['gen_search_location_id']) ? " and (item_in_out.location_id='{$form['gen_search_location_id']}')" : "") . "
                                    " . (is_numeric(@$form['gen_search_item_group_id']) ? " and (item_group_id='{$form['gen_search_item_group_id']}' or item_group_id_2='{$form['gen_search_item_group_id']}' or item_group_id_3='{$form['gen_search_item_group_id']}')" : "") . "
                                    " . (@$form['gen_search_include_end_item'] == 'false' ? " and not coalesce(item_master.end_item, false)" : "") . "
                                    and (classification='in'
                                     or classification='manufacturing'
                                     or classification='move_in'
                                     or classification='seiban_change_in'
                                    ))
                                group by
                                    " . ($isHorizLocation ? 'item_in_out.location_id' : 'item_in_out.item_id') . "
                                ) as t_in
                                on " . ($isHorizLocation ? 'location_master.location_id' : 'item_master.item_id') . "  = t_in.in_iid

                            /* 出庫数 */
                            left join (
                                select
                                    " . ($isHorizLocation ? 'item_in_out.location_id' : 'item_in_out.item_id') . " as out_iid
                                    ,sum(item_in_out_quantity) as out_qty
                                    ,sum(item_in_out_quantity * temp_stock_price.stock_price) as out_amount
                                from
                                    item_in_out
                                    left join item_master on item_in_out.item_id = item_master.item_id
                                    left join temp_stock_price on item_in_out.item_id = temp_stock_price.item_id
                                where
                                    (item_in_out_date between '{$from}'::date and '{$to}'::date
                                    " . (is_numeric(@$form['gen_search_item_id']) ? " and (item_in_out.item_id='{$form['gen_search_item_id']}')" : "") . "
                                    " . (is_numeric(@$form['gen_search_location_id']) ? " and (item_in_out.location_id='{$form['gen_search_location_id']}')" : "") . "
                                    " . (is_numeric(@$form['gen_search_item_group_id']) ? " and (item_group_id='{$form['gen_search_item_group_id']}' or item_group_id_2='{$form['gen_search_item_group_id']}' or item_group_id_3='{$form['gen_search_item_group_id']}')" : "") . "
                                    " . (@$form['gen_search_include_end_item'] == 'false' ? " and not coalesce(item_master.end_item, false)" : "") . "
                                    and (classification='out'
                                     or classification='payout'
                                     or classification='use'
                                     or classification='delivery'
                                     or classification='move_out'
                                     or classification='seiban_change_out'
                                    ))
                                group by
                                    " . ($isHorizLocation ? 'item_in_out.location_id' : 'item_in_out.item_id') . "
                                ) as t_out
                                on " . ($isHorizLocation ? 'location_master.location_id' : 'item_master.item_id') . "  = t_out.out_iid

                            " .
                            ($isShowProfit ? "
                                /* 粗利(profit)。製番のみ。*/
                                /* temp_base_cost.profit を使用しているので、未納品分は受注額、納品済分は納品額に対する粗利となる。 */
                                /* ロケ別には出せない。したがって表示条件や横軸にロケが指定されている場合、粗利や交差比率は正しく出ない */
                                left join (
                                    select
                                        item_id as base_iid
                                        ,sum(profit) as profit
                                    from (
                                        select
                                            received_detail_id as rid
                                            ,max(item_id) as item_id
                                            ,max(profit) as profit
                                        from
                                            temp_base_cost
                                        group by
                                            received_detail_id
                                        ) as t_base1
                                    group by
                                        item_id
                                    ) as t_base
                                    on " . ($isHorizLocation ? '1=0' : 'item_master.item_id = t_base.base_iid') . "
                            " : "") . "
                            where
                            in_iid is not null
                            or out_iid is not null
                            " . ($isShowProfit ? "or base_iid is not null" : ""). "
                        ) as t_inout on temp_stock.item_id = t_inout.inout_iid
                    )  as t_stock
            [Where]
                -- ダミー品目は表示しない
             	and not coalesce(dummy_item, false)
                " . ($dateColumn != "" ? "and {$dateColumn} between '{$from}'::date and '{$to}'::date" : "") . "
                " . (is_numeric(@$form['gen_search_item_group_id']) ? " and (item_group_id='{$form['gen_search_item_group_id']}' or item_group_id_2='{$form['gen_search_item_group_id']}' or item_group_id_3='{$form['gen_search_item_group_id']}')" : "") . "
             	" . (@$form['gen_search_include_end_item'] == 'false' ? " and not coalesce(end_item, false)" : "") . "
            group by
                {$groupByColumn}
            [Orderby]
            ";
        }
        $this->orderbyDefault = "show";

        // イレギュラーな形ではあるが、ここでいったん setViewParamを呼び出す。
        // $this->getOrderByArray で columnArrayを必要とするため。
        $this->setViewParam($form);

        // グラフ用にSQLを作成（以下はListBaseとだいたい同じ）
        if (!isset($form['gen_search_showChart']) || $form['gen_search_showChart'] != "1") {
            $user_id = Gen_Auth::getCurrentUserId();
            $action = get_class($this);
            $orderbyArr = $this->getOrderByArray($form, $this->orderbyDefault, $user_id, $action);
            $whereStr = $this->getSearchCondition($form, $form['gen_searchControlArray']);
            $orderbyStr = $this->makeOrderBy($orderbyArr);
            $chartQuery = str_replace('[Where]', $whereStr, $this->selectQuery);
            $chartQuery = str_replace('[Orderby]', $orderbyStr, $chartQuery);
            $pageCount = $this->getPageCount($form);  // ListBase
            $page = (is_numeric(@$form['gen_search_page']) ? $form['gen_search_page'] : 1);
            $pageCount = $this->getPageCount($form);  // ListBase;
            $page = 1;
            if (isset($form[SEARCH_FIELD_PREFIX . 'page'])) {
                $page = $form[SEARCH_FIELD_PREFIX . 'page'];
            }
            $offset = ($page - 1) * $pageCount;
            $chartQuery .= " offset {$offset} limit {$pageCount}";
            $chartLegend2 = (@$form['gen_search_vert2'] != "" ? $chartLegend2 : null);

            // グラフのセットアップ
            $form['gen_useChart'] = 'true';
            $form['gen_chartType'] = 'bar_line';	// pie / area / line / bar / bar_line
            $form['gen_chartWidth'] = '650';
            $form['gen_chartHeight'] = '150';
            $form['gen_chartAppendKey'] = 'true';	// 凡例表示
            $form['gen_chartData'] = $this->getChartData($chartQuery, $chartLegend, $chartLegend2);
        }
    }

    function getVertParam($vert, &$columnArr, &$vertColumn, &$chartTitle, &$chartLegend, &$chartBaloon, $isShowProfit)
    {
        global $gen_db;

        // 縦軸項目別のカラムSQL取得（この下）と、SQL組み立て（setQueryParam）で使用。
        // key部がSQL組み立て時のカラム名（ as XXX）になる。
        $columnArr = array(
            // 在庫数量
            'logical_stock_quantity' => "sum(logical_stock_quantity)",
            // 有効在庫数
            'available_stock_quantity' => "sum(available_stock_quantity)",
            // 入庫数
            'in_qty' => "sum(in_qty)",
            // 出庫数
            'out_qty' => "sum(out_qty)",
            // 入庫金額
            'in_amount' => "sum(in_amount)",
            // 出庫金額
            'out_amount' => "sum(out_amount)",
            // 在庫回転日数
            //  = 期間平均在庫額 / (期間中出庫額 / 期間日数)
            'turnoverspan' => "case when sum(out_amount) / max(date_count) <> 0 then " .
            "round(" .
            // ※本来、期間平均在庫額の計算の際、期間前の在庫額の計算にはその時点の評価単価を使用したいが、いまの在庫評価の仕組みでは難しい。
            //      それでここでは期間前も期間末も同一の単価（最新の評価単価）で計算している。
            // 期間平均在庫額 = (期間前在庫額 + 期間末在庫額) / 2
            //              = ((期間末在庫額 - 期間中入庫額 + 期間中出庫額) + 期間末在庫額) / 2
            //              = (期間末在庫額 * 2 - 期間中入庫額 + 期間中出庫額) / 2
            //              = 期間末在庫額 - (期間中入庫額 - 期間中出庫額) / 2
            "   (coalesce(sum(stock_amount),0) - (coalesce(sum(in_amount),0) - coalesce(sum(out_amount),0)) / 2) " .
            "   / (sum(out_amount) / max(date_count)) " .
            ") end",
            // 在庫回転率
            //  = (期間中出庫額 / 期間日数  * 365) / 期間平均在庫額
            'turnover' => "case when (coalesce(sum(stock_amount),0) - (coalesce(sum(in_amount),0) - coalesce(sum(out_amount),0)) / 2) <> 0 then " .
            "round(" .
            "   (sum(out_amount) / max(date_count) * 365) / " .
            // 期間平均在庫額。式の根拠は前の項目のコメントを参照
            "   (coalesce(sum(stock_amount),0) - (coalesce(sum(in_amount),0) - coalesce(sum(out_amount),0)) / 2) " .
            ",1) end",
           // 在庫金額
            'stock_amount' => "sum(stock_amount)",
        );
        if ($isShowProfit) {
            // 交差比率（製番のみ）
            //  = 期間中粗利 / 期間平均在庫額
            $columnArr['crossper'] =
                "case when (coalesce(sum(stock_amount),0) - (coalesce(sum(in_amount),0) - coalesce(sum(out_amount),0)) / 2) <> 0 then " .
                "round(" .
                "   sum(profit) / " .
                // 期間平均在庫額。式の根拠は前の項目のコメントを参照
                "   (coalesce(sum(stock_amount),0) - (coalesce(sum(in_amount),0) - coalesce(sum(out_amount),0)) / 2) " .
                ") end";
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        switch ($vert) {
            case 'qty':   // 在庫数量
                $vertColumn = $columnArr['logical_stock_quantity'];
                $chartTitle = _g("在庫数量");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            case 'available':   // 有効在庫数
                $vertColumn = $columnArr['available_stock_quantity'];
                $chartTitle = _g("有効在庫数");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            case 'in_qty':   // 入庫数
                $vertColumn = $columnArr['in_qty'];
                $chartTitle = _g("入庫数");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            case 'out_qty':   // 出庫数
                $vertColumn = $columnArr['out_qty'];
                $chartTitle = _g("出庫数");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            case 'in_amount':   // 入庫金額
                $vertColumn = $columnArr['in_amount'];
                $chartTitle = _g("入庫金額");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            case 'out_amount':   // 出庫金額
                $vertColumn = $columnArr['out_amount'];
                $chartTitle = _g("出庫金額");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;

            case 'turnoverspan':   // 在庫回転日数
                $vertColumn = $columnArr['turnoverspan'];
                $chartTitle = _g("在庫回転日数");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]   [value]" . _g("日");
                break;
            case 'turnover':   // 在庫回転率
                $vertColumn = $columnArr['turnover'];
                $chartTitle = _g("在庫回転率(回/年)");
                $chartLegend = _g("在庫回転率");
                $chartBaloon = "[category]   [value]" . _g("回/年");
                break;
            case 'crossper':   // 交差比率
                $vertColumn = $columnArr['crossper'];
                $chartTitle = _g("交差比率 (製番品目のみ)");
                $chartLegend = _g("交差比率");
                $chartBaloon = "[category]  [value]";
                break;
            default:        // 在庫金額
                $vertColumn = $columnArr['stock_amount'];
                $chartTitle = _g("在庫金額");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  {$keyCurrency} [value]";
                break;
        }
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("在庫レポート");
        $form['gen_menuAction'] = "Menu_Report";
        $form['gen_listAction'] = "Report_Stock_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = '';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("レポート");

        $form['gen_message_noEscape'] = sprintf(_g("グラフに表示されるデータは最初の%s件までです。"), GEN_CHART_HORIZ_MAX) . "<br>" . _g("金額は最新の評価単価で算出しています。「粗利」「原価」は製番品目に対する受注のみが対象です。受注日に計上されます。");

        $form['gen_javascript_noEscape'] = "
            function drillDown(linkParam) {
                linkParam += '&gen_search_vert=" . h(@$form['gen_search_vert']) . "';
                " .
                (@$form['gen_search_horiz'] == "date" || @$form['gen_search_horiz'] == "month" ? "" :
                    "linkParam += '&gen_search_date_from=" . h(@$form['gen_search_date_from']) . "';
                     linkParam += '&gen_search_date_to=" . h(@$form['gen_search_date_to']) . "';
                    ")
                . "
                location.href = 'index.php?action=" . h($form['gen_listAction']) . "&' + linkParam;
            }
        ";

        // 固定列
        $form['gen_fixColumnArray'] = array(
            array(
                'label' => $this->horizColumnText, // 横軸
                'field' => 'show',
                'width' => '200',
            ),
        );

        if (isset($this->autoAddColumn)) {
            $form['gen_fixColumnArray'][] =
                array(
                    'label' => $this->autoAddColumn[1],
                    'width' => '200',
                    'field' => 'autoadd',
                );
        }

        // 横軸に「ロケーション」を選択しているか、表示条件の「ロケーション」を指定している場合、有効在庫・粗利・交差比率は表示できない
        $isLocationMode = (@$form['gen_search_horiz'] == "location" || is_numeric(@$form['gen_search_location_id']));

        // 粗利と交差比率を表示するか
        $isShowProfit= (isset($form['gen_search_show_profit']) && $form['gen_search_show_profit'] == '1');

        // スクロール列
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('在庫金額'),
                'field' => 'stock_amount',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('期間最終日時点の在庫数 × 期間最終日時点の在庫評価単価 です。'),
            ),
            array(
                'label' => _g('在庫数量'),
                'field' => 'logical_stock_quantity',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('期間最終日時点の理論在庫数です。'),
            ),
            array(
                'label' => _g('有効在庫数'),
                // ロケ指定されているときは表示しない
                'field' => ($isLocationMode ? "" : "available_stock_quantity"),
                'colorCondition' => array("#cccccc" => ($isLocationMode ? "true" : "false")),
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('期間最終日時点の有効在庫数です。') . "<br><br>"
                . ('なお、表示条件の「グラフ横軸」に「ロケーション」を指定したり、表示条件の「ロケーション」に「すべて」以外を指定した場合、この欄に数字は表示されません。ロケーション別の有効在庫は計算できないためです。'),
            ),
            array(
                'label' => _g('前在庫額'),
                'field' => 'before_amount',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('期間開始時点の在庫数 × 期間最終日時点の在庫評価単価 です。'),
            ),
            array(
                'label' => _g('入庫額'),
                'field' => 'in_amount',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g("期間中の入庫数 × 期間最終日時点の在庫評価単価 です。") . "<br>"
                    . _g("明細は[資材管理]-[受払履歴]画面で確認できます。"),
            ),
            array(
                'label' => _g('出庫額'),
                'field' => 'out_amount',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g("期間中の出庫数 × 期間最終日時点の在庫評価単価 です。") . "<br>"
                    . _g("明細は[資材管理]-[受払履歴]画面で確認できます。"),
            ),
            array(
                'label' => _g('入庫数'),
                'field' => 'in_qty',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g("期間中の入庫数です。") . "<br>"
                    . _g("明細は[資材管理]-[受払履歴]画面で確認できます。"),
            ),
            array(
                'label' => _g('出庫数'),
                'field' => 'out_qty',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g("期間中の出庫数です。") . "<br>"
                    . _g("明細は[資材管理]-[受払履歴]画面で確認できます。"),
            ),
            array(
                'label' => _g('日数'),
                'field' => 'date_count',
                'width' => '40',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('在庫回転日数'),
                'field' => 'turnoverspan',
                'width' => '83',
                'type' => 'numeric',
                'helpText_noEscape' => _g("((期間前在庫額 + 期間末在庫額) ÷ 2) ÷(期間中出庫額 ÷ 期間日数)。") . "<br>"
                    . _g("その製品が入庫してから出庫されるまでの平均日数をあらわします。日数が短いほど在庫管理の効率がよいといえます。") . "<br>"
                    . _g("計算対象期間は、横軸が「年月」の場合は１ヶ月、「日」の場合は1日、その他の場合は左側の表示条件で指定された期間です。"),
            ),
            array(
                'label' => _g('在庫回転率(回/年)'),
                'field' => 'turnover',
                'width' => '110',
                'type' => 'numeric',
                'helpText_noEscape' => _g("(期間中出庫額 ÷ 期間日数 × 365) ÷ ((期間前在庫額 + 期間末在庫額) ÷ 2)。") . "<br>"
                    . _g("その製品が、入庫→出庫のサイクルを1年に平均何回繰り返すかをあらわします。値が高いほど在庫管理の効率がよいといえます。") . "<br>"
                    . _g("計算対象期間は、横軸が「年月」の場合は１ヶ月、「日」の場合は1日、その他の場合は左側の表示条件で指定された期間です。"),
            ),
        );
            if ($isShowProfit) {
                $form['gen_columnArray'][] =
                array(
                    'label' => _g('粗利(製番品目のみ)'),
                    // ロケ指定されているときは表示しない
                    'field' => ($isLocationMode ? "" : "profit"),
                    'colorCondition' => array("#cccccc" => ($isLocationMode ? "true" : "false")),
                    'width' => '80',
                    'type' => 'numeric',
                    'helpText_noEscape' => _g('期間中の粗利です。製番品目のみです。') . '<br>'
                        . _g('原価リスト画面の「粗利」と同じです。（未納品分は受注金額、納品済分は納品金額に対する粗利となります。）') . "<br><br>"
                        . _g('正確な粗利を出すには、実績登録において製造時間を登録している必要があります。利益と原価の計算方法については、原価リスト画面のチップヘルプを参照してください。') . "<br><br>"
                        . _g('なお、表示条件の「グラフ横軸」に「ロケーション」を指定したり、表示条件の「ロケーション」に「すべて」以外を指定した場合、この欄に数字は表示されません。ロケーション別の原価は計算できないためです。'),
                );
                $form['gen_columnArray'][] =
                array(
                    'label' => _g('交差比率(製番品目のみ)'),
                    // ロケ指定されているときは表示しない
                    'field' => ($isLocationMode ? "" : "crossper"),
                    'colorCondition' => array("#cccccc" => ($isLocationMode ? "true" : "false")),
                    'width' => '80',
                    'type' => 'numeric',
                    'helpText_noEscape' => _g("期間中粗利 ÷ ((期間前在庫額 + 期間末在庫額) ÷ 2)。") . "<br>"
                        . _g("製番品目のみです。") . "<br>"
                        . _g("販売の効率性をあらわす指標です。通常、利益率と回転率(販売数)はトレードオフの関係にあることが多いですが、この値が高い製品は両者のバランスが高いレベルでとれているといえます。") . "<br>"
                        . _g("計算対象期間は、横軸が「年月」の場合は１ヶ月、「日」の場合は1日、その他の場合は左側の表示条件で指定された期間です。") . "<br><br>"
                        . _g("なお、表示条件の「グラフ横軸」に「ロケーション」を指定したり、表示条件の「ロケーション」に「すべて」以外を指定した場合、この欄に数字は表示されません。ロケーション別の原価は計算できないためです。"),
                );
            }
            $form['gen_columnArray'][] =
            array(
                'label' => _g('品目'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_code" || @$form['gen_search_horiz'] == "item_name" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_code')",
            );
            $form['gen_columnArray'][] =
            array(
                'label' => _g('日'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "date" || @$form['gen_search_horiz'] == "month" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=date')",
            );
            $form['gen_columnArray'][] =
            array(
                'label' => _g('ロケ'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "location" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=location')",
            );
    }

    function getChartData($query, $chartLegend, $chartLegend2)
    {
        global $gen_db;

        $res = $gen_db->getArray($query);
        $chartData = array();
        if (is_array($res)) {
            // 見出し
            if (isset($chartLegend2)) {
                $chartData[] = array(
                    '',
                    $chartLegend . " (" . _g("左目盛り") . ")",
                    $chartLegend2 . " (" . _g("右目盛り") . ")",
                );
            } else {
                $chartData[] = array(
                    '',
                    $chartLegend,
                );
            }

            // データ
            $res = array_slice($res, 0, GEN_CHART_HORIZ_MAX);
            foreach ($res as $row) {
                $rowData = array(
                    $row["show"],
                    $row["data1"],
                );
                if (isset($row["data2"])) {
                    $rowData[] = $row["data2"];
                } else if (isset($chartLegend2)) {
                    $rowData[] = "";
                }
                $chartData[] = $rowData;
            }
        }
        return $chartData;
    }
}
