<?php

class Manufacturing_WorkerManage_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = 'select equip_id, equip_name from equip_master order by equip_code';
        $equipOptions = $gen_db->getHtmlOptionArray($query, true);

        $query = 'select process_id, process_name from process_master order by process_code';
        $processOptions = $gen_db->getHtmlOptionArray($query, true);

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('作業者'),
                'field' => 'worker_id',
                'type' => 'dropdown',
                'size' => '150',
                'dropdownCategory' => 'worker',
            ),
            array(
                'label' => _g('品目'),
                'field' => 'item_id',
                'type' => 'dropdown',
                'size' => '150',
                'dropdownCategory' => 'item',
                'nosql' => 'true',
                'hide' => true,
            ),
            array(
                'label' => _g('作業日'),
                'type' => 'dateFromTo',
                'field' => 'achievement_date',
                'defaultFrom' => date('Y-m-d'),
            ),
            array(
                'label' => _g('設備'),
                'type' => 'select',
                'field' => 'equip_id',
                'nosql' => 'true',
                'options' => $equipOptions,
            ),
            array(
                'label' => _g('工程'),
                'type' => 'select',
                'field' => 'process_id',
                'nosql' => 'true',
                'options' => $processOptions,
                'hide' => true,
            ),
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
                t0.worker_id
                ,t1.worker_code
                ,t1.worker_name
                ,t0.achievement_date
                ,t0.begin_time
                ,to_char(t0.begin_time, 'HH24:MI') as begin_time_format
                ,to_char(t0.end_time, 'HH24:MI') as end_time_format
                ,cast(floor(work_minute / 60) as text) || ':' || lpad(cast(mod(work_minute, 60) as text),2,'0') as work_time_format
                ,cast(floor(break_minute / 60) as text) || ':' || lpad(cast(mod(break_minute, 60) as text),2,'0') as break_time_format
                ,t2.item_id
                ,t2.item_code
                ,t2.item_name
                ,t3.process_id
                ,t3.process_code
                ,t3.process_name
                ,t4.equip_id
                ,t4.equip_code
                ,t4.equip_name
                ,t0.achievement_quantity
                ,t2.measure
                ,t5.waster_qty
                ,t0.remarks
                ,case when work_minute > 0 then round((achievement_quantity / work_minute * 60), 2) else 0 end as mph
                /* for custom column */
                ,t0.achievement_id
            from
                achievement as t0
                left join (select worker_id as wid, worker_code, worker_name from worker_master) as t1 on t0.worker_id = t1.wid
                left join item_master as t2 on t0.item_id = t2.item_id
                left join process_master as t3 on t0.process_id = t3.process_id
                left join equip_master as t4 on t0.equip_id = t4.equip_id
                left join (
                    select achievement_id as aid, sum(waster_quantity) as waster_qty from waster_detail group by achievement_id
                    ) as t5 on t0.achievement_id = t5.aid
            [Where]
                " . (@$form['gen_search_item_id'] != "" ? " and t0.item_id = {$form['gen_search_item_id']}" : "") . "
                " . (is_numeric(@$form['gen_search_process_id']) ? " and t0.process_id = {$form['gen_search_process_id']}" : "") . "
                " . (is_numeric(@$form['gen_search_equip_id']) ? " and t0.equip_id = {$form['gen_search_equip_id']}" : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'achievement_date desc, begin_time';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "t2", "", "achievement_id"),
            array("process_master", "t3", "", "achievement_id"),
            array("equip_master", "t4", "", "achievement_id"),
        );        
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("従業員設備管理");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Manufacturing_WorkerManage_List";
        $form['gen_idField'] = 'worker_id';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("従業員");

        $form['gen_titleRowHeight'] = 44;

        $form['gen_columnArray'] = array(
            array(
                'label' => _g("従業員名"),
                'field' => 'worker_name',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g("設備名"),
                'field' => 'equip_name',
                'width' => '100',
            ),
            array(
                'label' => _g("作業日"),
                'field' => 'achievement_date',
                'type' => 'date',
                'sameSellJoin' => true
            ),
            array(
                'label' => _g("開始時刻"),
                'field' => 'begin_time_format',
                'width' => '70',
                'align' => 'center',
            ),
            array(
                'label' => _g("終了時刻"),
                'field' => 'end_time_format',
                'width' => '70',
                'align' => 'center',
            ),
            array(
                'label' => _g("品目名"),
                'field' => 'item_name',
            ),
            array(
                'label' => _g("工程"),
                'field' => 'process_name',
            ),
            array(
                'label' => _g("製造数"),
                'field' => 'achievement_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g("単位"),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g("不適合数"),
                'field' => 'waster_qty',
                'type' => 'numeric',
            ),
            array(
                'label' => _g("製造時間"),
                'field' => 'work_time_format',
                'width' => '70',
                'align' => 'center',
            ),
            array(
                'label' => _g("休憩時間"),
                'field' => 'break_time_format',
                'width' => '70',
                'align' => 'center',
            ),
            array(
                'label' => _g("製造数/h"),
                'field' => 'mph',
                'type' => 'numeric',
                'helpText_noEscape' => _g('小数点以下3桁目を四捨五入しています。')
            ),
            array(
                'label' => _g("実績備考"),
                'field' => 'remarks',
            ),
        );
    }

}