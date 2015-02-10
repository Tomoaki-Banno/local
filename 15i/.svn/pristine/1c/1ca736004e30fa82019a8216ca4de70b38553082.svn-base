<?php

class Partner_Accepted_AjaxInspectionDate extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['accepted_date']) || !Gen_String::isDateString($form['accepted_date'])
                || !isset($form['order_detail_id']) || !is_numeric($form['order_detail_id'])) {
            $insDate = '';
        } else {
            $query = "select inspection_lead_time from customer_master where customer_id =
            	(select partner_id from order_header inner join order_detail on order_header.order_header_id = order_detail.order_header_id
            	where order_detail_id = '{$form['order_detail_id']}')";
            $lt = $gen_db->queryOneValue($query);

            if ($lt == '') {
                $insDate = '';
            } else {
                // func名は「getDeadLine」だが、ここでは検収日取得に使用している
                $insDate = date('Y-m-d', Gen_Date::getDeadLine(strtotime($form['accepted_date']), $lt));
            }
        }

        return
            array(
                'status' => "success",
                'inspection_date' => $insDate,
            );
    }

}