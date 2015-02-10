<?php

class Delivery_DataLock_AjaxLock extends Base_AjaxBase
{

    function _execute(&$form)
    {
        if (!Gen_String::isDateString(@$form['lock_year'] . "-" . @$form['lock_month'] . "-01")) {
            return 
                array(
                    "status" => "failure"
                );
        }

        // ロック処理実行
        // 1: 販売データロック
        if (Logic_DataLock::dataLock(1, $form['lock_year'], $form['lock_month'])) {
            $status = "success";
        } else {
            $status = "failure";
        }

        // データアクセスログ
        $msg = "[" . _g("ロック年月") . "]" . $form['lock_year'] . "-" . $form['lock_month'];
        Gen_Log::dataAccessLog(_g("販売データロック"), _g("更新"), $msg);

        return 
            array(
                "status" => $status
            );
    }

}