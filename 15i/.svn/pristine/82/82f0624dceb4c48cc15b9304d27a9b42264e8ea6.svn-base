<?php

class Report_Achievement_List extends Base_ListBase
{

    var $horizColumnText;
    var $drillDownLinkParam;
    var $autoAddColumn;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_master.item_group_id, item_group_name
            from item_master
            inner join item_group_master on item_master.item_group_id = item_group_master.item_group_id
            order by item_master.item_group_id";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select item_master.item_group_id_2, item_group_name
            from item_master
            inner join item_group_master on item_master.item_group_id_2 = item_group_master.item_group_id
            order by item_master.item_group_id_2";
        $option_item_group_2 = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select item_master.item_group_id_3, item_group_name
            from item_master
            inner join item_group_master on item_master.item_group_id_3 = item_group_master.item_group_id
            order by item_master.item_group_id_3";
        $option_item_group_3 = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = 'select equip_id, equip_name from equip_master order by equip_code';
        $equipOptions = $gen_db->getHtmlOptionArray($query, true);

        $query = 'select process_id, process_name from process_master order by process_code';
        $processOptions = $gen_db->getHtmlOptionArray($query, true);

        $query = 'select section_id, section_name from section_master order by section_code';
        $sectionOptions = $gen_db->getHtmlOptionArray($query, true);
        
        // ドリルダウン機能では、月に関係なく月末日が「31」と指定されている。これを実際の月末日に置き換える必要がある。
        $to = @$form['gen_search_date_to'];
        if (substr($to, -2) == "31") {
            $form['gen_search_date_to'] = date('Y-m-d', strtotime(date('Y-m-0', strtotime(str_replace("31", "1", $to) . " +1 month"))));
        }

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('グラフ横軸'),
                'type' => 'select',
                'field' => 'horiz',
                'nosql' => 'true',
                'onChange_noEscape' => 'onHorizVertChange()',
                'options' => array('date' => _g("製造日"), 'date_ym' => _g("製造年月"), 'item_code' => _g('品目コード'), 'item_name' => _g('品目名'), 'worker' => _g('作業者'), 'equip' => _g('設備'), 'process' => _g('工程'), 'section' => _g('部門'), 'waster' => _g('不適合理由'), 'item_group_id' => _g('品目グループ1'), 'item_group_id_2' => _g('品目グループ2'), 'item_group_id_3' => _g('品目グループ3')),
            ),
            array(
                'label' => _g('グラフ縦軸1 (棒)'),
                'type' => 'select',
                'field' => 'vert',
                'nosql' => 'true',
                'onChange_noEscape' => 'onHorizVertChange()',
                'options' => array('qty' => _g('製造数(良品数)'), 'waster' => _g('不適合数'), 'hour' => _g('作業時間'), 'workcost' => _g('作業工賃'), 'qty_avg' => _g('時間あたり数量'), 'waster_avg' => _g('時間あたり不適合数'), 'waster_avg_qty' => _g('不適合率')),
            ),
            array(
                'label' => _g('グラフ縦軸2 (折線)'),
                'type' => 'select',
                'field' => 'vert2',
                'nosql' => 'true',
                'onChange_noEscape' => 'onHorizVert2Change()',
                'options' => array('' => '', 'qty' => _g('製造数(良品数)'), 'waster' => _g('不適合数'), 'hour' => _g('作業時間'), 'workcost' => _g('作業工賃'), 'qty_avg' => _g('時間あたり数量'), 'waster_avg' => _g('時間あたり不適合数'), 'waster_avg_qty' => _g('不適合率')),
            ),
            array(
                'label' => _g('期間'),
                'type' => 'dateFromTo',
                'field' => 'date',
                // 期間デフォルト : 今月
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'nosql' => 'true',
                'size' => '80'
            ),
            array(
                'label' => _g('品目グループ1'),
                'type' => 'select',
                'field' => 'item_group_id',
                'options' => $option_item_group,
            ),
            array(
                'label' => _g('品目グループ2'),
                'type' => 'select',
                'field' => 'item_group_id_2',
                'options' => $option_item_group_2,
            ),
            array(
                'label' => _g('品目グループ3'),
                'type' => 'select',
                'field' => 'item_group_id_3',
                'options' => $option_item_group_3,
            ),
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'dropdownCategory' => 'item',
                'size' => '150',
                'field' => 'item_id',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('作業者'),
                'type' => 'dropdown',
                'dropdownCategory' => 'worker',
                'size' => '150',
                'field' => 'worker_id',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('設備'),
                'type' => 'select',
                'field' => 'equip_id',
                'options' => $equipOptions,
            ),
            array(
                'label' => _g('工程'),
                'type' => 'select',
                'field' => 'process_id',
                'options' => $processOptions,
            ),
            array(
                'label' => _g('部門'),
                'type' => 'select',
                'field' => 'section_id',
                'options' => $sectionOptions,
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
        if (!isset($form['gen_search_date_from']) || !Gen_String::isDateString($form['gen_search_date_from']) 
                || !isset($form['gen_search_date_to']) || !Gen_String::isDateString($form['gen_search_date_to'])) {
            $from = "2005-01-02";   // fromとtoを逆にすることで「データなし」にする
            $to = "2005-01-01";
            $form['gen_message_noEscape'] = "<font color='red'>" . _g("表示期間を設定してください。") . '</font>';
        } else {
            $from = $form['gen_search_date_from'];
            $to = $form['gen_search_date_to'];
        }

        // 期間制限（後のdate_masterの処理を考慮して）
        if (strtotime($from) < strtotime(date('2005-01-01'))) {
            $from = "2005-01-01";
        }
        if (strtotime($to) < strtotime(date('2005-01-01'))) {
            $to = "2005-01-01";
        }

        // 横軸
        switch (@$form['gen_search_horiz']) {
            case 'item_code':   // 品目コード
                $horizColumn = "max(item_code)";
                $this->horizColumnText = _g("品目コード");
                $table = "achievement inner join (select item_id as iid, item_name, item_code from item_master) as t_item_master on achievement.item_id = t_item_master.iid";
                $dateColumn = "achievement_date";
                $groupByColumn = "achievement.item_id";
                $this->drillDownLinkParam = "gen_search_item_id=[groupkey]";
                $this->autoAddColumn = array("item_name", _g("品目名"));
                break;
            case 'item_name':   // 品目名
                $horizColumn = "max(item_name)";
                $this->horizColumnText = _g("品目名");
                $table = "achievement inner join (select item_id as iid, item_code, item_name from item_master) as t_item_master on achievement.item_id = t_item_master.iid";
                $dateColumn = "achievement_date";
                $groupByColumn = "achievement.item_id";
                $this->drillDownLinkParam = "gen_search_item_id=[groupkey]";
                $this->autoAddColumn = array("item_code", _g("品目コード"));
                break;
            case 'worker':    // 作業者
                $horizColumn = "max(case when achievement.worker_id is null then '" . _g("(作業者なし)") . "' else worker_name end)";
                $this->horizColumnText = _g("作業者名");
                $table = "achievement left join (select worker_id as wid, worker_name from worker_master) as t_worker on achievement.worker_id = t_worker.wid";
                $dateColumn = "achievement_date";
                $groupByColumn = "achievement.worker_id";
                $this->drillDownLinkParam = "gen_search_worker_id=[groupkey]";
                break;
            case 'equip':    // 設備
                $horizColumn = "max(case when achievement.equip_id is null then '" . _g("(設備なし)") . "' else equip_name end)";
                $this->horizColumnText = _g("設備名");
                $table = "achievement left join (select equip_id as eid, equip_name from equip_master) as t_equip on achievement.equip_id = t_equip.eid";
                $dateColumn = "achievement_date";
                $groupByColumn = "achievement.equip_id";
                $this->drillDownLinkParam = "gen_search_equip_id=[groupkey]";
                break;
            case 'process':    // 工程
                $horizColumn = "max(process_name)";
                $this->horizColumnText = _g("工程名");
                $table = "achievement inner join (select process_id as pid, process_name from process_master) as t_process on achievement.process_id = t_process.pid";
                $dateColumn = "achievement_date";
                $groupByColumn = "achievement.process_id";
                $this->drillDownLinkParam = "gen_search_process_id=[groupkey]";
                break;
            case 'section':    // 部門
                $horizColumn = "max(case when achievement.section_id is null then '" . _g("(部門なし)") . "' else section_name end)";
                $this->horizColumnText = _g("部門名");
                $table = "achievement left join (select section_id, section_name from section_master) as t_section on achievement.section_id = t_section.section_id";
                $dateColumn = "achievement_date";
                $groupByColumn = "achievement.section_id";
                $this->drillDownLinkParam = "gen_search_section_id=[groupkey]";
                break;
            case 'waster':    // 不適合理由
                // これのときは、縦軸が不適合数・不適合率である必要がある（他の数値は無意味）
                $horizColumn = "max(waster_name)";
                $this->horizColumnText = _g("不適合理由");
                $table = "
                    -- 不良数以外の数値関係項目（achievement_quantity等）は品目別に別途集計するため、ここでは含めない。そのためにサブクエリにしている
                    (select achievement_id, achievement_date, order_detail_id, process_id, item_id, worker_id, equip_id, section_id from achievement) as achievement
                    inner join (select achievement_id, waster_id, waster_quantity from waster_detail) as t_waster_detail
                        on achievement.achievement_id = t_waster_detail.achievement_id
                    inner join (select waster_id, waster_name from waster_master) as t_waster
                        on t_waster_detail.waster_id = t_waster.waster_id
                    -- 数値関係は品目ごとに集計する必要がある（不適合が登録されていない実績の分も含める）
                    left join (select item_id as iid, sum(achievement_quantity) as achievement_quantity, sum(work_minute) as work_minute from achievement
                    " . (is_numeric(@$form['gen_search_item_group_id']) ? "inner join (select item_id as iid, item_group_id, item_group_id_2, item_group_id_3 from item_master) as t_item on achievement.item_id = t_item.iid" : "") . "
                    [Where] and achievement_date between '{$from}'::date and '{$to}'::date
                    group by item_id
                    ) as t_ach_qty
                    on achievement.item_id = t_ach_qty.iid
                ";
                $dateColumn = "achievement_date";
                $groupByColumn = "t_waster_detail.waster_id";
                $this->drillDownLinkParam = "gen_search_waster_id=[groupkey]";
                break;
            case 'date_ym':    // 製造年月
                $horizColumn = "to_char(date_trunc('month',date),'YYYY-MM')";
                $this->horizColumnText = _g("年月");
                $table = "date_master left join achievement on date_master.date = achievement.achievement_date";
                $dateColumn = "date_master.date";
                $groupByColumn = $horizColumn;
                $this->drillDownLinkParam = "gen_search_date_from=[groupkey]-01&gen_search_date_to=[groupkey]-31";
                break;

            case 'item_group_id':       // 品目グループ1
            case 'item_group_id_2':     // 品目グループ2
            case 'item_group_id_3':     // 品目グループ3
                switch ($form['gen_search_horiz']) {
                    case 'item_group_id':
                        $name = _g("品目グループ1");
                        break;
                    case 'item_group_id_2':
                        $name = _g("品目グループ2");
                        break;
                    case 'item_group_id_3':
                        $name = _g("品目グループ3");
                        break;
                }

                $horizColumn = "max(case when item_group_name is null then '" . _g("(グループなし)") . "' else item_group_name end)";
                $this->horizColumnText = $name;
                $table = "achievement
                            left join (select item_id as iid, {$form['gen_search_horiz']} as item_group_id_for_key from item_master) as t_item_master on achievement.item_id = t_item_master.iid
                            left join (select item_group_id as igid, item_group_name from item_group_master) as t_item_group_master
                                on t_item_master.item_group_id_for_key = t_item_group_master.igid
                            ";
                $dateColumn = "achievement_date";
                $groupByColumn = "t_item_master.item_group_id_for_key";
                $this->drillDownLinkParam = "gen_search_{$form['gen_search_horiz']}=[groupkey]";
                break;

            default:        // 製造日
                $horizColumn = "to_char(date,'YYYY-MM-DD')";
                $this->horizColumnText = _g("製造日");
                $table = "date_master left join achievement on date_master.date = achievement.achievement_date";
                $dateColumn = "date_master.date";
                $groupByColumn = $horizColumn;
                $this->drillDownLinkParam = "gen_search_date_from=[groupkey]&gen_search_date_to=[groupkey]";
                $form['gen_search_horiz'] = "date";
                break;
        }

        // 縦軸
        //（引数の2番目以降は戻り値の受け取り用）
        $this->getVertParam(@$form['gen_search_vert'], $vertColumn, $chartTitle, $chartLegend, $chartBaloon);
        if (@$form['gen_search_vert2'] != "") {
            $this->getVertParam(@$form['gen_search_vert2'], $vertColumn2, $chartTitle2, $chartLegend2, $chartBaloon2);
            $chartTitle .= ", " . $chartTitle2;
        }

        // グラフ, 表 共通のSQL。グラフでは「show」が横軸、「data1」が縦軸に表示される
        $this->selectQuery = "
            select
                {$horizColumn} as show
                ,{$vertColumn} as data1
                " . (@$vertColumn2 != "" ? ",{$vertColumn2} as data2" : "") . "
                " . (isset($this->autoAddColumn) ? ",max({$this->autoAddColumn[0]}) as autoadd" : "") . "
                ,{$groupByColumn} as groupkey
                ,sum(achievement_quantity) as qty
                ,round(sum(work_minute) / 60.000,2) as work_hour
                ,round(sum(work_minute * charge_price)) as work_cost
                ,sum(waster_quantity) as waster
                ,(case when sum(work_minute)=0 then null else round(sum(achievement_quantity) / sum(work_minute) * 60.000,2) end) as qty_avg
                ,(case when sum(work_minute)=0 then null else round(sum(waster_quantity) / sum(work_minute) * 60.000,2) end) as waster_avg
                ,(case when sum(coalesce(achievement_quantity,0)+coalesce(waster_quantity,0))=0 then null else round(sum(waster_quantity) / sum(coalesce(achievement_quantity,0)+coalesce(waster_quantity,0)) * 100.000,2) end) as waster_avg_qty
            from
                {$table}
                " . (is_numeric(@$form['gen_search_item_group_id']) || is_numeric(@$form['gen_search_item_group_id_2']) || is_numeric(@$form['gen_search_item_group_id_3']) ? "
                inner join (select item_id as iid, item_group_id, item_group_id_2, item_group_id_3 from item_master) as t_item on achievement.item_id = t_item.iid" : "") . "
                " . (@$form['gen_search_horiz'] != "waster" ? "left join (select achievement_id, sum(waster_quantity) as waster_quantity from waster_detail group by achievement_id) as t_waster_detail on achievement.achievement_id = t_waster_detail.achievement_id" : "") . "
                left join (select order_detail_id, process_id as pid, charge_price from order_process) as t_order_process on achievement.order_detail_id = t_order_process.order_detail_id
                    and achievement.process_id = t_order_process.pid
            [Where]
                and {$dateColumn} between '{$from}'::date and '{$to}'::date
            group by
                {$groupByColumn}
            [Orderby]
        ";
        $this->orderbyDefault = "show";

        if (stripos($this->selectQuery, "date_master") !== FALSE) {
            // 横軸が時系列（月、もしくは日）の場合
            // 今回の表示範囲が日付基準テーブルになければ、日付レコードを作る
            Gen_Date::makeDateMaster($from, $to);
        }

        // イレギュラーな形ではあるが、ここでいったん setViewParamを呼び出す。
        // $this->getOrderByArray で columnArrayを必要とするため。
        $this->setViewParam($form);

        if (!isset($form['gen_search_showChart']) || $form['gen_search_showChart'] != "1") {
            // グラフ用にSQLを作成（以下のセクションはListBaseと同じ）
            $user_id = Gen_Auth::getCurrentUserId();
            $action = get_class($this);
            $orderbyArr = $this->getOrderByArray($form, $this->orderbyDefault, $user_id, $action);
            $whereStr = $this->getSearchCondition($form, $form['gen_searchControlArray']);
            $orderbyStr = $this->makeOrderBy($orderbyArr);
            $chartQuery = str_replace('[Where]', $whereStr, $this->selectQuery);
            $chartQuery = str_replace('[Orderby]', $orderbyStr, $chartQuery);
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

    function getVertParam($vert, &$vertColumn, &$chartTitle, &$chartLegend, &$chartBaloon)
    {
        global $gen_db;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        switch ($vert) {
            case 'waster':   // 不適合数
                $vertColumn = "sum(waster_quantity)";
                $chartTitle = _g("不適合数");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            case 'hour':   // 作業時間
                $vertColumn = "round(sum(work_minute) / 60.000,2)";
                $chartTitle = _g("作業時間(h)");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category] " . _g("hour") . " [value]";
                break;
            case 'workcost':   // 作業工賃
                $vertColumn = "round(sum(work_minute * charge_price))";
                $chartTitle = sprintf(_g("作業工賃(%s)"), $keyCurrency);
                $chartLegend = $chartTitle;
                $chartBaloon = "[category] {$keyCurrency} [value]";
                break;
            case 'qty_avg':   // 時間あたり数量
                $vertColumn = "(case when sum(work_minute)=0 then null else round(sum(achievement_quantity) / sum(work_minute) * 60.000,2) end)";
                $chartTitle = _g("時間あたり数量");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]／hour";
                break;
            case 'waster_avg':   // 時間あたり不適合数
                $vertColumn = "(case when sum(work_minute)=0 then null else round(sum(waster_quantity) / sum(work_minute) * 60.000,2) end)";
                $chartTitle = _g("時間あたり不適合数");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]／hour";
                break;
            case 'waster_avg_qty':   // 不適合率
                $vertColumn = "(case when sum(coalesce(achievement_quantity,0)+coalesce(waster_quantity,0))=0 then null else round(sum(waster_quantity) / sum(coalesce(achievement_quantity,0)+coalesce(waster_quantity,0)) * 100.000,2) end)";
                $chartTitle = _g("不適合率(％)");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]   [value]％";
                break;
            default:        // 製造数量
                $vertColumn = "sum(achievement_quantity)";
                $chartTitle = _g("製造数(良品数)");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
        }
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("製造実績レポート");
        $form['gen_menuAction'] = "Menu_Home";
        $form['gen_listAction'] = "Report_Achievement_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = '';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("レポート");

        if (!isset($form['gen_message_noEscape'])) {
            $form['gen_message_noEscape'] = sprintf(_g("グラフに表示されるデータは最初の%s件までです。"), GEN_CHART_HORIZ_MAX);
        }

        $msg = _g("グラフ横軸が「不適合理由」のときは、グラフ縦軸が「不適合数」「不適合率」「時間あたり不適合数」のいずれかである必要があります。");
        $form['gen_javascript_noEscape'] = "
            function onHorizVertChange() {
                if ($('#gen_search_horiz').val()!='waster') return;
                var vert = $('#gen_search_vert').val();
                if (vert == 'waster' || vert == 'waster_avg' || vert == 'waster_avg_qty') return;

                alert('$msg');
                $('#gen_search_vert').val('waster');
            }
            function onHorizVert2Change() {
                if ($('#gen_search_horiz').val()!='waster') return;
                var vert = $('#gen_search_vert2').val();
                if (vert == 'waster' || vert == 'waster_avg' || vert == 'waster_avg_qty') return;

                alert('$msg');
                $('#gen_search_vert2').val('waster');
            }

            // ドリルダウン
            function drillDown(linkParam) {
                linkParam += '&report=" . h(@$form['report']) . "';
                linkParam += '&gen_search_vert=" . h(@$form['gen_search_vert']) . "';
                " .
                (@$form['gen_search_horiz'] == "date" || @$form['gen_search_horiz'] == "date_ym" || @$form['gen_search_horiz'] == "" ? "" :
                        "linkParam += '&gen_search_date_from=" . h(@$form['gen_search_date_from']) . "';
                     linkParam += '&gen_search_date_to=" . h(@$form['gen_search_date_to']) . "';
                    ")
                . "
                location.href = 'index.php?action=" . h($form['gen_listAction']) . "&' + linkParam;
            }
        ";

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        // 固定列
        $form['gen_fixColumnArray'] = array(
            array(
                'label' => $this->horizColumnText, // 横軸
                'width' => '200',
                'field' => 'show',
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

        // スクロール列
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('製造数(良品数)'),
                'field' => 'qty',
                'width' => '100',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('作業時間(h)'),
                'field' => 'work_hour',
                'width' => '100',
                'type' => 'numeric',
            ),
            array(
                'label' => sprintf(_g('作業工賃(%s)'), $keyCurrency),
                'field' => 'work_cost',
                'width' => '100',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('不適合数'),
                'field' => 'waster',
                'width' => '100',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('時間あたり製造数(良品数)'),
                'field' => 'qty_avg',
                'width' => '110',
                'type' => 'numeric',
                'visible' => (@$form['gen_search_horiz'] != "waster")
            ),
            array(
                'label' => _g('時間あたり不適合数'),
                'field' => 'waster_avg',
                'width' => '110',
                'type' => 'numeric',
            ),
            array(
                'field' => 'waster_avg_qty',
                'label' => _g('不適合率(％)'),
                'width' => '100',
                'type' => 'numeric',
            ),
            // ドリルダウン項目
            array(
                'label' => _g('品目'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_code" || @$form['gen_search_horiz'] == "item_name" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_code')",
            ),
            array(
                'label' => _g('作業者'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "worker" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=worker')",
            ),
            array(
                'label' => _g('設備'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "equip" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=equip')",
            ),
            array(
                'label' => _g('工程'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "process" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=process')",
            ),
            array(
                'label' => _g('部門'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "section" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=section')",
            ),
            array(
                'label' => _g('製造日'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "date" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=date')",
            ),
            array(
                'label' => _g('品目グループ1'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_group_id" || @$form['gen_search_horiz'] == "delivery_header_id" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_group_id')",
            ),
            array(
                'label' => _g('品目グループ2'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_group_id_2" || @$form['gen_search_horiz'] == "delivery_header_id" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_group_id_2')",
            ),
            array(
                'label' => _g('品目グループ3'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_group_id_3" || @$form['gen_search_horiz'] == "delivery_header_id" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_group_id_3')",
            ),
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
            $res = array_slice($res,0 ,GEN_CHART_HORIZ_MAX);
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