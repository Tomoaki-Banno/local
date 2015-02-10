<?php

class Monthly_Bill_AjaxLimitDateParam extends Base_AjaxBase
{
    // 締日グループを取得し請求締日を返す。

    function _execute(&$form)
    {
        global $gen_db;

        // 数字
        if (!isset($form['limit_date']) || !is_numeric($form['limit_date'])) {
            return
                array(
                    "status" => "failure"
                );
        }

        // 日付取得
        $limitDate = $form['limit_date'];
        if ($limitDate == "31") {
            $date = date('Y-m-t');
        } else {
            $date = date('Y-m-d', mktime(0, 0, 0, date('m'), $limitDate, date('Y')));
        }

        if (strtotime($date) > strtotime(date('Y-m-d'))) {
            if ($limitDate == "31") {
                $date = date("Y-m-t", strtotime(date('Y-m-01') . ' -1 month'));
            } else {
                $date = date('Y-m-d', mktime(0, 0, 0, date('m')-1, $limitDate, date('Y')));
            }
        }

        return
            array(
                'status' => "success",
                'close_date' => $date,
            );
    }
}