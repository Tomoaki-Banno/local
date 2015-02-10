<?php

class Manufacturing_Received_AjaxDeadlineCheck extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['deadline']) || $form['deadline'] == "")
            return;

        $res = "";
        $lineNo = "";
        $list = explode(",", $form['deadline']);
        foreach ($list as $param) {
            $d = explode(":", $param);   // [0]:行、[1]:受注納期
            if (!Gen_String::isDateString($d[1])) {
                $res = 'incorrect';
                $lineNo = $d[0];
                break;
            } else {
                $query = "select * from holiday_master where holiday = '{$d[1]}'";
                if ($gen_db->existRecord($query)) {
                    $res = 'holiday';
                    // incorrectがあるかもしれないのでここではbreakしない
                }
            }
        }

        return
            array(
                'result' => $res,
                'lineNo' => $lineNo,
            );
    }

}