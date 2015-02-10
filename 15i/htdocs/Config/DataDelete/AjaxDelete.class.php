<?php

class Config_DataDelete_AjaxDelete extends Base_AjaxBase
{

    function _execute(&$form)
    {
        // CSRF対策
        if (!isset($form['gen_page_request_id']) || !Gen_Reload::reloadCheck($form['gen_page_request_id'])) {
            return
                array(
                    'status' => "failure",
                );
        }

        set_time_limit(0);

        $date = @$form['delete_date'];
        if (Gen_String::isDateString($date)) {
            Logic_DataDelete::deleteData($date);
            $status = "success";
        } else {
            $status = "failure";
        }

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("データ削除"), "", "[" . _g("基準日") . "] " . $date);

        // 通知メール
        $title = ("過去データの削除");
        $body = _g("過去データが削除されました。") . "\n\n"
                . "[" . _g("削除日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("削除者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("基準日") . "] " . $date . "\n"
                . "";
        Gen_Mail::sendAlertMail('config_datadelete_delete', $title, $body);

        return
            array(
                'status' => $status,
            );
    }

}