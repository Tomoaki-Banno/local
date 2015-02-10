<?php

class Progress_SeibanProgress_List extends Base_ListBase
{

    var $holidayArr;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'notShowMatchBox' => true,
            ),
            array(
                'label' => _g('表示モード'),
                'field' => 'show_mode',
                'type' => 'select',
                'options' => array('0' => _g('納品/実績/受入数'), '1' => _g('受注/オーダー数 (納期日に表示)'), '2' => _g('受注/オーダー数 (期間分散表示)')),
                'default' => '0',
                'nosql' => 'true',
                'helpText_noEscape' => _g('リスト内の数値表示を切り替えます。') . '<br><br>'
                    . '<b>' . _g('納品/実績/受入数') . _g("：") . '</b><br>'
                    . _g("進捗状況を見るのに便利なモードです。") . "<br>"
                    . _g("受注の場合は納品数、内製の場合は製造実績数、注文/外製の場合は受入数を表示します。数値は納品日、もしくは製造日・受入日に表示されます。納品・製造実績・受入の登録がない場合、数値は表示されません。") . '<br><br>'
                    . '<b>' . _g('受注/オーダー数 (受注納期日に表示)') . _g("：") . '</b><br>'
                    . _g('受注やオーダー（製造指示、注文）の状況を見るのに便利なモードです。受注数・オーダー数（内製の場合は製造指示数、注文/外製の場合は発注数）を表示します。数値は納期日に表示されます。') . '<br><br>'
                    . '<b>' . _g('受注/オーダー数 (期間分散表示)') . _g("：") . '</b><br>'
                    . _g('内製の日別の負荷状況を見るのに便利なモードです。受注数・オーダー数（内製の場合は製造指示数、注文/外製の場合は発注数）を表示します。数値は、受注に関しては受注納期、オーダー（内製・注文・外製）に関してはオーダー日からオーダー納期までの間（休日を除く）に分割して表示されます。'),
            ),
            array(
                'label' => _g('表示期間(最大100日)'),
                'field' => 'date',
                'type' => 'dateFromTo',
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'rowSpan' => 2,
                'nosql' => true,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
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
                'label' => _g('発注先・工程コード/名'),
                'field' => 'process_code',
                'field2' => 'process_name',
                'hide' => true,
            ),
            array(
                'label' => _g('手配区分'),
                'field' => 'classification',
                'type' => 'select',
                'options' => Gen_Option::getPartnerClass('search-progress'),
                'default' => null,
                'nosql' => 'true',
                'hide' => true,
            ),
            array(
                'label' => _g('完了受注表示'),
                'field' => 'show_finish_received',
                'type' => 'select',
                'options' => Gen_Option::getTrueOrFalse('search'),
                'default' => 'false',
                'nosql' => 'true',
            ),
        );
        // 表示条件クリアの指定がされていたときの設定。
        // 進捗画面のリンク等からレコード指定でこの画面を開いたときのため。
        if (isset($form['gen_searchConditionClear'])) {
            $form['gen_search_show_mode'] = '0';
            $form['gen_search_show_finish_received'] = 'true';  // 完了データの表示を「する」にしておく。
        }
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->notDateStrToValue('gen_search_date_from', '');
        $converter->notDateStrToValue('gen_search_date_to', '');
        if (@$form['gen_search_date_from'] == '') {
            if (@$form['gen_search_date_to'] == '') {
                // 両方未設定ならデフォルト値に
                unset($form['gen_search_date_from']);
                unset($form['gen_search_date_to']);
            } else {
                $form['gen_search_date_from'] = date('Y-m-d', strtotime($form['gen_search_date_to'] . ' -100 days'));
            }
        } else if (@$form['gen_search_date_to'] == '') {
            $form['gen_search_date_to'] = date('Y-m-d', strtotime($form['gen_search_date_from'] . ' +100 days'));
        }
        $converter->dateSort('gen_search_date_from', 'gen_search_date_to');
        $converter->dateSpan('gen_search_date_from', 'gen_search_date_to', 100);    // PostgreSQLのselectリストは最大1664項目
    }

    function beforeLogic(&$form)
    {
        global $gen_db;

        $from = strtotime($form['gen_search_date_from']);
        $to = strtotime($form['gen_search_date_to']);
        $betweenDateFromTo = "between '" . date('Y-m-d', $from) . "' and '" . date('Y-m-d', $to) . "'";

        // 休日
        $query = "select holiday_master.holiday from holiday_master where holiday between '" . date('Y-m-d', $from) . "' and '" . date('Y-m-d', $to) . "'";
        $holidays = $gen_db->getArray($query);
        $this->holidayArr = array();
        if (is_array($holidays)) {
            foreach ($holidays as $h) {
                $this->holidayArr[] = $h['holiday'];
            }
        }

        $query = "
        create temp table  temp_progress as

            /****** オーダー ******/

            select
                order_detail.seiban as seiban

                ,max(t_order.received_header_id) as received_header_id
                ,max(received_number) as received_number
                ,max(worker_name) as worker_name
                ,max(section_name) as section_name
                ,max(estimate_number) as estimate_number

                ,max(item_master.item_code) as item_code
                ,max(item_master.item_name) as item_name
                ,max(item_master.item_code) || '[br]' || max(item_master.item_name) as item_code_name
                ,max(item_master.item_group_id) as item_group_id
                ,max(item_master.item_group_id_2) as item_group_id_2
                ,max(item_master.item_group_id_3) as item_group_id_3
                ,max(item_master.measure) as measure
                ,max(llc) as llc

                ,max(coalesce(customer_master.customer_no, process_master.process_code)) as process_code
                ,max(coalesce(customer_master.customer_name, process_name || case when t_subcontract_process_customer.customer_name is not null then '[br]' || t_subcontract_process_customer.customer_name else '' end)) as process_name
                ,max(order_process.machining_sequence) as machining_sequence

                ,max(coalesce(process_start_date,order_date)) as order_date
                ,max(coalesce(process_dead_line,order_detail_dead_line)) as dead_line

                ,max(case when order_header.classification=0 and coalesce(order_process.subcontract_partner_id,0)=0 then '" . _g("内製") . "' else case when order_header.classification=1 then '" . _g("注文") . "' else '" . _g("外製") . "' end end) as class

                ,case
                    /* 完了。ちなみにここの場合、完了判断を order_detail_completedだけでは行えない。中間工程の状況は order_detail_completedに反映されないため。 */
                    when sum(coalesce(order_detail_quantity,0) - coalesce(t_ach.achievement_quantity,0) - coalesce(t_acc.accepted_quantity,0))<=0 or sum(case when order_detail_completed then 0 else 1 end)=0 then '" . _g('完了') . "'
                    /* 遅れ */
                    when sum(coalesce(order_detail_quantity,0) - coalesce(t_ach.achievement_quantity,0) - coalesce(t_acc.accepted_quantity,0))>0 and sum(case when order_detail_completed then 0 else 1 end)>0 and max(coalesce(process_dead_line,order_detail_dead_line)) < '" . date('Y-m-d') . "' then '" . _g('遅れ') . "'
                    /* 着手。日付にかかわらず、そのオーダーの実績/受入が1件でも登録されていれば、着手されたとみなす */
                    when max(case when achievement_date is null and accepted_date is null then 0 else 1 end) = 1 then '" . _g('着手') . "'
                    /* 未着手 */
                    else '" . _g('未') . "'
                 end as status

                /* オーダー数合計。以前はここで計算していたが、表示期間だけではなく全期間の合計を計算するよう仕様変更したため、from句で別途計算することにした */
                /* achievement と accepted の join により order_detailが膨らんでいるため、sumではなくmaxする */
                /* （合計はサブクエリにて、オーダー/工程レベルで計算していることに注意） */
                ,round(max(t_order_total.order_total)) as order_total

                /* 実績/受入数合計 */
                ,round(sum(coalesce(t_ach.achievement_quantity,0) + coalesce(t_acc.accepted_quantity,0))) as acc_total

                ";

                for ($day = $from; $day <= $to; $day += 86400) {     // 86400sec = 1day
                    $dateStr = date('Y-m-d', $day);
                    switch (@$form['gen_search_show_mode']) {
                        case '1':
                            // オーダー数表示（オーダー納期日に表示）
                            $query .= ",round(SUM(case when coalesce(process_dead_line, order_detail_dead_line) = '{$dateStr}' then order_detail_quantity else 0 end)) as day" . date('Ymd', $day);
                            break;
                        case '2':
                            // オーダー数表示（期間分散表示）
                            if (in_array($dateStr, $this->holidayArr)) {
                                $col = "0";
                            } else {
                                $col = "round(SUM(case when coalesce(process_start_date,order_date) <= '{$dateStr}' AND coalesce(process_dead_line,order_detail_dead_line) >= '{$dateStr}' then
                                            order_detail_quantity / (
                                                case when
                                                    coalesce(process_dead_line,order_detail_dead_line) - coalesce(process_start_date,order_date) + 1 - (select count(holiday) from holiday_master where holiday between coalesce(process_start_date,order_date) and coalesce(process_dead_line,order_detail_dead_line)) > 0
                                                then
                                                    coalesce(process_dead_line,order_detail_dead_line) - coalesce(process_start_date,order_date) + 1 - (select count(holiday) from holiday_master where holiday between coalesce(process_start_date,order_date) and coalesce(process_dead_line,order_detail_dead_line))
                                                else 1 end )
                                            else 0 end))";
                            }
                            $query .= ",{$col} as day" . date('Ymd', $day);
                            break;
                        default:
                            // 実績/受入数表示
                            $query .= ",round(SUM(coalesce(ach_day" . date('Ymd', $day) . ",0)+coalesce(acc_day" . date('Ymd', $day) . ",0))) as day" . date('Ymd', $day);
                    }
                }
                $query .= "

            from
                order_detail
                left join order_header on order_detail.order_header_id = order_header.order_header_id
                left join (
                    select
                        seiban
                        ,max(dead_line) as received_dead_line
                        ,max(received_header.received_header_id) as received_header_id
                        ,max(received_number) as received_number
                        ,max(case when delivery_completed then 0 else 1 end) as delivery_completed
                        ,max(worker_name) as worker_name
                        ,max(section_master.section_name) as section_name
                        ,max(estimate_number) as estimate_number
                    from
                        received_detail
                        inner join received_header on received_header.received_header_id=received_detail.received_header_id
                        left join worker_master on received_header.worker_id = worker_master.worker_id
                        left join section_master on received_header.section_id = section_master.section_id
                        left join estimate_header on received_header.estimate_header_id = estimate_header.estimate_header_id
                    group by
                        seiban
                    ) as t_order on order_detail.seiban = t_order.seiban
                left join order_process on order_detail.order_detail_id = order_process.order_detail_id
                left join process_master on order_process.process_id = process_master.process_id
                left join item_master on order_detail.item_id = item_master.item_id
                left join customer_master on order_header.partner_id = customer_master.customer_id
                /* 外製先の取得。10iではorder_process.subcontract_partner_idとリンクすることでオーダー発行時のマスタ上の外製先を取得していたが、 */
                /* それだと外製工程オーダーのオーダー先を直接変更したときにそれが反映されないため、外製工程のオーダー先を取得するように変更した。 */
                left join (select subcontract_order_process_no, max(partner_id) as subcontract_partner_id from order_detail inner join order_header on order_detail.order_header_id = order_header.order_header_id group by subcontract_order_process_no) as t_subcontract_process_order
                    on order_process.order_process_no = t_subcontract_process_order.subcontract_order_process_no
                left join customer_master as t_subcontract_process_customer on t_subcontract_process_order.subcontract_partner_id = t_subcontract_process_customer.customer_id
                left join (
                    select
                        order_detail_id
                        ,process_id
                        ,max(achievement_date) as achievement_date
                        ,sum(achievement_quantity) as achievement_quantity
                    	";
                    if (@$form['gen_search_show_mode'] != '1' && @$form['gen_search_show_mode'] != '2') {
                        // 実績/受入数表示
                        for ($day = $from; $day <= $to; $day += 86400) {     // 86400sec = 1day
                            $dateStr = date('Y-m-d', $day);
                            $query .= ",round(SUM(case when achievement_date = '{$dateStr}' then achievement_quantity else 0 end)) as ach_day" . date('Ymd', $day);
                        }
                    }
                    $query .= "
                    from
                     	achievement
                    group by
                     	order_detail_id
                        ,process_id
                    /* 外製工程の外製指示に対する受入を実績として表示 */
                    union all
                    select
                     	order_process.order_detail_id
                        ,order_process.process_id
                        ,max(accepted_date) as achievement_date
                        ,sum(accepted.accepted_quantity) as achievement_quantity
                    	";
                    if (@$form['gen_search_show_mode'] != '1' && @$form['gen_search_show_mode'] != '2') {
                        // 実績/受入数表示
                        for ($day = $from; $day <= $to; $day += 86400) {     // 86400sec = 1day
                            $dateStr = date('Y-m-d', $day);
                            $query .= ",round(SUM(case when accepted_date = '{$dateStr}' then accepted.accepted_quantity else 0 end)) as ach_day" . date('Ymd', $day);
                        }
                    }
                    $query .= "
                    from
                     	accepted
                     	inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                     	inner join order_process on order_detail.subcontract_order_process_no = order_process.order_process_no
                    group by
                     	order_process.order_detail_id
                        ,order_process.process_id
                    ) as t_ach
                    on order_detail.order_detail_id = t_ach.order_detail_id
                        and t_ach.process_id = process_master.process_id
                left join (
                    select
                        order_detail_id
                        ,max(accepted_date) as accepted_date
                        ,sum(accepted_quantity) as accepted_quantity
                    	";
                    if (@$form['gen_search_show_mode'] != '1' && @$form['gen_search_show_mode'] != '2') {
                        // 実績/受入数表示
                        for ($day = $from; $day <= $to; $day += 86400) {     // 86400sec = 1day
                            $dateStr = date('Y-m-d', $day);
                            $query .= ",round(SUM(case when accepted_date = '{$dateStr}' then accepted_quantity else 0 end)) as acc_day" . date('Ymd', $day);
                        }
                    }
                    $query .= "
                    from
                        accepted
                    group by
                        order_detail_id
                    ) as t_acc
                    on order_detail.order_detail_id = t_acc.order_detail_id

                /* 合計計算。以前はselect句で計算していたため、計算期間内の数値のみ合計されていた。09iから全期間の合計をとるため、ここで計算するようにした */
                left join (
                    select
                        order_detail.order_detail_id
                        ,order_process.process_id
                        ,order_detail_quantity as order_total
                    from
                        order_detail
                        left join order_process on order_detail.order_detail_id = order_process.order_detail_id
                    ) as t_order_total
                    on order_detail.order_detail_id = t_order_total.order_detail_id
                        and coalesce(order_process.process_id,-99999) = coalesce(t_order_total.process_id,-99999)

            where
                (order_date {$betweenDateFromTo} or order_detail_dead_line {$betweenDateFromTo} or achievement_date {$betweenDateFromTo} or accepted_date {$betweenDateFromTo} )
                /* 外製工程の外製指示は含めない。ただし受入数を工程行に実績として表示するため、join部の achivementに工夫していることに注意 */
                and (order_detail.subcontract_order_process_no is null or order_detail.subcontract_order_process_no = '')
                " . (@$form['gen_search_seiban'] != "" ? "  and order_detail.seiban = '{$form['gen_search_seiban']}'" : "and order_detail.seiban <> ''") . "
                " . (isset($form['gen_search_classification']) && is_numeric($form['gen_search_classification']) ? " and order_header.classification = '{$form['gen_search_classification']}'" : "") . "
                " . (@$form['gen_search_show_finish_received'] != "true" ? "  and delivery_completed=1 " : "") . "

            group by
                order_detail.seiban
                ,order_header.partner_id
                ,item_master.item_id
                ,process_master.process_id

           /****** 受注 ******/

            UNION ALL

            select
                max(received_detail.seiban) as seiban

                ,max(received_header.received_header_id) as received_header_id
                ,max(received_number) as received_number
                ,max(worker_name) as worker_name
                ,max(section_master.section_name) as section_name
                ,max(estimate_number) as estimate_number

                ,max(item_master.item_code) as item_code
                ,max(item_master.item_name) as item_name
                ,max(item_master.item_code) || '[br]' || max(item_master.item_name) as item_code_name
                ,max(item_master.item_group_id) as item_group_id
                ,max(item_master.item_group_id_2) as item_group_id_2
                ,max(item_master.item_group_id_3) as item_group_id_3
                ,max(item_master.measure) as measure
                ,max(llc) as llc

                ,max(customer_no) as process_code
                ,max(customer_master.customer_name) as process_name
                ,null as machining_sequence

                ,max(received_date) as order_date
                ,max(dead_line) as dead_line

                ,'" . _g('受注') . "' as class

                ,case
                    /* 納品済 */
                    when sum(case when delivery_completed then 0 else 1 end)=0 then '" . _g('完了') . "'
                    /* 遅れ */
                    when sum(case when delivery_completed then 0 else 1 end)>0 and max(dead_line) < '" . date('Y-m-d') . "' then '" . _g('遅れ') . "'
                    /* 着手。日付にかかわらず、納品が1件でも登録されていれば、着手されたとみなす */
                    when max(case when delivery_header.delivery_date is null then 0 else 1 end) = 1 then '" . _g('着手') . "'
                    /* 未納品 */
                    else '" . _g('未') . "'
                end as status

                /* 受注数合計。表示期間だけではなく全期間の合計 */
                ,max(received_total) as received_total

                /* 納品数合計。表示期間だけではなく全期間の合計 */
                ,round(coalesce(sum(delivery_quantity),0)) as acc_total
                ";

                for ($day = $from; $day <= $to; $day += 86400) {     // 86400sec = 1day
                    if (@$form['gen_search_show_mode'] == '0') {
                        // 納品数表示
                        $query .= ",round(SUM(case when delivery_header.delivery_date = '" . date('Y-m-d', $day) . "' then delivery_quantity else 0 end)) as day" . date('Ymd', $day);
                    } else {
                        // 受注数表示
                        $query .= ",round(max(case when dead_line = '" . date('Y-m-d', $day) . "' then received_total else 0 end)) as day" . date('Ymd', $day);
                    }
                }
                $query .= "
            from
                received_header
                inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                /* 分納がある場合に deliveryで膨らむため、受注合計は個別に出しておく */
                left join (select received_detail_id, round(sum(received_quantity)) as received_total from received_detail group by received_detail_id) as t_rec_total
                    on received_detail.received_detail_id = t_rec_total.received_detail_id
                left join item_master on received_detail.item_id = item_master.item_id
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join delivery_detail on received_detail.received_detail_id = delivery_detail.received_detail_id
                left join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join section_master on received_header.section_id = section_master.section_id
                left join estimate_header on received_header.estimate_header_id = estimate_header.estimate_header_id

            where
                /* 納期もしくは実際の納品日のいずれかが期間内に含まれている受注 */
                (received_date {$betweenDateFromTo} or dead_line {$betweenDateFromTo} or delivery_header.delivery_date {$betweenDateFromTo})
                " . (@$form['gen_search_seiban'] != "" ? "  and received_detail.seiban = '{$form['gen_search_seiban']}'" : "") . "
                " . (@$form['gen_search_show_finish_received'] != "true" ? "  and (not delivery_completed or delivery_completed is null) " : "") . "

            group by
                received_detail.received_detail_id
            ";

        $gen_db->query($query);
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "select * from temp_progress [Where] [Orderby]";

        $this->orderbyDefault = "received_number, llc desc, item_code, machining_sequence";
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("受注別進捗状況");
        $form['gen_menuAction'] = "Menu_Progress";
        $form['gen_listAction'] = "Progress_SeibanProgress_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = ''; // 不要
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("受注別進捗");

        $form['gen_dataRowHeight'] = '38';
        $form['gen_alterColorDisable'] = "true";

        $form['gen_message_noEscape'] = _g("受注・納品状況はMRP品目・製番品目とも表示されますが、手配状況（内製・注文・完了）が表示されるのは製番品目のみです。");

        $form['gen_javascript_noEscape'] = "
             function goOrder(id) {
                gen.modal.open('index.php?action=Manufacturing_Received_Edit&received_header_id=' + id);
             }
        ";

        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("完了")),
            "53d4c7" => array(_g("グリーン"), _g("着手")),
            "fae0a6" => array(_g("イエロー"), _g("遅れ")),
            "aee7fa" => array(_g("ブルー"), _g("未")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '80',
                'align' => 'center',
                'sameCellJoin' => true,
                'colorCondition' => array("#ffffff" => "true"),
                'link' => "javascript:goOrder('[received_header_id]')",
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => true,
                'colorCondition' => array("#ffffff" => "true"),
            ),
            array(
                'label' => _g('受注担当者名'),
                'field' => 'worker_name',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'received_number',
                'hide' => true,
            ),
            array(
                'label' => _g('受注部門名'),
                'field' => 'section_name',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'received_number',
                'hide' => true,
            ),
            array(
                'label' => _g('見積書番号'),
                'field' => 'estimate_number',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'received_number',
                'hide' => true,
            ),
            array(
                'label_noEscape' => _g('品目コード') . '<br>' . _g('品目名'),
                'field' => 'item_code_name',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'seiban',
                'colorCondition' => array("#ffffff" => "true"),
            ),
            array(
                'label' => _g('発注先/工程コード'),
                'field' => 'process_code',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'seiban',
                'colorCondition' => array(
                    "#d5ebff" => "'[status_show]'=='" . _g("受注") . "' or '[status_show]'=='" . _g("納品") . "'",
                    "#ffffff" => "true"),
                'hide' => true,
            ),
            array(
                'label' => _g('発注先/工程名'),
                'field' => 'process_name',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'seiban',
                'colorCondition' => array(
                    "#d5ebff" => "'[status_show]'=='" . _g("受注") . "' or '[status_show]'=='" . _g("納品") . "'",
                    "#ffffff" => "true"),
            ),
            array(
                'label' => _g('手配区分'),
                'field' => 'class',
                'width' => '40',
                'align' => 'center',
                'colorCondition' => array(
                    "#ffffcc" => "'[class]'=='" . _g('受注') . "'",
                ),
            ),
            array(
                'label' => _g('納期'),
                'field' => 'dead_line',
                'type' => 'date',
                'width' => '72',
                'align' => 'center',
                //'sameCellJoin'=>true,
                //'parentColumn'=>'process_name',
                'isOrderby' => false,
                'colorCondition' => array(
                    "#d5ebff" => "'[status_show]'=='" . _g("受注") . "' or '[status_show]'=='" . _g("納品") . "'",
                    "#ffffff" => "true"),
                'helpText_noEscape' => _g('納期が異なる複数のオーダーが存在する場合は、最終納期を表示しています。')
            ),
            array(
                'label' => _g('状況'),
                'field' => 'status',
                'width' => '40',
                'align' => 'center',
                'colorCondition' => array(
                    "#d7d7d7" => "'[status]'=='" . _g('完了') . "'",
                    "#fae0a6" => "'[status]'=='" . _g('遅れ') . "'",
                    "#53d4c7" => "'[status]'=='" . _g('着手') . "'",
                    "#aee7fa" => "'[status]'=='" . _g('未') . "'",
                ),
                'helpText_noEscape' =>
                _g("「未」：未完了で、まだ納期が来ていないオーダーです。")
                . "<br><br>" . _g("「着手」：一部の製造実績・受入・納品が登録されているものの、まだ未完了のオーダーです。")
                . "<br><br>" . _g("「遅れ」：未完了で、なおかつ納期が過ぎているオーダーです。")
                . "<br><br>" . _g("「完了」：製造または受入が完了したオーダーです。")
            ),
            array(
                'label' => _g('受注/ｵｰﾀﾞｰ数'),
                'field' => 'order_total',
                'width' => '60',
                'type' => 'numeric',
                'helpText_noEscape' => _g('受注行は受注数の合計、内製/注文/外製行はオーダー数の合計が表示されます。')
            ),
            array(
                'label' => _g('納品/実績/受入'),
                'field' => 'acc_total',
                'width' => '60',
                'type' => 'numeric',
                'helpText_noEscape' => _g('受注行は納品数の合計、内製行は製造実績数の合計、注文/外製行は受入数の合計が表示されます。表示期間外の分も含まれています。')
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
        );


        $from = strtotime($form['gen_search_date_from']);
        $to = strtotime($form['gen_search_date_to']);

        for ($date = $from; $date <= $to; $date += 86400) {// 86400sec = 1day
            $dateStr = date('Y-m-d', $date);
            $form['gen_columnArray'][] = array(
                'label' => date('m-d', $date) . "(" . Gen_String::weekdayStr($dateStr) . ")",
                'field' => 'day' . date('Ymd', $date),
                'width' => '65',
                'type' => 'numeric',
                'zeroToBlank' => true,
                'denyMove' => true, // 日付列は列順序固定。日付範囲を変更したときの表示乱れを防ぐため
                'colorCondition' => array(
                    "#d7d7d7" => "'[order_date]'<='{$dateStr}' and '[dead_line]'>='{$dateStr}' and '[status]'=='" . _g('完了') . "'",
                    "#53d4c7" => "'[order_date]'<='{$dateStr}' and '[dead_line]'>='{$dateStr}' and '[status]'=='" . _g('着手') . "'",
                    "#fae0a6" => "'[order_date]'<='{$dateStr}' and '[dead_line]'>='{$dateStr}' and '[status]'=='" . _g('遅れ') . "'",
                    "#aee7fa" => "'[order_date]'<='{$dateStr}' and '[dead_line]'>='{$dateStr}'",    // 未
                    "#ffeaef" => in_array($dateStr, $this->holidayArr) ? "true" : "false",          // 休日
                ),
            );
        }
    }

}