<?php

// Edit項目の表示非表示を登録

class Config_Setting_AjaxControlHide extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if ($form['action_name'] == '') {
            return;
        }

        $user_id = Gen_Auth::getCurrentUserId();
        $pfx = "gen_controladd_";

        $gen_db->begin();

        $table = 'control_info';

        $action = str_replace('&gen_restore_search_condition=true', '', $form['action_name']);

        foreach ($form as $key => $val) {
            if (substr($key, 0, strlen($pfx)) == $pfx) {
                $number = substr($key, strlen($pfx));
                // カラム情報は必ず作成されているはず（EditBase）なので Update
                $data = array("control_hide" => ($val == "0" ? "true" : "false"));
                $where = "user_id = $user_id and action = '$action' and control_number = '$number'";
                $gen_db->update($table, $data, $where);
            }
        }
        $gen_db->commit();

        return;
    }

}