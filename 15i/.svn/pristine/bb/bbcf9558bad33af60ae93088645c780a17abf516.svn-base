<?php

class Master_Holiday_AjaxHolidayRead extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!checkdate($form['month'], 1, $form['year']))
            return;

        $monthStart = date("Y/m/d", mktime(0, 0, 0, $form['month'], 1, $form['year']));
        $monthEnd = date("Y/m/d", mktime(0, 0, 0, $form['month'] + 1, 0, $form['year'])); // 「x月0日」は前月末日をあらわす

        $query = "select holiday from holiday_master where holiday between '{$monthStart}'::date and '{$monthEnd}'::date order by holiday";
        $res = $gen_db->getArray($query);

        $arr = array();
        if (is_array($res)) {
            foreach ($res as $row) {
                $arr[] = $row['holiday'];
            }
        }

        return
            array(
                'holiday' => $arr,
            );
    }

}