<?php

class Report_Delivery_List extends Base_ListBase
{

    var $horizColumnText;
    var $vertColumnText;
    var $drillDownLinkParam;
    var $autoAddColumn;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        // セレクタ選択肢
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

        $query = "select customer_master.customer_group_id_1, customer_group_name
            from customer_master
            inner join customer_group_master on customer_master.customer_group_id_1 = customer_group_master.customer_group_id
            order by customer_group_master.customer_group_code";
        $option_customer_group_1 = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select customer_master.customer_group_id_2, customer_group_name
            from customer_master
            inner join customer_group_master on customer_master.customer_group_id_2 = customer_group_master.customer_group_id
            order by customer_group_master.customer_group_code";
        $option_customer_group_2 = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select customer_master.customer_group_id_3, customer_group_name
            from customer_master
            inner join customer_group_master on customer_master.customer_group_id_3 = customer_group_master.customer_group_id
            order by customer_group_master.customer_group_code";
        $option_customer_group_3 = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = 'select section_id, section_name from section_master order by section_code';
        $sectionOptions = $gen_db->getHtmlOptionArray($query, true);
        
        $query = 'select worker_id, worker_name from worker_master order by worker_code';
        $workerOptions = $gen_db->getHtmlOptionArray($query, true);
        
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
                'options' => array('date_ym' => _g("納品年月"), 'date' => _g("納品日"), 'delivery_no' => _g("納品書番号"), 'item_code' => _g('品目コード'), 'item_name' => _g('品目名'), 'section' => _g('部門'), 'worker' => _g('担当者'), 'customer_no' => _g('得意先コード'), 'customer' => _g('得意先名'), 'delivery_customer_no' => _g('発送先コード'), 'delivery_customer' => _g('発送先名'), 'item_group_id' => _g('品目グループ1'), 'item_group_id_2' => _g('品目グループ2'), 'item_group_id_3' => _g('品目グループ3'), 'customer_group_id_1' => _g('取引先グループ1'), 'customer_group_id_2' => _g('取引先グループ2'), 'customer_group_id_3' => _g('取引先グループ3')),
            ),
            array(
                'label' => _g('グラフ縦軸1 (棒)'),
                'type' => 'select',
                'field' => 'vert',
                'nosql' => 'true',
                // 12iでは販売レポートにおける実績原価・実績粗利・実績利益率を廃止した。
                // ジェネシスの実績原価は受注単位で計算されるので、納品基準では正確な値が出せないため（分納があった場合など）
                'options' => array('amount' => _g("納品額"), 'qty' => _g('納品数量'), 'count' => _g('納品件数'), 'sales_basecost' => _g('販売原価'), 'sales_profit' => _g('販売粗利'), 'sales_profitper' => _g('販売利益率'), 'amount_avg' => _g('平均納品額'), 'qty_avg' => _g('平均納品数量')),
            ),
            array(
                'label' => _g('グラフ縦軸2 (折線)'),
                'type' => 'select',
                'field' => 'vert2',
                'nosql' => 'true',
                'options' => array('' => '', 'amount' => _g("納品額"), 'qty' => _g('納品数量'), 'count' => _g('納品件数'), 'sales_basecost' => _g('販売原価'), 'sales_profit' => _g('販売粗利'), 'sales_profitper' => _g('販売利益率'), 'amount_avg' => _g('平均納品額'), 'qty_avg' => _g('平均納品数量')),
            ),
            array(
                'label' => _g('期間'),
                'type' => 'dateFromTo',
                'field' => 'date',
                // デフォルト：　過去1年
                'defaultFrom' => date('Y-m-01', strtotime(date('Y-m-01') . " -12 month")),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'nosql' => 'true',
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
                'label' => _g('取引先グループ1'),
                'type' => 'select',
                'field' => 'customer_group_id_1',
                'options' => $option_customer_group_1,
            ),
            array(
                'label' => _g('取引先グループ2'),
                'type' => 'select',
                'field' => 'customer_group_id_2',
                'options' => $option_customer_group_2,
            ),
            array(
                'label' => _g('取引先グループ3'),
                'type' => 'select',
                'field' => 'customer_group_id_3',
                'options' => $option_customer_group_3,
            ),
            array(
                'label' => _g('品目'),
                'field' => 'item_id',
                'type' => 'dropdown',
                'size' => '150',
                'dropdownCategory' => 'item',
                'nosql' => 'true',
            ),
            array(
                'label' => _g('得意先'),
                'field' => 'delivery_header___customer_id',
                'type' => 'dropdown',
                'size' => '150',
                'dropdownCategory' => 'customer',
            ),
            array(
                'label' => _g('発送先'),
                'field' => 'delivery_header___delivery_customer_id',
                'type' => 'dropdown',
                'size' => '150',
                'dropdownCategory' => 'delivery_customer',
            ),
            array(
                'label' => _g('部門'),
                'type' => 'select',
                'field' => 'section_id',
                'options' => $sectionOptions,
            ),
            array(
                'label' => _g('担当者'),
                'type' => 'select',
                'field' => 'worker_id',
                'options' => $workerOptions,
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

        // 納品日基準か検収日基準か
        $query = "select receivable_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);
        $dateCol = ($timing == '1' ? 'inspection' : 'delivery') . '_date';

        // 横軸
        switch (@$form['gen_search_horiz']) {
            // $table には必ず received_detail が含まれるようにすること
            // (表示条件で品目や品目グループ が指定されたときのため）

            case 'item_code':   // 品目コード
                $horizColumn = "max(item_code)";
                $this->horizColumnText = _g("品目コード");
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    left join (select item_id as iid, item_code, item_name from item_master) as t_item_master on received_detail.item_id = t_item_master.iid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "received_detail.item_id";
                $this->drillDownLinkParam = "gen_search_item_id=[groupkey]";
                $this->autoAddColumn = array("item_name", _g("品目名"));
                break;
            case 'item_name':   // 品目名
                $horizColumn = "max(item_name)";
                $this->horizColumnText = _g("品目名");
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    left join (select item_id as iid, item_code, item_name from item_master) as t_item_master on received_detail.item_id = t_item_master.iid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "received_detail.item_id";
                $this->drillDownLinkParam = "gen_search_item_id=[groupkey]";
                $this->autoAddColumn = array("item_code", _g("品目コード"));
                break;
            case 'delivery_header_id':    // 納品書番号
                $horizColumn = "max(delivery_header.delivery_no)";
                $this->horizColumnText = _g("納品書番号");
                $table = "
                    delivery_header
                    inner join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "delivery_header.delivery_header_id";
                $this->drillDownLinkParam = "gen_search_item_id=[groupkey]";
                break;
            case 'section':    // 部門
                $horizColumn = "max(case when received_header.section_id is null then '" . _g("(部門なし)") . "' else section_name end)";
                $this->horizColumnText = _g("部門名");
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    left join (select section_id as sid, section_code, section_name from section_master) as t_sec on received_header.section_id = t_sec.sid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "received_header.section_id";
                $this->drillDownLinkParam = "gen_search_section_id=[groupkey]";
                break;
            case 'worker':    // 担当者
                $horizColumn = "max(case when received_header.worker_id is null then '" . _g("(担当者なし)") . "' else worker_name end)";
                $this->horizColumnText = _g("担当者名");
                // サブクエリにsection_idを含めると、表示条件で部門を指定したときにエラーになることに注意
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    left join (select worker_id as wid, worker_code, worker_name from worker_master) as t_worker on received_header.worker_id = t_worker.wid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "received_header.worker_id";
                $this->drillDownLinkParam = "gen_search_worker_id=[groupkey]";
                break;
            case 'customer_no':    // 得意先コード
                $horizColumn = "max(customer_no)";
                $this->horizColumnText = _g("得意先コード");
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    inner join (select customer_id as cid, customer_no, customer_name from customer_master) as t_customer on received_header.customer_id = t_customer.cid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "received_header.customer_id";
                $this->drillDownLinkParam = "gen_search_delivery_header___customer_id=[groupkey]";
                $this->autoAddColumn = array("customer_name", _g("得意先名"));
                break;
            case 'customer':    // 得意先名
                $horizColumn = "max(customer_name)";
                $this->horizColumnText = _g("得意先名");
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    inner join (select customer_id as cid, customer_no, customer_name from customer_master) as t_customer on received_header.customer_id = t_customer.cid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "received_header.customer_id";
                $this->drillDownLinkParam = "gen_search_delivery_header___customer_id=[groupkey]";
                $this->autoAddColumn = array("customer_no", _g("得意先コード"));
                break;
            case 'delivery_customer_no':    // 発送先コード
                $horizColumn = "max(customer_no)";
                $this->horizColumnText = _g("発送先コード");
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    inner join (select customer_id as cid, customer_no, customer_name from customer_master) as t_customer on received_header.delivery_customer_id = t_customer.cid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "received_header.delivery_customer_id";
                $this->drillDownLinkParam = "gen_search_delivery_header___delivery_customer_id=[groupkey]";
                $this->autoAddColumn = array("customer_name", _g("発送先名"));
                break;
            case 'delivery_customer':    // 発送先名
                $horizColumn = "max(customer_name)";
                $this->horizColumnText = _g("発送先名");
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    inner join (select customer_id as cid, customer_no, customer_name from customer_master) as t_customer on received_header.delivery_customer_id = t_customer.cid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "received_header.delivery_customer_id";
                $this->drillDownLinkParam = "gen_search_delivery_header___delivery_customer_id=[groupkey]";
                $this->autoAddColumn = array("customer_no", _g("発送先コード"));
                break;

            case 'date':    // 納品日
                $horizColumn = "to_char(date,'YYYY-MM-DD')";
                $this->horizColumnText = _g("納品日");
                $table = "
                    date_master
                    left join delivery_header on date_master.date = delivery_header.{$dateCol}
                    left join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                ";
                $dateColumn = "date_master.date";
                $groupByColumn = $horizColumn;
                $this->drillDownLinkParam = "gen_search_date_from=[groupkey]&gen_search_date_to=[groupkey]";
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
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    left join (select item_id as iid, {$form['gen_search_horiz']} as item_group_id_for_key from item_master) as t_item_master on received_detail.item_id = t_item_master.iid
                    left join (select item_group_id as igid, item_group_name from item_group_master) as t_item_group_master on t_item_master.item_group_id_for_key = t_item_group_master.igid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "t_item_master.item_group_id_for_key";
                $this->drillDownLinkParam = "gen_search_{$form['gen_search_horiz']}=[groupkey]";
                break;

            case 'customer_group_id_1':     // 取引先グループ1
            case 'customer_group_id_2':     // 取引先グループ2
            case 'customer_group_id_3':     // 取引先グループ3
                switch ($form['gen_search_horiz']) {
                    case 'customer_group_id_1':
                        $name = _g("取引先グループ1");
                        break;
                    case 'customer_group_id_2':
                        $name = _g("取引先グループ2");
                        break;
                    case 'customer_group_id_3':
                        $name = _g("取引先グループ3");
                        break;
                }

                $horizColumn = "max(case when customer_group_name is null then '" . _g("(グループなし)") . "' else customer_group_name end)";
                $this->horizColumnText = $name;
                $table = "
                    delivery_detail
                    inner join delivery_header on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    left join (select customer_id as cid, {$form['gen_search_horiz']} as customer_group_id_for_key from customer_master) as t_customer_master on received_header.customer_id = t_customer_master.cid
                    left join (select customer_group_id as cgid, customer_group_name from customer_group_master) as t_customer_group_master on t_customer_master.customer_group_id_for_key = t_customer_group_master.cgid
                ";
                $dateColumn = $dateCol;
                $groupByColumn = "t_customer_master.customer_group_id_for_key";
                $this->drillDownLinkParam = "gen_search_{$form['gen_search_horiz']}=[groupkey]";
                break;

            default:        // 納品年月
                $horizColumn = "to_char(date_trunc('month',date),'YYYY-MM')";
                $this->horizColumnText = _g("年月");
                $table = "
                    date_master
                    left join delivery_header on date_master.date = delivery_header.{$dateCol}
                    left join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                ";
                $dateColumn = "date_master.date";
                $groupByColumn = $horizColumn;
                $this->drillDownLinkParam = "gen_search_date_from=[groupkey]-01&gen_search_date_to=[groupkey]-31";
                $form['gen_search_horiz'] = "date_ym";
                break;
        }

        // 縦軸
        //（引数の2番目以降は戻り値の受け取り用）
        $this->getVertParam(@$form['gen_search_vert'], $vertColumn, $chartTitle, $chartLegend, $chartBaloon);
        if (@$form['gen_search_vert2'] != "") {
            $this->getVertParam(@$form['gen_search_vert2'], $vertColumn2, $chartTitle2, $chartLegend2, $chartBaloon2);
            $chartTitle .= ", " . $chartTitle2;
        }

        // 共通from句
        $fromQuery = "
            {$table}
            /* tableにreceived_detailが含まれていることが前提 */
            " . (is_numeric(@$form['gen_search_item_group_id']) || is_numeric(@$form['gen_search_item_group_id_2']) || is_numeric(@$form['gen_search_item_group_id_3']) ? "
                inner join (select item_id as iid, item_group_id, item_group_id_2, item_group_id_3 from item_master)
                    as t_item on received_detail.item_id = t_item.iid
            " : "") . "
            /* tableにreceived_headerが含まれていることが前提 */
            " . (is_numeric(@$form['gen_search_customer_group_id_1']) || is_numeric(@$form['gen_search_customer_group_id_2']) || is_numeric(@$form['gen_search_customer_group_id_3']) ? "
                left join (select customer_id as cid, customer_group_id_1, customer_group_id_2, customer_group_id_3 from customer_master)
                    as t_customer_group on received_header.customer_id = t_customer_group.cid
            " : "") . "
        ";

        // 共通where句
        $whereQuery = "
            [Where]
                and {$dateColumn} between '{$from}'::date and '{$to}'::date
                " . (is_numeric(@$form['gen_search_item_id']) ? " and received_detail.item_id = '{$form['gen_search_item_id']}'" : "") . "
        ";

        // グラフ, 表 共通のSQL。グラフでは「show」が横軸、「data1」が縦軸に表示される
        $this->selectQuery = "
        select
            {$horizColumn} as show
            ,{$vertColumn} as data1
            " . (@$vertColumn2 != "" ? ",$vertColumn2 as data2" : "") . "
            " . (isset($this->autoAddColumn) ? ",max({$this->autoAddColumn[0]}) as autoadd" : "") . "
            ,$groupByColumn as groupkey
            ,sum(delivery_amount) as delivery_amount
            ,case when max(total_delivery_amount) = 0 then 0 else round(sum(delivery_amount) / max(total_delivery_amount) * 100, 2) end as component_ratio
            ,sum(case when sum(delivery_amount) = 0 then 0 else sum(delivery_amount) end)
                    over(order by case when max(total_delivery_amount) = 0 then 0 else round(sum(delivery_amount) / max(total_delivery_amount) * 100, 2) end desc) as delivery_cumulative
            ,sum(case when max(total_delivery_amount) = 0 then 0 else round(sum(delivery_amount) / max(total_delivery_amount) * 100, 2) end)
                    over(order by case when max(total_delivery_amount) = 0 then 0 else round(sum(delivery_amount) / max(total_delivery_amount) * 100, 2) end desc) as component_cumulative
            ,case
                when sum(case when max(total_delivery_amount) = 0 then 0 else round(sum(delivery_amount) / max(total_delivery_amount) * 100, 2) end)
                    over(order by case when max(total_delivery_amount) = 0 then 0 else round(sum(delivery_amount) / max(total_delivery_amount) * 100, 2) end desc) <= 80 then 'A'
                when sum(case when max(total_delivery_amount) = 0 then 0 else round(sum(delivery_amount) / max(total_delivery_amount) * 100, 2) end)
                    over(order by case when max(total_delivery_amount) = 0 then 0 else round(sum(delivery_amount) / max(total_delivery_amount) * 100, 2) end desc) <= 90 then 'B'
                else 'C' end as cumulative_rank
            ,sum(delivery_quantity) as delivery_qty
            ,count(distinct delivery_detail.delivery_detail_id) as delivery_count
            ,case when count(distinct delivery_detail.delivery_detail_id) > 0 then round(sum(delivery_amount) / count(distinct delivery_detail.delivery_detail_id)) end as delivery_amount_avg
            ,case when count(distinct delivery_detail.delivery_detail_id) > 0 then round(sum(delivery_quantity) / count(distinct delivery_detail.delivery_detail_id)) end as delivery_qty_avg
            -- 販売粗利・原価・利益率。販売原価未設定の場合は数字を出さないよう、あえてcoalesceはつけない
            ,sum(delivery_detail.sales_base_cost_total) as sales_base_cost
            ,sum(delivery_detail.delivery_amount - delivery_detail.sales_base_cost_total) as sales_profit
            ,case when sum(delivery_amount) <> 0 then
                round(sum(delivery_detail.delivery_amount - delivery_detail.sales_base_cost_total) / sum(delivery_amount) * 100,1)
                end as sales_profit_per
        from
            {$fromQuery}
            left join (select sum(delivery_amount) as total_delivery_amount from {$fromQuery} {$whereQuery}) as t_total on 1=1        {$whereQuery}
        group by
            {$groupByColumn}
        [Orderby]
        ";
        if (@$form['gen_search_horiz'] == "item_code" || @$form['gen_search_horiz'] == "item_name"
                || @$form['gen_search_horiz'] == "item_group_id" || @$form['gen_search_horiz'] == "item_group_id_2" || @$form['gen_search_horiz'] == "item_group_id_3") {
            $this->orderbyDefault = "component_ratio desc, show";
        } else {
            $this->orderbyDefault = "show";
        }

        if (stripos($this->selectQuery, "date_master") !== FALSE) {
            // 横軸が時系列（月、もしくは日）の場合
            // 今回の表示範囲が日付基準テーブルになければ、日付レコードを作る
            Gen_Date::makeDateMaster($from, $to);
        }

        // イレギュラーな形ではあるが、ここでいったん setViewParamを呼び出す。
        // $this->getOrderByArray で columnArrayを必要とするため。
        $this->setViewParam($form);

        if (!isset($form['gen_search_showChart']) || $form['gen_search_showChart'] != "1") {
            // グラフ用にSQLを作成（以下の4行はListBaseと同じ）
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
            case 'qty':   // 数量
                $vertColumn = "sum(delivery_quantity)";
                $chartTitle = _g("納品数量");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            case 'count':   // 納品件数
                $vertColumn = "count(distinct delivery_detail.delivery_detail_id)";
                $chartTitle = _g("納品件数");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category] " . _g("件") . " [value]";
                break;
            case 'sales_basecost':   // 販売原価
                $vertColumn = "sum(delivery_quantity * delivery_detail.sales_base_cost)";
                $chartTitle = _g("販売原価 ");
                $chartLegend = _g("販売原価");
                $chartBaloon = "[category]  {$keyCurrency} [value]";
                break;
            case 'sales_profit':   // 販売粗利
                $vertColumn = "sum(delivery_quantity * (delivery_price - delivery_detail.sales_base_cost))";
                $chartTitle = _g("販売粗利");
                $chartLegend = _g("販売粗利");
                $chartBaloon = "[category]  {$keyCurrency} [value]";
                break;
            case 'sales_profitper':   // 販売利益率
                $vertColumn = "case when sum(delivery_quantity * delivery_price) <> 0 then round(sum(delivery_quantity * (delivery_price - delivery_detail.sales_base_cost)) / sum(delivery_quantity * delivery_price) * 100,1) end";
                $chartTitle = _g("販売利益率");
                $chartLegend = _g("販売利益率");
                $chartBaloon = "[category]  ％ [value]";
                break;
            case 'amount_avg':   // 平均納品額
                $vertColumn = "case when count(distinct delivery_detail.delivery_detail_id) > 0 then round(sum(delivery_quantity * delivery_price) / count(distinct delivery_detail.delivery_detail_id)) end";
                $chartTitle = _g("平均納品額");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            case 'qty_avg':   // 平均納品数量
                $vertColumn = "case when count(distinct delivery_detail.delivery_detail_id) > 0 then round(sum(delivery_quantity) / count(distinct delivery_detail.delivery_detail_id)) end";
                $chartTitle = _g("平均納品数量");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  [value]";
                break;
            default:        // 納品額
                $vertColumn = "sum(delivery_quantity * delivery_price)";
                $chartTitle = _g("納品額");
                $chartLegend = $chartTitle;
                $chartBaloon = "[category]  {$keyCurrency} [value]";
                break;
            // 12iでは販売レポートにおける実績原価・実績粗利・実績利益率を廃止した。
            // ジェネシスの実績原価は受注単位で計算されるので、納品基準では正確な値が出せないため（分納があった場合など）
        }
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("販売レポート");
        $form['gen_menuAction'] = "Menu_Home";
        $form['gen_listAction'] = "Report_Delivery_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = '';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("レポート");

        if (!isset($form['gen_message_noEscape'])) {
            $form['gen_message_noEscape'] = sprintf(_g("グラフに表示されるデータは最初の%s件までです。"), GEN_CHART_HORIZ_MAX);
        }

        $form['gen_javascript_noEscape'] = "
            function drillDown(linkParam) {
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
                'label' => _g('合計納品額'),
                'field' => 'delivery_amount',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('構成比(%)'),
                'field' => 'component_ratio',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('累積納品額'),
                'field' => 'delivery_cumulative',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('累計構成比(%)'),
                'field' => 'component_cumulative',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('区分'),
                'field' => 'cumulative_rank',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g('合計納品数量'),
                'field' => 'delivery_qty',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('納品件数'),
                'field' => 'delivery_count',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('販売原価'),
                'field' => 'sales_base_cost',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('販売粗利'),
                'field' => 'sales_profit',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('販売利益率'),
                'field' => 'sales_profit_per',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('平均納品額'),
                'field' => 'delivery_amount_avg',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('平均納品数量'),
                'field' => 'delivery_qty_avg',
                'type' => 'numeric',
            ),
            // ドリルダウン項目
            array(
                'label' => _g('品目'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_code" || @$form['gen_search_horiz'] == "item_name" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_code')",
            ),
            array(
                'label' => _g('部門'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "section" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=section')",
            ),
            array(
                'label' => _g('担当者'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "worker" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=worker')",
            ),
            array(
                'label' => _g('得意先'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "customer_no" || @$form['gen_search_horiz'] == "customer" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=customer')",
            ),
            array(
                'label' => _g('発送先'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "delivery_customer_no" || @$form['gen_search_horiz'] == "delivery_customer" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=delivery_customer')",
            ),
            array(
                'label' => _g('納品日'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "date" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=date')",
            ),
            array(
                'label' => _g('品目グループ1'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_group_id" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_group_id')",
            ),
            array(
                'label' => _g('品目グループ2'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_group_id_2" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_group_id_2')",
            ),
            array(
                'label' => _g('品目グループ3'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "item_group_id_3" || @$form['gen_search_horiz'] == "delivery_no" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=item_group_id_3')",
            ),
            array(
                'label' => _g('取引先グループ1'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "customer_group_id_1" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=customer_group_id_1')",
            ),
            array(
                'label' => _g('取引先グループ2'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "customer_group_id_2" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=customer_group_id_2')",
            ),
            array(
                'label' => _g('取引先グループ3'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>●</span>",
                'align' => 'center',
                'showCondition' => (@$form['gen_search_horiz'] == "customer_group_id_3" ? "false" : "true"),
                'link' => "javascript:drillDown('" . $this->drillDownLinkParam . "&gen_search_horiz=customer_group_id_3')",
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