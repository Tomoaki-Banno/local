<?php

class Config_Setting_AjaxListFilterReset extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if ($form['action_name'] == '') {
            return;
        }

        // クロス集計時は列情報の保存を行わない。（リスト列が通常時とは異なるので、保存できない）
        // クロス集計時かどうかはクライアントからのパラメータに頼っている。そのため不正POSTすればチェックを抜けられてしまう。
        // しかし、現在表示されているのがクロス集計であるかどうかを、サーバー側で確実に判断する方法がない。
        // それに、仮に不正POSTを行われても、当人の画面表示がおかしくなるだけ。
        if (isset($form['is_cross']) && $form['is_cross'] == "true") 
            return;

        $user_id = Gen_Auth::getCurrentUserId();
        $action = $form['action_name'];
        if (isset($user_id) && is_numeric($user_id)) {
            $where = "user_id = {$user_id} and action = '{$action}'";
            $gen_db->update("column_info", array("column_filter" => ""), $where);
        }

        return;
    }

}