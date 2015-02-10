<?php

class Config_Setting_AjaxPin extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if ($form['action_name'] == '' || $form['control_name1'] == '') {
            return;
        }
        $user_id = Gen_Auth::getCurrentUserId();
        $action = $form['action_name'];
        $control1 = $form['control_name1'];
        $control2 = $form['control_name2'];

        // 更新処理
        $query = "select pin_info from page_info where " . (isset($user_id) && is_numeric($user_id) ? "user_id = {$user_id}" : "1=0") . " and action = '{$action}'";
        $colInfoJson = $gen_db->queryOneValue($query);
        // 「\」は「￥」として登録されている。ここで戻しておかないと再登録の際に「￥」がエンコードされてしまう
        $colInfoJson = str_replace("￥", "\\", $colInfoJson);
        if ($colInfoJson == "") {
            $colInfoObj = (object) array();
        } else {
            $colInfoObj = json_decode($colInfoJson);
        }
        if (isset($form['turnOn'])) {
            $colInfoObj->$control1 = $form['control_value1'];
            if ($control2 != "")
                $colInfoObj->$control2 = $form['control_value2'];
        } else {
            unset($colInfoObj->$control1);
            if ($control2 != "")
                unset($colInfoObj->$control2);
        }
        $colInfoJson = json_encode($colInfoObj);

        if (isset($user_id) && is_numeric($user_id)) {
            // 登録の際、自動的に「\」が「￥」に変換されることに注意。
            $key = array("user_id" => $user_id, "action" => $action);
            $data = array(
                "pin_info" => $colInfoJson,
            );
            $gen_db->updateOrInsert('page_info', $key, $data);
        }

        return;
    }

}