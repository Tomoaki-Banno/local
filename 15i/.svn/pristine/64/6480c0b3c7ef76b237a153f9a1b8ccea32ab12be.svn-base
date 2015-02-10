<?php

class Master_Item_AjaxLeadTime extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (is_numeric(@$form['partner_id'])) {
            $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
            $query = "
            select
                default_lead_time
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name
            from
                customer_master
                left join currency_master on customer_master.currency_id = currency_master.currency_id
            where
                customer_id = '{$form['partner_id']}'
             ";
        } else if (is_numeric(@$form['process_id'])) {
            $query = "
            select
                default_lead_time
                ,'' as currency_name
            from
                process_master
            where
                process_id = '{$form['process_id']}'
            ";
        } else {
            return;
        }
        $res = $gen_db->queryOneRowObject($query);

        return
            array(
                'default_lead_time' => @$res->default_lead_time,
                'currency_name' => @$res->currency_name,
            );
    }

}