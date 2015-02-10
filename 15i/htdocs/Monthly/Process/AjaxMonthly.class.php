<?php

// このクラスは以前は「月次処理」だったが、08iで「過去データのロック」に変更された
class Monthly_Process_AjaxMonthly extends Base_AjaxBase
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
        if (Logic_DataLock::dataLock(0, $form['lock_year'], $form['lock_month'])) {
            $status = "success";
        } else {
            $status = "failure";
        }

        // データアクセスログ
        $msg = "[" . _g("ロック年月") . "]" . $form['lock_year'] . "-" . $form['lock_month'];
        Gen_Log::dataAccessLog(_g("過去データロック"), _g("更新"), $msg);

        return
            array(
                "status" => $status
            );
    }

}