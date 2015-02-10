<?php

class Master_Holiday_AjaxHolidayEntry extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!checkdate($form['selMonth'], $form['selDay'], $form['selYear']))
            return;
        
        $selDate = $form['selYear'] . '/' . $form['selMonth'] . '/' . $form['selDay'];

        // 指定日が登録されていれば消去し、登録されていなければ登録する
        $query = "select holiday from holiday_master where holiday = '{$selDate}'";
        if ($gen_db->queryOneValue($query) != '') {
            $query = "delete from holiday_master where holiday = '{$selDate}'";
            $gen_db->query($query);

            // データアクセスログ
            Gen_Log::dataAccessLog(_g("カレンダーマスタ"), _g("休日削除"), $selDate);
        } else {
            // データベースへ登録
            $key = array('holiday' => $selDate);
            $gen_db->updateOrInsert('holiday_master', $key, array());

            // データアクセスログ
            Gen_Log::dataAccessLog(_g("カレンダーマスタ"), _g("休日登録"), $selDate);
        }

        return;
    }

}