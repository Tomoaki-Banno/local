<?php

class Master_Bom_AjaxItemParam extends Base_AjaxBase
{

    // item_idを受け取り、品目id・品目コード・品目名・管理区分・販売単価 を返す
    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric(@$form['itemId']))
            return;

        $query = "
        select
            item_code
            ,item_name
            ,order_class
            ,default_selling_price
            ,partner_class
            ,case when t_remained.item_id is null then 0 else 1 end as remained_exist
            ,case when dummy_item then 'yes' else '' end as dummy_item
        from
            item_master
            left join item_order_master on item_master.item_id = item_order_master.item_id
                and item_order_master.line_number = 0
            left join (
                select
                    item_id
                from
                    order_detail
                    inner join order_header on order_header.order_header_id = order_detail.order_header_id
                where
                    (not order_detail_completed or order_detail_completed is null)
                    and classification <> '1'
                group by
                    item_id
                ) as t_remained
                on item_master.item_id = t_remained.item_id
        where
            item_master.item_id = '{$form['itemId']}'
        ";
        $data = $gen_db->queryOneRowObject($query);

        // 標準原価
        $baseCost = floatval(Logic_BaseCost::calcStandardBaseCost($form['itemId'], 1));
        
        return
            array(
                'item_id' => $form['itemId'],
                'item_code' => $data->item_code,
                'item_name' => $data->item_name,
                'order_class' => $data->order_class,
                'default_selling_price' => $data->default_selling_price,
                'partner_class' => $data->partner_class,
                'remained_exist' => $data->remained_exist,
                'base_cost' => $baseCost,
                'dummy_item' => $data->dummy_item,
            );
    }

}