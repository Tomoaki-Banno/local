<?php

class Delivery_Delivery_AjaxCustomerParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['customerId']) || !is_numeric(@$form['customerId']))
            return;

        // 売掛残高データの取得（temp_receivable。納品ベース、取引通貨別）
        // customer_id の限定を行ってはいけない。請求先があるかもしれないので。
        // 最終残高を求める。（2038年以降は日付と認識されない）
        $day = date('2037-12-31');
        Logic_Receivable::createTempReceivableTable($day, $day, 0, false);

        $query = "
        select
            coalesce(receivable_balance,0) as receivable_balance
            ,t_bill_customer.credit_line
        from
            customer_master
            left join temp_receivable on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = temp_receivable.customer_id
                and coalesce(customer_master.currency_id,-999999) = coalesce(temp_receivable.currency_id,-999999)
            left join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
        where
            customer_master.customer_id = '{$form['customerId']}'
        ";

        $res = $gen_db->queryOneRowObject($query);

        // 修正モードの場合、売掛残高から今回の納品額を引く。
        if (isset($form['deliveryHeaderId']) && is_numeric($form['deliveryHeaderId']) && is_numeric(@$res->receivable_balance)) {
            $query = "
            select
                sum(case
                    when delivery_header .foreign_currency_id is null then delivery_price * delivery_quantity + delivery_tax
                    else foreign_currency_delivery_price * delivery_quantity end)
            from
                delivery_detail
                left join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
            where
                delivery_detail.delivery_header_id = '{$form['deliveryHeaderId']}'
            ";
            $amount = $gen_db->queryOneValue($query);
            if (is_numeric($amount))
                $res->receivable_balance -= $amount;
        }

        return
            array(
                'receivable_balance' => Gen_Math::round(@$res->receivable_balance, 'round', 0),
                'credit_line' => ($res->credit_line === null ? '' : $res->credit_line),
            );
    }

}