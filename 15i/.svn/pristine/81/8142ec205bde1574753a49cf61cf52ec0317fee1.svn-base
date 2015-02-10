<?php

class Manufacturing_Received_AjaxCustomerParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['customerId']) || !is_numeric(@$form['customerId']))
            return;

        // 売掛残高データの取得（temp_receivable。受注ベース、取引通貨別）
        // customer_id の限定を行ってはいけない。請求先があるかもしれないので。
        // 最終残高を求める。（2038年以降は日付と認識されない）
        $day = date('2037-12-31');
        Logic_Receivable::createTempReceivableTable($day, $day, 1, false);

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "
        select
            case when customer_master.price_percent is not null then customer_master.price_percent else price_percent_group_master.price_percent end as price_percent
            ,case when currency_master.currency_name is null then '{$keyCurrency}' else currency_master.currency_name end as currency_name_show
            ,coalesce(receivable_balance,0) as receivable_balance
            ,t_bill_customer.credit_line
            ,customer_master.remarks
            ,customer_master.remarks_2
            ,customer_master.remarks_3
            ,customer_master.remarks_4
            ,customer_master.remarks_5
        from
            customer_master
            inner join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
            left join currency_master on t_bill_customer.currency_id = currency_master.currency_id
            left join price_percent_group_master on customer_master.price_percent_group_id = price_percent_group_master.price_percent_group_id
            left join temp_receivable on t_bill_customer.customer_id = temp_receivable.customer_id
        where
            customer_master.customer_id = '{$form['customerId']}'
        ";

        $res = $gen_db->queryOneRowObject($query);

        // 修正モードの場合、売掛残高から今回の受注額を引く。
        // 得意先を変更した場合に対応するため、得意先idもwhere条件に含める。
        if (isset($form['receivedHeaderId']) && is_numeric($form['receivedHeaderId']) && is_numeric(@$res->receivable_balance)) {
            $receivedPrice = "case when t_detail.foreign_currency_id is null then t_detail.product_price else t_detail.foreign_currency_product_price end";
            $receivedSales = "gen_round_precision({$receivedPrice} * t_detail.received_quantity, t_bill_customer.rounding, t_bill_customer.precision)";
            $receivedTax = "case when t_detail.foreign_currency_id is null then gen_round_precision({$receivedPrice} * t_detail.received_quantity * t_detail.received_tax_rate / 100, t_bill_customer.rounding, t_bill_customer.precision) end";
            $query = "
            select
                sum(coalesce({$receivedSales},0) + coalesce({$receivedTax},0))
            from
                received_header
                inner join (
                    select
                        received_detail.*
                        /* 消費税率の取得 */
                        ,coalesce(coalesce(item_master.tax_rate,tax_rate_master.tax_rate),0) as received_tax_rate
                    from
                        received_detail
                        inner join item_master on received_detail.item_id = item_master.item_id
                        left join (
                            select
                                received_detail_id
                                ,max(apply_date) as max_apply_date
                            from
                                received_detail
                                left join tax_rate_master on received_detail.dead_line >= tax_rate_master.apply_date
                            group by
                                received_detail_id
                            ) as t_max_apply_date on received_detail.received_detail_id = t_max_apply_date.received_detail_id
                        left join tax_rate_master on t_max_apply_date.max_apply_date = tax_rate_master.apply_date
                    ) as t_detail on received_header.received_header_id = t_detail.received_header_id
                inner join customer_master on received_header.customer_id = customer_master.customer_id
                inner join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
            where
                received_header.received_header_id = '{$form['receivedHeaderId']}'
                and received_header.customer_id = '{$form['customerId']}'
            ";
            $amount = $gen_db->queryOneValue($query);
            if (is_numeric($amount))
                $res->receivable_balance -= $amount;
        }

        $obj = array(
            'price_percent' => @$res->price_percent,
            'currency_name' => @$res->currency_name_show,
            'receivable_balance' => Gen_Math::round(@$res->receivable_balance, 'round', 0),
            'credit_line' => ($res->credit_line === null ? '' : $res->credit_line),
            'remarks' => $res->remarks,
            'remarks_2' => $res->remarks_2,
            'remarks_3' => $res->remarks_3,
            'remarks_4' => $res->remarks_4,
            'remarks_5' => $res->remarks_5,
        );

        return $obj;
    }

}