<?php

@define('ROW_NUM', 10);

class Delivery_PayingIn_AjaxBillParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['bill_header_id']) || !is_numeric(@$form['bill_header_id']))
            return;

        $query = "
        select
            case when foreign_currency_id is null then (case when coalesce(bill_amount,0) < coalesce(total_amount,0) then 0 else coalesce(bill_amount,0) - coalesce(total_amount,0) end)
                else (case when coalesce(foreign_currency_bill_amount,0) < coalesce(total_foreign_currency_amount,0) then 0 else coalesce(foreign_currency_bill_amount,0) - coalesce(total_foreign_currency_amount,0) end) end as bill_amount
        from
            bill_header
            left join (select bill_header_id as bhid, sum(amount) as total_amount, sum(foreign_currency_amount) as total_foreign_currency_amount
                from paying_in " . (isset($form['paying_in_id']) && is_numeric($form['paying_in_id']) ? "where paying_in_id <> '{$form['paying_in_id']}'" : "") . "
                group by bill_header_id) as t_paying on bill_header.bill_header_id = t_paying.bhid
        where
            bill_header.bill_header_id = '{$form['bill_header_id']}'
        ";
        $bill_amount = $gen_db->queryOneValue($query);

        for ($i = 1; $i <= ROW_NUM; $i++) {
            if (isset($form["bill_header_id_{$i}"]) && is_numeric($form["bill_header_id_{$i}"]) && $form["bill_header_id_{$i}"] == $form['bill_header_id']) {
                if (isset($form["amount_{$i}"]) && is_numeric($form["amount_{$i}"]))
                    $bill_amount = $bill_amount - $form["amount_{$i}"];
            }
        }

        return
            array(
                'bill_amount' => (isset($bill_amount) && is_numeric($bill_amount) ? $bill_amount : ''),
            );
    }

}