<?php

class Master_Bom_AjaxTreeview extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId'])) {
            throw new Exception('品目ID必須');
        }

        if (isset($form['reverse'])) {
            $reverse = ($form['reverse'] == 'true');
        } else {
            $reverse = false;
        }

        if (!$reverse) {
            // 正方向展開（指定された品目の子品目を取得）
            $query = "
            select
                seq
                ,bom_master.child_item_id as c1
                ,item_code as c2
                ,item_name as c3
                ,bom_master.quantity as c4
                ,measure as c5
                ,order_class as c6
                ,dummy_item as c7
                ,process_name_show as c8
                ,end_item as c9
                ,case when t_child_bom.item_id is null then false else true end as c10
            from
                bom_master
                left join item_master on bom_master.child_item_id = item_master.item_id
                left join (select item_id from bom_master group by item_id) as t_child_bom on bom_master.child_item_id = t_child_bom.item_id
                left join (
                    select 
                        t_process_1.item_id
                        ,string_agg(process_name,', ') as process_name_show 
                    from 
                        (select item_id, process_id from item_process_master order by machining_sequence) as t_process_1
                        inner join process_master on t_process_1.process_id = process_master.process_id
                            /* 標準工程は表示しない*/ and process_master.process_id <> 0
                    group by
                        t_process_1.item_id
                ) as t_process on item_master.item_id = t_process.item_id
            where
                bom_master.item_id = '{$form['itemId']}'
            ";
        } else {
            // 逆方向展開（指定された品目の親品目を取得）
            $query = "
            select
                seq
                ,bom_master.item_id as c1
                ,item_code as c2
                ,item_name as c3
                ,quantity as c4
                ,measure as c5
                ,order_class as c6
                ,dummy_item as c7
                ,process_name_show as c8
                ,end_item as c9
                ,case when t_child_bom.child_item_id is null then false else true end as c10
            from
                bom_master
                left join item_master on bom_master.item_id = item_master.item_id
                left join (select child_item_id from bom_master group by child_item_id) as t_child_bom on bom_master.item_id = t_child_bom.child_item_id
                left join (
                    select 
                        t_process_1.item_id
                        ,string_agg(process_name, ', ') as process_name_show 
                    from 
                        (select item_id, process_id from item_process_master order by machining_sequence) as t_process_1
                        inner join process_master on t_process_1.process_id = process_master.process_id
                            /* 標準工程は表示しない*/ and process_master.process_id <> 0
                    group by
                        t_process_1.item_id
                ) as t_process on item_master.item_id = t_process.item_id
            where
                bom_master.child_item_id = '{$form['itemId']}'
            ";
        }
        $showQty = true;
        $query_parent = "select dummy_item, end_item from item_master where item_id = '{$form['itemId']}'";
        $obj = $gen_db->queryOneRowObject($query_parent);
        $parent_dummy_item = $obj->dummy_item;
        $parent_end_item = $obj->end_item;

        $query .= " order by seq, c2";

        // データ取得
        $res = $gen_db->getArray($query);

        // 結果文字列の準備
        $arr = array();
        if (is_array($res)) {
            foreach ($res as $row) {
                // 取数モードを実装したが、使用中止になった（tplでセレクタをコメントアウト）。理由はtplのセレクタの箇所を参照
                $inzu = (@$form['inzu_mode'] == "tori" ? round(1 / $row['c4'], 4) : round($row['c4'], 4) . $row['c5']);
                
                $proc = $row['c8'];
                if (substr($proc, -2) == ", ") {
                    $proc = substr($proc, 0, strlen($proc)-2);
                }
                if ($proc != "") {
                    $proc = "[{$proc}]";
                }

                $arr[] = array(
                    $row['c1']
                    ,$row['c2'] .":" . $row['c3'] . ($showQty ? "($inzu)" : "") . ($row['c6'] == "0" ? " " . _g("【製番】") : " " . _g("【MRP】") . $proc)
                    ,$row['c7']
                    ,$row['c9']
                    ,$row['c10']
                );
            }
        }
        
        return
            array(
                "parent_dummy_item" => $parent_dummy_item,
                "parent_end_item" => $parent_end_item,
                "data" => $arr,
            );
    }

}
