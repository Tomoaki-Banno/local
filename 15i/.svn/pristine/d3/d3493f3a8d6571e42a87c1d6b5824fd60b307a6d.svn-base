<?php

class Delivery_Delivery_AjaxReceivedParam extends Base_AjaxBase
{

    // received_detail_id（必須）とdelivery_detail_id（空文字可）を受け取り、納品フォーム表示用の各種情報を返す

    function _execute(&$form)
    {
        $obj =
            array(
               "status" => "failure" 
            );

        // 数字
        if (!isset($form['received_detail_id']) || !is_numeric($form['received_detail_id']))
            return $obj;

        // 空文字か数字
        if (!isset($form['delivery_detail_id']) || (!is_numeric($form['delivery_detail_id']) && !$form['delivery_detail_id'] == ""))
            return $obj;

        if (!isset($form['location_id']) || !is_numeric($form['location_id']))
            $form['location_id'] = null;

        // データ取得
        $res = Logic_Delivery::getDeliveryData($form['received_detail_id'], $form['delivery_detail_id'], $form['location_id']);

        return
            array(
                'status' => "success",
                'received_number' => $res['received_number'],
                'received_line_no' => $res['line_no'],
                'item_code' => $res['item_code'],
                'item_name' => $res['item_name'],
                'measure' => $res['measure'],
                'rec_qty' => $res['received_quantity'],
                'price' => $res['product_price'],
                'rem_qty' => $res['remained_quantity'],
                'stock_qty' => $res['stock_quantity'],
                'loc3' => $res['default_location_id_3'],
                'customer_id' => $res['customer_id'],
                'customer_no' => $res['customer_no'],
                'delivery_customer_id' => $res['delivery_customer_id'],
                'delivery_customer_no' => $res['delivery_customer_no'],
                'delivery_customer_name' => $res['delivery_customer_name'],
                'sales_base_cost' => ($res['currency_id'] === null ? $res['sales_base_cost'] : $res['foreign_currency_sales_base_cost']),
                'dead_line' => $res['dead_line'],
                'remarks' => $res['remarks'],
                'is_dummy' => $res['is_dummy'],
            );
    }

}