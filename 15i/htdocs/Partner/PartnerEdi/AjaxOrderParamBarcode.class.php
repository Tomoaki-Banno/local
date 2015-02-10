<?php

class Partner_PartnerEdi_AjaxOrderParamBarcode extends Base_AjaxBase
{

    // order_no を受け取り、各種情報を返す
    // 戻り値はカンマ区切り

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
            left join (select order_header_id, classification, partner_id from order_header) as t_header on order_detail.order_header_id = t_header.order_header_id
        where
            t_header.partner_id = '{$_SESSION["user_customer_id"]}'
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