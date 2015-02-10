<?php

class Partner_DataLock_AjaxLock extends Base_AjaxBase
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
        if (Logic_DataLock::dataLock(2, $form['lock_year'], $form['lock_month'])) {    // 2: 購買データロック
            $status = "success";
        } else {
            $status = "failure";
        }

        // データアクセスログ
        $msg = "[" . _g("ロック年月") . "]" . $form['lock_year'] . "-" . $form['lock_month'];
        Gen_Log::dataAccessLog(_g("購買データロック"), _g("更新"), $msg);

        return 
            array(
                "status" => $status
            );
    }

}