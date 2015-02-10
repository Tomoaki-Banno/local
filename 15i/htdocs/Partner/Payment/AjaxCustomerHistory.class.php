<?php

class Partner_Payment_AjaxCustomerHistory extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['customer_id']) || !is_numeric($form['customer_id'])) {
            return
                array(
                    "status" => "success", 
                    "recentAccept" => "", 
                    "recentPayment" => ""
                );
        }

        $query = "
        select
            accepted_date
            ,inspection_date
            ,coalesce(case when order_detail.foreign_currency_id is null then accepted_amount else foreign_currency_accepted_amount end,0)
                    + coalesce(accepted_tax,0) as accepted_with_tax
            ,payment_date
            ,order_detail.order_no
            ,order_detail.item_name
        from
            accepted
            inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
            inner join order_header on order_detail.order_header_id = order_header.order_header_id
        where
            partner_id = '{$form['customer_id']}'
        order by
            accepted_date desc
        limit 30
        ";

        $res1 = $gen_db->getArray($query);

        $classQuery = Gen_Option::getWayOfPayment('list-query');

        $query = "
        select
            payment_date
            ,coalesce(case when payment.foreign_currency_id is null then amount else foreign_currency_amount end,0)
                + coalesce(adjust_amount,0) as payment_with_adjust
            ,case way_of_payment {$classQuery} end as way_of_payment_show
            ,remarks
        from
            payment
        where
            customer_id = '{$form['customer_id']}'
        order by
            payment_date desc
        limit 30
        ";
        $res2 = $gen_db->getArray($query);

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "
        select
            coalesce(customer_master.currency_id, -1) as currency_id
            ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name_show
            ,case when currency_name is null then '' else cast(coalesce(rate_master.rate,1) as text) end as rate
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
                "recentAccept" => $res1,
                "recentPayment" => $res2,
                "currency_id" => $res3->currency_id,
                "currency_name" => $res3->currency_name_show,
                "rate" => $res3->rate
            );
    }

}