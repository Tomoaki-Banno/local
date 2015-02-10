<?php

class Partner_SubcontractAccepted_AjaxOrderParamBarcode extends Base_AjaxBase
{

    // order_detail_id を受け取り、各種情報を返す

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['order_no']) || $form['order_no'] === '')
            return;

        $query = "
        select
            item_code
            ,item_name
            ,order_detail_completed
            ,order_detail_quantity
            ,accepted_quantity
            ,classification
        from
            order_detail
            left join (select order_header_id, classification from order_header) as T2 on order_detail.order_header_id = T2.order_header_id
        where
            classification = 2
            and classification <> 0
            and order_detail.order_no = '{$form['order_no']}'
        ";
        $data = $gen_db->queryOneRowObject($query);

        if (!$data || $data == null)
            return;

        return
            array(
                'item_code' => $data->item_code,
                'item_name' => $data->item_name,
                'quantity' => ($data->order_detail_completed == "t" ? 0 : ($data->order_detail_quantity - $data->accepted_quantity)),
                'classification' => $data->classification,
            );
    }

}