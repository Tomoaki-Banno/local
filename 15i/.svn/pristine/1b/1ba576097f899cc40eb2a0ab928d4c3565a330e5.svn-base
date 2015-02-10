<?php

class Manufacturing_Plan_AjaxEntry extends Base_AjaxBase
{

    function validate($validator, &$form)
    {
        $validator->existRecord('plan_id', '', 'select plan_id from plan where plan_id = $1', false);
        $validator->range('day', '', 1, 31);
        $validator->numeric('value', '');
        return 'simple.tpl';    // if error
    }

    function _execute(&$form)
    {
        global $gen_db;

        if ($form['gen_readonly'] == "true" || !Gen_String::isNumeric($form['plan_id'])) {
            return
                array(
                    "success" => "false"
                );
        }

        $plan_id = $form['plan_id'];
        $day = "day" . $form['day'];
        $value = $form['value'];

        $data = array("{$day}" => $value);
        $where = "plan_id = '{$plan_id}'";
        $gen_db->update("plan", $data, $where);

        Logic_Plan::updatePlanQuantity($plan_id);

        // データアクセスログ
        $res = $gen_db->queryOneRowObject("select item_code, plan_year, plan_month from item_master
                    inner join plan on item_master.item_id = plan.item_id where plan_id = '{$plan_id}'");
        $msg = "[" . _g("日付") . "] " . sprintf(_g("%1\$s年%2\$s月%3\$s日"), $res->plan_year, $res->plan_month, $form['day']) . " [" . _g("品目コード") . "] " . $res->item_code;
        Gen_Log::dataAccessLog(_g("計画"), _g("簡易更新"), $msg);

        return
            array(
                "success" => "true"
            );
    }

}