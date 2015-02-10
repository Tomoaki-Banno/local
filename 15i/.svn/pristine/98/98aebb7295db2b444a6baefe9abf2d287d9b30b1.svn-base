<?php

class Manufacturing_Achievement_AjaxChildItem extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['order_detail_id']) || !is_numeric(@$form['order_detail_id']))
            return;
        
        $isEdit = (isset($form['achievement_id']) && Gen_String::isNumeric($form['achievement_id']));

        $query = "
        select
            order_child_item.child_item_id
            ,item_master.item_code
            ,item_master.item_name
            ,order_child_item.quantity
            " . ($isEdit ? ",t_use.use_qty" : "") . "
        from
            order_detail
            inner join order_child_item on order_detail.order_detail_id = order_child_item.order_detail_id
            inner join item_master on order_child_item.child_item_id = item_master.item_id
            left join bom_master on order_detail.item_id = bom_master.item_id and order_child_item.child_item_id = bom_master.child_item_id /* 表示順決定用 */
            " . ($isEdit ? 
                "left join (select item_id, sum(item_in_out_quantity) as use_qty from item_in_out 
                    where item_in_out.achievement_id = '{$form['achievement_id']}' 
                        and item_in_out.classification = 'use'
                    group by item_in_out.item_id
                    ) as t_use
                    on order_child_item.child_item_id = t_use.item_id" 
            : "") . "
        where
            order_detail.order_detail_id = '{$form['order_detail_id']}'
        order by
            bom_master.seq, child_item_id
        ";
        $arr = $gen_db->getArray($query);

        $resArr = array();
        if (is_array($arr)) {
            foreach ($arr as $row) {
                $resArr[] = array(
                    $row['child_item_id'],
                    trim($row['item_code']),
                    trim($row['item_name']),
                    $row['quantity'],
                    ($isEdit ? $row['use_qty'] : ""),
                );
            }
        }

        return
            array(
                'children' => $resArr,
            );
    }

}