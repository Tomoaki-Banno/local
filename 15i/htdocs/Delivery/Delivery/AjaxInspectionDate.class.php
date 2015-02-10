<?php

class Delivery_Delivery_AjaxInspectionDate extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['delivery_date']) || !Gen_String::isDateString($form['delivery_date'])
                || !isset($form['customer_id']) || !is_numeric($form['customer_id'])) {
            $insDate = '';
        } else {
            $query = "select inspection_lead_time from customer_master where customer_id = '{$form['customer_id']}'";
            $lt = $gen_db->queryOneValue($query);

            if ($lt == '') {
                $insDate = '';
            } else {
                // func名は「getDeadLine」だが、ここでは検収日取得に使用している
                $insDate = date('Y-m-d', Gen_Date::getDeadLine(strtotime($form['delivery_date']), $lt));
            }
        }

        return
            array(
                'status' => "success",
                'inspection_date' => $insDate,
            );
    }

}