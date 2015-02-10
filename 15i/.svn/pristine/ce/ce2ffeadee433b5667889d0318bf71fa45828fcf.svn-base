<?php

class Config_Background_Entry
{

    function execute(&$form)
    {
        global $gen_db;

        // CSRF対策
        if (!Gen_Reload::reloadCheck(@$form['gen_page_request_id'])) {
            return 'action:Config_Background_Edit';
        }

        // モード取得
        $mode = isset($form['mode']) && is_numeric($form['mode']) ? $form['mode'] : 0;

        // 画像選択
        $image = "";
        if ($mode == 1) {
            foreach ($form as $key => $val) {
                if (substr($key, 0, 6) == "check_") {
                    if ($val == "1") {
                        $image .= substr($key, 6) . ";";
                    }
                }
            }
        }

        // 登録
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            $data = array(
                'background_mode' => $mode,
                'background_image' => $image,
            );
            $where = "user_id = {$_SESSION['user_id']}";
            $gen_db->update('user_master', $data, $where);
        }
        
        $_SESSION['gen_setting_user']->slideshowSpeed = $form['slideshowSpeed'];
        Gen_Setting::saveSetting();

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("パティオ画像"), _g("更新"), $mode == 1 ? _g("マイセレクト") : _g("自動セレクト"));

        $form['result'] = "success";
        return 'action:Config_Background_Edit';
    }

}
