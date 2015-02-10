<?php

class Manufacturing_CustomerEdi_AjaxCustomerParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // 売掛残高データの取得（temp_receivable。受注ベース、取引通貨別）
        // customer_id の限定を行ってはいけない。請求先があるかもしれないので
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
        from
            customer_master
            inner join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
            left join currency_master on t_bill_customer.currency_id = currency_master.currency_id
            left join price_percent_group_master on customer_master.price_percent_group_id = price_percent_group_master.price_percent_group_id
            left join temp_receivable on t_bill_customer.customer_id = temp_receivable.customer_id
        where
            customer_master.customer_id = '{$_SESSION["user_customer_id"]}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        // 修正モードの場合、売掛残高から今回の受注額を引く。
        // 得意先を変更した場合に対応するため、得意先idもwhere条件に含める。
        if (isset($form['receivedHeaderId']) && is_numeric($form['receivedHeaderId']) && is_numeric(@$res->receivable_balance)) {
            $query = "
            select
                sum(case when foreign_currency_id is null then product_price else foreign_currency_product_price end * received_quantity)
            from
                received_header
                inner join received_detail on received_header.received_header_id = received_detail.received_header_id
            where
                received_header.received_header_id = '{$form['receivedHeaderId']}'
                and received_header.customer_id = '{$_SESSION['user_customer_id']}'
            ";
            $amount = $gen_db->queryOneValue($query);
            if (is_numeric($amount))
                $res->receivable_balance -= $amount;
        }

        return
            array(
                'currency_name' => @$res->currency_name_show,
                'receivable_balance' => (@$res->receivable_balance === null ? '' : Gen_Math::round(@$res->receivable_balance, 'round', 0)),
                'credit_line' => (@$res->credit_line === null ? '' : $res->credit_line),
            );
    }

}