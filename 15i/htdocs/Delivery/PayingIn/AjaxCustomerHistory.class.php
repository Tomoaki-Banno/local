<?php

class Delivery_PayingIn_AjaxCustomerHistory extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['customer_id']) || !is_numeric($form['customer_id'])) {
            return
                array(
                    "status" => "success", 
                    "recentBill" => "", 
                    "recentPayingIn" => ""
                );
        }

        if (@$form['recent_bill_or_delivery'] == '1') {
            // 納品
            $query = "
            select
                delivery_date
                ,coalesce(case when delivery_header.foreign_currency_id is null then delivery_amount else foreign_currency_delivery_amount end,0)
                    + coalesce(delivery_tax,0) as sales_with_tax
                ,item_name
                ,delivery_quantity
            from
                delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                inner join received_header on received_detail.received_header_id = received_header.received_header_id
                left join item_master on received_detail.item_id = item_master.item_id
            where
                received_header.customer_id = '{$form['customer_id']}'
            order by
                delivery_date desc
            limit 30
            ";
        } else {
            // 請求
            $query = "
            select
                close_date
                ,coalesce(case when bill_header.foreign_currency_id is null then sales_amount else foreign_currency_sales_amount end,0)
                    + coalesce(tax_amount,0) as sales_with_tax
                ,case when bill_header.foreign_currency_id is null then bill_amount else foreign_currency_bill_amount end as bill_amount
                ,receivable_date
            from
                bill_header
            where
                customer_id = '{$form['customer_id']}'
            order by
                close_date desc
            limit 30
            ";
        }

        $res1 = $gen_db->getArray($query);

        $classQuery = Gen_Option::getWayOfPayment('list-query');

        $query = "
        select
            paying_in_date
            ,case when paying_in.foreign_currency_id is null then amount else foreign_currency_amount end as amount
            ,case way_of_payment {$classQuery} end as way_of_payment_show
            ,remarks
        from
            paying_in
        where
            customer_id = '{$form['customer_id']}'
        order by
            paying_in_date desc
        limit 30
        ";
        $res2 = $gen_db->getArray($query);

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "
        select
            coalesce(customer_master.currency_id, -1) as currency_id
            ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name_show
            ,case when currency_name is null then '' else cast(coalesce(rate_master.rate,1) as text) end as rate
            ,precision
        from
            customer_master
            left join currency_master on customer_master.currency_id = currency_master.currency_id
            -- 当日時点のレートを取得
            left join (select currency_id, max(rate_date) as rate_date from rate_master where rate_date <= '" . date('Y-m-d') . "' group by currency_id) as t_rate
                    on currency_master.currency_id = t_rate.currency_id
            left join rate_master on currency_master.currency_id = rate_master.currency_id and t_rate.rate_date = rate_master.rate_date
        where
            customer_master.customer_id = '{$form['customer_id']}'
        ";
        $res3 = $gen_db->queryOneRowObject($query);


        return
            array(
                "status" => "success",
                "recentBill" => $res1,
                "recentPayingIn" => $res2,
                "currency_id" => $res3->currency_id,
                "currency_name" => $res3->currency_name_show,
                "rate" => $res3->rate,
                "precision" => $res3->precision,
            );
    }

}