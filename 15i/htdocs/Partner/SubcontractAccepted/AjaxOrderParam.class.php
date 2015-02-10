<?php

class Partner_SubcontractAccepted_AjaxOrderParam extends Base_AjaxBase
{

    // order_detail_id を受け取り、各種情報を返す

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['order_detail_id']) || !is_numeric(@$form['order_detail_id']))
            return;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "
        select
            customer_no
            ,customer_name
            ,item_code
            ,item_name
            ,order_date
            ,order_detail_quantity
            ,order_detail_dead_line
            ,case when foreign_currency_id is null then item_price else foreign_currency_item_price end as item_price
            ,order_header.classification
            ,default_location_id
            ,seiban
            ,COALESCE(order_detail_quantity,0) - COALESCE(accepted_quantity,0) as remained_quantity
            ,order_detail.order_measure
            ,order_header.remarks_header
            ,order_detail.remarks
            ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name
            -- 外製用
            ,subcontract_parent_order_no
            ,subcontract_process_name
            ,subcontract_process_remarks_1
            ,subcontract_process_remarks_2
            ,subcontract_process_remarks_3
            ,subcontract_ship_to

        from
            order_detail
            inner join order_header on order_detail.order_header_id = order_header.order_header_id
            left join (select item_id, default_location_id from item_master) as t1 on order_detail.item_id = t1.item_id
            left join customer_master on order_header.partner_id = customer_master.customer_id
            left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id
        where
            order_header.classification = 2
            and order_header.classification <> 0
            and order_detail_id = '{$form['order_detail_id']}'
        ";
        $data = $gen_db->queryOneRowObject($query);

        if (!$data || $data == null)
            return;


        // 在庫製番の決定
        $stockSeiban = Logic_Seiban::getStockSeiban($data->seiban);

        return
            array(
                'customer_no' => $data->customer_no,
                'customer_name' => $data->customer_name,
                'item_code' => $data->item_code,
                'item_name' => $data->item_name,
                'order_date' => $data->order_date,
                'order_detail_quantity' => $data->order_detail_quantity,
                'order_detail_dead_line' => $data->order_detail_dead_line,
                'price' => $data->item_price,
                'seiban' => $data->seiban,
                'stock_seiban' => $stockSeiban,
                'currency_name' => $data->currency_name,
                'remained_quantity' => $data->remained_quantity,
                'order_measure' => $data->order_measure,
                'classification' => $data->classification,
                'default_location_id' => $data->default_location_id,
                'remarks_header' => $data->remarks_header,
                'subcontract_parent_order_no' => $data->subcontract_parent_order_no,
                'subcontract_process_name' => $data->subcontract_process_name,
                'subcontract_process_remarks_1' => $data->subcontract_process_remarks_1,
                'subcontract_process_remarks_2' => $data->subcontract_process_remarks_2,
                'subcontract_process_remarks_3' => $data->subcontract_process_remarks_3,
                'subcontract_ship_to' => $data->subcontract_ship_to,
                'remarks' => $data->remarks,
            );
    }

}
