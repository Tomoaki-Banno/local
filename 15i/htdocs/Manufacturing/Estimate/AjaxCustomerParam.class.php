<?php

class Manufacturing_Estimate_AjaxCustomerParam extends Base_AjaxBase
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
            customer_master.customer_name
            ,customer_master.person_in_charge
            ,customer_master.zip
            ,customer_master.address1
            ,customer_master.address2
            ,customer_master.tel
            ,customer_master.fax
            ,t_bill_customer.credit_line
            ,coalesce(receivable_balance,0) as receivable_balance
            ,case when currency_master.currency_name is null then '{$keyCurrency}' else currency_master.currency_name end as currency_name_show
        from
            customer_master
            left join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
            left join temp_receivable on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = temp_receivable.customer_id
                and coalesce(customer_master.currency_id,-999999) = coalesce(temp_receivable.currency_id,-999999)
            left join currency_master on t_bill_customer.currency_id = currency_master.currency_id
        where
            customer_master.customer_id = '{$form['customerId']}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        return
            array(
                'customer_name' => $res->customer_name,
                'person_in_charge' => $res->person_in_charge,
                'zip' => $res->zip,
                'address1' => $res->address1,
                'address2' => $res->address2,
                'tel' => $res->tel,
                'fax' => $res->fax,
                'credit_line' => (isset($res->credit_line) && is_numeric($res->credit_line) ? $res->credit_line : ''),
                'receivable_balance' => Gen_Math::round(@$res->receivable_balance, 'round', 0),
                'currency_name' => @$res->currency_name_show,
            );
    }

}