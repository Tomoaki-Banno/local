<?php

class Progress_ProcessLoad_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            // [Where]を使わず、SQLで独自処理していることに注意
            array(
                'label' => _g('工程コード/名'),
                'field' => 'process_code',
                'field2' => 'process_name',
            ),
            array(
                'label' => _g("表示期間(最大100日)"),
                'type' => 'dateFromTo',
                'field' => 'order_detail_dead_line',
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'nosql' => true,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->notDateStrToValue('gen_search_order_detail_dead_line_from', '');
        $converter->notDateStrToValue('gen_search_order_detail_dead_line_to', '');
        if (@$form['gen_search_order_detail_dead_line_from'] == '') {
            if (@$form['gen_search_order_detail_dead_line_to'] == '') {
                // 両方未設定ならデフォルト値に
                unset($form['gen_search_order_detail_dead_line_from']);
                unset($form['gen_search_order_detail_dead_line_to']);
            } else {
                $form['gen_search_order_detail_dead_line_from'] = date('Y-m-d', strtotime($form['gen_search_order_detail_dead_line_to'] . ' -100 days'));
            }
        } else if (@$form['gen_search_order_detail_dead_line_to'] == '') {
            $form['gen_search_order_detail_dead_line_to'] = date('Y-m-d', strtotime($form['gen_search_order_detail_dead_line_from'] . ' +100 days'));
        }
        $converter->dateSort('gen_search_order_detail_dead_line_from', 'gen_search_order_detail_dead_line_to');
        $converter->dateSpan('gen_search_order_detail_dead_line_from', 'gen_search_order_detail_dead_line_to', 100);    // PostgreSQLのselectリストは最大1664項目
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $from = strtotime($form['gen_search_order_detail_dead_line_from']);
        $to = strtotime($form['gen_search_order_detail_dead_line_to']);

        $dateSelectStr = "";
        for ($day = $from; $day <= $to; $day += 86400) {        // 86400sec = 1day
            $dateSelectStr .=
                    ",round(SUM(case when process_dead_line = '" . date('Y-m-d', $day) . "' then percent1 else 0 end)) as day" . date('Ymd', $day);
        }

        $this->selectQuery = "
            select
                process_master.process_id
                ,max(process_code) as process_code
                ,max(process_name) as process_name
                {$dateSelectStr}
            from
                process_master
                left join (
                    select
                        process_id
                        ,process_dead_line
                        ,case when pcs_per_day > 0 then (order_detail_quantity / pcs_per_day) * 100 else 0 end as percent1
                    from
                        order_header
                        inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                        inner join order_process on order_detail.order_detail_id = order_process.order_detail_id
                        left join item_master on order_detail.item_id = item_master.item_id
                    where
                        classification = 0    /* 製造指示書のみ。外製用（「兼注文書」）は含めない */
                        and order_detail_dead_line between '" . date('Y-m-d', $from) . "' and '" . date('Y-m-d', $to) . "'
                    ) as t0 on process_master.process_id = t0.process_id
            [Where]
            group by
                process_master.process_id
            [Orderby]
        ";

        $this->orderbyDefault = 'process_master.process_id';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("工程別負荷状況");
        $form['gen_menuAction'] = "Menu_Progress";
        $form['gen_listAction'] = "Progress_ProcessLoad_List";
        $form['gen_idField'] = 'process_id';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("負荷");

        $form['gen_message_noEscape'] = _g("単位は％です。「製造能力」(品目マスタ)が0になっている品目の製造数は含まれません。");

        $form['gen_colorSample'] = array(
            "ffcc99" => array(_g("ベージュ"), _g("負荷が100%以上")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('工程コード'),
                'field' => 'process_code',
                'width' => '120',
            ),
            array(
                'label' => _g('工程名'),
                'field' => 'process_name',
                'width' => '120',
            ),
        );

        $from = strtotime($form['gen_search_order_detail_dead_line_from']);
        $to = strtotime($form['gen_search_order_detail_dead_line_to']);

        for ($date = $from; $date <= $to; $date += 86400) {        // 86400sec = 1day
            $fieldName = 'day' . date('Ymd', $date);
            $form['gen_columnArray'][] = array(
                'label' => date('m-d', $date) . "(" . Gen_String::weekdayStr(date('Y-m-d', $date)) . ")",
                'field' => $fieldName,
                'width' => '70',
                'type' => 'numeric',
                'zeroToBlank' => true,
                'denyMove' => true, // 日付列は列順序固定。日付範囲を変更したときの表示乱れを防ぐため
                'colorCondition' => array("#ffcc99" => "[{$fieldName}] >= 100"),
            );
        }
    }

}