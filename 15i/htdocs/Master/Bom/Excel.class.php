<?php

class Master_Bom_Excel
{

    function execute(&$form)
    {
        global $gen_db;

        $offset = 0;
        if (isset($form['gen_csvOffset']) && Gen_String::isNumeric($form['gen_csvOffset'])) {
            $offset = $form['gen_csvOffset'] - 1;
        }
        $offsetQuery = " offset {$offset} limit " . GEN_EXCEL_EXPORT_MAX_COUNT;

        if (isset($form['tree'])) {
            // ●ツリー形式で出力

            $query = "select max(llc) from item_master";
            $maxLLC = $gen_db->queryOneValue($query);
            
            // データ
            $query = "select 1";
            for($i = 0; $i <= $maxLLC; $i++) {
                $query .= "
                    ,item{$i}.item_code as item_code_{$i}
                    ,item{$i}.item_name as item_name_{$i}
                    " .($i == 0 ? "" : ",bom{$i}.quantity as quantity_{$i}")."
                    ,case when item{$i}.order_class is null then '' else case item{$i}.order_class when 0 then '" . _g("製番") . "' when 2 then '" . _g("ロット") . "' else '" . _g("MRP") . "' end end as order_class_{$i}
                    ,case when item{$i}.dummy_item then '" . _g("ダミー") . "' else '' end as show_dummy_{$i}
                    ,case when item{$i}.end_item then '" . _g("非表示") . "' else '' end as show_end_{$i}
                        /* 工程名。最後のカンマを削除する */
                    ,case when process_name_show_{$i} like '%, ' then substr(process_name_show_{$i}, 0, length(process_name_show_{$i}) - 1) else process_name_show_{$i} end
                ";
            }
            for($i = 0; $i <= $maxLLC; $i++) {
                if ($i == 0) {
                    $query .= "
                        from item_master as item0
                    ";
                } else {
                    $query .= "
                        left join bom_master as bom{$i} on item" . ($i-1) . ".item_id = bom{$i}.item_id
                        left join item_master as item{$i} on bom{$i}.child_item_id = item{$i}.item_id
                    ";
                }
                $query .= "
                    left join (
                        select 
                            t_process_sub.item_id
                            ,string_agg(process_name, ', ') as process_name_show_{$i} 
                        from 
                            (select item_id, process_id from item_process_master order by machining_sequence) as t_process_sub
                            inner join process_master on t_process_sub.process_id = process_master.process_id
                                /* 標準工程は表示しない*/ and process_master.process_id <> 0
                        group by
                            t_process_sub.item_id
                    ) as t_process{$i} on item{$i}.item_id = t_process{$i}.item_id
                ";
            }
            $query .= " 
                where 
                " . (isset($form['itemId']) && is_numeric($form['itemId']) ? " item0.item_id = '{$form['itemId']}'" : "item0.llc = 0") . "
                order by item0.item_code
            ";
            for($i = 1; $i <= $maxLLC; $i++) {
                $query .= ",bom{$i}.seq";
            }
            $query .= " {$offsetQuery}";

            $colArray = array();
            for($i = 0; $i <= $maxLLC; $i++) {
                $colStart = count($colArray);
                $colArray[] = array(
                    'label' => _g('品目コード'),
                    'width' => '100',
                    'type' => 'data',
                    'field' => "item_code_{$i}",
                    'colorCondition' => array('#FFFFCC' => true),
                    'sameCellJoin' => 'true',
                    'parentColumn' . ($i == 0 ? "dummy" : "") => "item_code_" . ($i-1),
                );
                $colArray[] = array(
                    'label' => _g('品目名'),
                    'width' => '150',
                    'type' => 'data',
                    'field' => "item_name_{$i}",
                    'sameCellJoin' => 'true',
                    'parentColumn' => "item_code_{$i}",
                );
                if ($i > 0) {
                    $colArray[] = array(
                        'label' => _g('員数'),
                        'width' => '30',
                        'type' => 'numeric',
                        'field' => "quantity_{$i}",
                        'sameCellJoin' => 'true',
                        'parentColumn' => "item_code_{$i}",
                    );
                }
                $colArray[] = array(
                    'label' => _g('管理区分'),
                    'width' => '30',
                    'type' => 'data',
                    'field' => "order_class_{$i}",
                    'sameCellJoin' => 'true',
                    'parentColumn' => "item_code_{$i}",
                );
                $colArray[] = array(
                    'label' => _g('ダミー品目'),
                    'width' => '30',
                    'type' => 'data',
                    'field' => "show_dummy_{$i}",
                    'sameCellJoin' => 'true',
                    'parentColumn' => "item_code_{$i}",
                );
                $colArray[] = array(
                    'label' => _g('非表示品目'),
                    'width' => '30',
                    'type' => 'data',
                    'field' => "show_end_{$i}",
                    'sameCellJoin' => 'true',
                    'parentColumn' => "item_code_{$i}",
                );
                $colArray[] = array(
                    'label' => _g('工程'),
                    'width' => '30',
                    'type' => 'data',
                    'field' => "process_name_show_{$i}",
                    'sameCellJoin' => 'true',
                    'parentColumn' => "item_code_{$i}",
                );
                // 階層は0はじまりとする ag.cgi?page=ProjectDocView&pid=1574&did=224022
                $showArray[] = array($colStart, 2, _g("階層") . ($i));
            }
            
            // Excel出力
            Gen_Excel::sqlToExcel($query, _g("●構成表マスタ（ツリー）"), $colArray, $showArray, 3, null, "", "", "", "", true);

        } else {
            if (isset($form['itemId']) && is_numeric($form['itemId'])) {
                // ●ベタ形式で出力（親品目以下の品目のみ）

                $gen_db->begin();
                Logic_Bom::expandBom($form['itemId'], 1, (@$form['reverse'] == "true"), true, false);

                // sql文の準備
                $sql = "
                select
                    lpad(cast(lc as text)
                    ,lc+1
                    ,cast('*' as text)) as level
                    ,item_code
                    ,item_name
                    ,quantity
                    ,case when dummy_item then '" . _g("ダミー品目") . "' else '' end as show_dummy_item
                    ,case when end_item then '" . _g("非表示品目") . "' else '' end as show_end_item
                from
                    temp_bom_expand
                    inner join item_master on temp_bom_expand.item_id = item_master.item_id
                order by
                    item_code_key
                {$offsetQuery}
                ";

                // 列見出しの準備
                if (@$form['reverse'] == "true") {
                    $lcTitle = _g("階層(数が大きいほうが上位)");
                } else {
                    $lcTitle = _g("階層");
                }

                $colArray = array(
                    array(
                        'label' => _g('階層'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'level',
                    ),
                    array(
                        'label' => _g('品目コード'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'item_code',
                    ),
                    array(
                        'label' => _g('品目名'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'item_name',
                    ),
                    array(
                        'label' => _g('員数'),
                        'width' => '100',
                        'type' => 'numeric',
                        'type' => 'data',
                        'field' => 'quantity',
                    ),
                    array(
                        'label' => _g('ダミー品目'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'show_dummy_item',
                    ),
                    array(
                        'label' => _g('非表示品目'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'show_end_item',
                    ),
                );

                // Excel出力
                Gen_Excel::sqlToExcel($sql, _g("●構成表マスタ") . (@$form['reverse'] == "true" ? _g("（逆展開）") : ""), $colArray, null, 2, null);

                // 後処理
                $gen_db->commit();

            } else {
                // ●ベタ形式で出力（全品目）

                // sql文の準備
                $sql = "
                select
                    item_master.item_code
                    ,item_master.item_name
                    ,child_item_master.item_code as child_item_code
                    ,child_item_master.item_name as child_item_name
                    ,quantity
                    ,case when child_item_master.dummy_item then '" . _g("ダミー品目") . "' else '' end as show_dummy_item
                    ,case when child_item_master.end_item then '" . _g("非表示品目") . "' else '' end as show_end_item
                from
                    bom_master
                    inner join item_master on bom_master.item_id = item_master.item_id
                    inner join item_master as child_item_master on bom_master.child_item_id = child_item_master.item_id
                order by
                    item_master.item_code
                    , child_item_master.item_code
                {$offsetQuery}
                ";

                // 列見出しの準備
                $colArray = array(
                    array(
                        'label' => _g('親品目コード'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'item_code',
                        'sameCellJoin' => true,
                    ),
                    array(
                        'label' => _g('親品目名'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'item_name',
                        'sameCellJoin' => true,
                    ),
                    array(
                        'label' => _g('子品目コード'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'child_item_code',
                    ),
                    array(
                        'label' => _g('子品目名'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'child_item_name',
                    ),
                    array(
                        'label' => _g('員数'),
                        'width' => '100',
                        'type' => 'numeric',
                        'field' => 'quantity',
                    ),
                    array(
                        'label' => _g('ダミー品目'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'show_dummy_item',
                    ),
                    array(
                        'label' => _g('非表示品目'),
                        'width' => '100',
                        'type' => 'data',
                        'field' => 'show_end_item',
                    ),
                );

                // Excel出力
                Gen_Excel::sqlToExcel($sql, _g("●構成表マスタ"), $colArray, null, 2, null);
            }
        }
        
        // リスト画面を表示
        $form['gen_restore_search_condition'] = 'true';
        return 'action:Master_Bom_List';
    }

}