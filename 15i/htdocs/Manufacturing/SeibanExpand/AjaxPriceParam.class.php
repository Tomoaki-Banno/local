<?php

class Manufacturing_SeibanExpand_AjaxPriceParam extends Base_AjaxBase
{

    // id(received_detail_id _ item_id)とcustomer_idを受け取り、購入単価及び適用数を返す

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['orderUserId']) || !is_numeric(@$form['orderUserId'])) {
            return;
        }
        
        // 手配先取引通貨取得
        $query = "
        select
            coalesce(t02.currency_name, t03.key_currency) as currency_name
        from
            customer_master as t01
            left join currency_master as t02 on t01.currency_id = t02.currency_id
            left join (select max(key_currency) as key_currency from company_master) as t03 on 1=1
        where
            t01.customer_id = '{$form['orderUserId']}'
        ";
        $currency_name = $gen_db->queryOneValue($query);

        $responseArr = array(
            'currency_status' => "success",
            'currency_name' => $currency_name,
        );

        if (!isset($form['id']) || strlen($form['id']) == 0) {
            return $responseArr;
        }

        // 品目id
        $idArr = preg_split('[_]', $form['id']);
        $itemId = $idArr[1];
        if (!is_numeric($itemId)) {
            return $responseArr;
        }

        $query = "
        select
            t01.item_id,
            t02.default_order_price,
            t02.default_order_price_2,
            t02.default_order_price_3,
            t02.order_price_limit_qty_1,
            t02.order_price_limit_qty_2
        from
            item_master as t01
            left join item_order_master as t02 on t01.item_id = t02.item_id
        where
            t01.item_id = '{$itemId}'
            and t02.order_user_id = '{$form['orderUserId']}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        if (!$res || $res == null) {
            return $responseArr;
        }

        $responseArr['status'] = "success";
        $responseArr['default_order_price'] = $res->default_order_price;
        $responseArr['default_order_price_2'] = $res->default_order_price_2;
        $responseArr['default_order_price_3'] = $res->default_order_price_3;
        $responseArr['order_price_limit_qty_1'] = $res->order_price_limit_qty_1;
        $responseArr['order_price_limit_qty_2'] = $res->order_price_limit_qty_2;
        
        return $responseArr;
    }

}