<?php

// リスト列・表示条件の表示非表示を登録

class Config_Setting_AjaxColHide extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if ($form['action_name'] == '') {
            return;
        }

        $user_id = Gen_Auth::getCurrentUserId();
        $pfx = "gen_columnadd_";

        $gen_db->begin();

        $table = 'column_info';
        if ($form['isSearch'] == 'true')
            $table = 'search_column_info';

        $action = str_replace('&gen_restore_search_condition=true', '', $form['action_name']);

        foreach ($form as $key => $val) {
            if (substr($key, 0, strlen($pfx)) == $pfx) {
                $number = substr($key, strlen($pfx));
                // カラム情報は必ず作成されているはず（ListBase）なので Update
                $data = array("column_hide" => ($val == "0" ? "true" : "false"));
                $where = "user_id = {$user_id} and action = '{$action}' and column_number = '{$number}'";
                $gen_db->update($table, $data, $where);
            }
        }
        $gen_db->commit();

        return;
    }

}