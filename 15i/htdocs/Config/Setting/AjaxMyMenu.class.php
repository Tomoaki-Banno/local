<?php

class Config_Setting_AjaxMyMenu extends Base_AjaxBase
{

    function _execute(&$form)
    {
        if (!isset($form['op'])) {
            return;
        }
        
        // Stock_Inout用の特別処理（index.phpの「Stock_Inoutの特別処理」も参照）
        if (isset($form['classification'])) {
            $form['action_name'] .= "&classification={$form['classification']}";
        }
        
        $myMenu = isset($_SESSION['gen_setting_user']->myMenu) ? $_SESSION['gen_setting_user']->myMenu : "";
        
        switch($form['op']) {
            case "reset":
                unset($_SESSION['gen_setting_user']->myMenu);
                Gen_Setting::saveSetting();
                break;
            
            case "delete":
                if ($form['action_name'] == '' || $form['page_name'] == '') {
                    return;
                }
                if (strpos($myMenu, $form['action_name']) !== FALSE) {
                    // 削除処理
                    $myMenuArr = explode(",", $myMenu);
                    $newMyMenu = "";
                    foreach ($myMenuArr as $myMenu) {
                        $myMenuSub = explode(":", $myMenu);
                        if ($myMenuSub[0] != $form['action_name']) {
                            if ($newMyMenu != "")
                                $newMyMenu .= ",";
                            $newMyMenu .= $myMenuSub[0] . ":" . $myMenuSub[1];
                        }
                    }
                    $_SESSION['gen_setting_user']->myMenu = $newMyMenu;
                    Gen_Setting::saveSetting();
                }
                break;
                
            case "reg":
                if ($form['action_name'] == '' || $form['page_name'] == '') {
                    return;
                }
                if (strpos($myMenu, $form['action_name']) === FALSE) {
                    // 登録処理
                    if ($myMenu != "") {
                        $myMenu .= ",";
                    }
                    $myMenu .= $form['action_name'] . ":" . $form['page_name'];
                    $_SESSION['gen_setting_user']->myMenu = $myMenu;
                    Gen_Setting::saveSetting();
                }
                break;
            
            case "sortreg":
                if ($form['ids'] == '') {
                    return;
                }
                // ソート処理
                $idArr = explode(",", $form['ids']);
                $myMenuArr = explode(",", $myMenu);
                $newMyMenu = "";
                foreach ($idArr as $id) {
                    $action = str_replace("gen_menu_", "", $id);
                    foreach ($myMenuArr as $myMenu) {
                        $myMenuSub = explode(":", $myMenu);
                        if ($myMenuSub[0] == $action) {
                            if ($newMyMenu != "") {
                                $newMyMenu .= ",";
                            }
                            $newMyMenu .= $myMenuSub[0] . ":" . $myMenuSub[1];
                        }
                    }
                }
                $_SESSION['gen_setting_user']->myMenu = $newMyMenu;
                Gen_Setting::saveSetting();
                break;
                
            default:
                return;
        }

        return 
            array(
                "result" => "success"
            );
    }
}