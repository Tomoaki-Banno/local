<?php

class Delivery_Delivery_AjaxCurrencyCheck extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // 請求先取引通貨取得
        if (!isset($form['customer_id']) || !is_numeric($form['customer_id']))
            return;

        $query = "
        select
            t_bill_customer.currency_id
        from
            customer_master as t_bill_customer
            inner join customer_master on t_bill_customer.customer_id = coalesce(customer_master.bill_customer_id,customer_master.customer_id)
        where
            customer_master.customer_id = '{$form['customer_id']}'
        ";
        $currencyId = $gen_db->queryOneValue($query);

        // 受注取引通貨チェック
        $currencyFlag = 0;
        if (isset($form['received_header_id']) && is_numeric($form['received_header_id'])) {
            $query = "
            select
                received_detail.foreign_currency_id
            from
                received_header
                inner join received_detail on received_header.received_header_id = received_detail.received_header_id
            where
                received_header.received_header_id = '{$form['received_header_id']}'
            order by
                line_no
            ";
            $receivedCurrencyId = $gen_db->queryOneValue($query);
            if ($currencyId != $receivedCurrencyId)
                $currencyFlag = 1;
        }

        // 納品取引通貨チェック
        if (isset($form['delivery_header_id']) && is_numeric($form['delivery_header_id'])) {
            $query = "select foreign_currency_id from delivery_header where delivery_header_id = '{$form['delivery_header_id']}'";
            $deliveryCurrencyId = $gen_db->queryOneValue($query);
            if ($currencyId != $deliveryCurrencyId)
                $currencyFlag = 1;
        }

        return
            array(
                'status' => "success",
                'currency_flag' => $currencyFlag,
            );
    }

}