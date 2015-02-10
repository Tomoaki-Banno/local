<?php

class Master_Holiday_AjaxHolidayBulkEntry extends Base_AjaxBase
{

    // 土日を一括で休日登録する。20年分
    // ホントは毎日ループするより、最初の土曜日と日曜日から7日間隔で登録していくほうが効率いいが、手を抜いている
    function _execute(&$form)
    {
        global $gen_db;

        for ($day = 0; $day <= 365 * 20; $day++) {
            $w = date('w', strtotime("{$day} day"));
            if ($w == 0 || $w == 6) {    // 日曜日 or 土曜日
                $entDate = date('Y-m-d', strtotime("{$day} day"));

                // データベースへ登録
                $key = array('holiday' => $entDate);

                $gen_db->updateOrInsert('holiday_master', $key, array());
            }
        }

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("カレンダーマスタ"), _g("休日一括登録"), "");

        return;
    }

}