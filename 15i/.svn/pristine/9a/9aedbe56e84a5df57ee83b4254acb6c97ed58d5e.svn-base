<?php

class Config_Setting_AjaxDashboardInfo extends Base_AjaxBase
{

    function _execute(&$form)
    {
        if (isset($form['widgetIds']) && $form['widgetIds'] != '') {
            // パーツの並べ替えと開閉状態の変更
            $arr = explode(",", $form['widgetIds']);
            foreach($arr as $col) {
                if (substr($col, -1) == "c") {
                    $col = substr($col, 0, strlen($col)-1);
                }
                if (!Gen_String::isNumeric($col)) {
                    return;
                }
            }

            $_SESSION['gen_setting_user']->dashboardWidgetIds = $form['widgetIds'];
            Gen_Setting::saveSetting();
            
        } else {
            // パーツの選択ダイアログ
            $pfx = "gen_widgetselect_";
            $selectedIdsArr = array();
            foreach ($form as $key => $val) {
                if (substr($key, 0, strlen($pfx)) == $pfx) {
                    $widgetId = substr($key, strlen($pfx));
                    if (is_numeric($widgetId) && $val == "1") {
                        $selectedIdsArr[] = $widgetId;
                    }
                }
            }
            sort($selectedIdsArr);
            
            if (isset($_SESSION['gen_setting_user']->dashboardWidgetIds)) {
                $savedIdsArr = explode(",", $_SESSION['gen_setting_user']->dashboardWidgetIds);
                $newIdsArr = array();
                // 既存パーツ
                if (is_array($savedIdsArr)) {
                    foreach($savedIdsArr as $widgetData) {
                        $widgetId = str_replace("c", "", $widgetData);
                        if (in_array($widgetId, $selectedIdsArr) || $widgetId == "0") {
                            $newIdsArr[] = $widgetData;
                        }
                    }
                }
                // 新規追加パーツ
                if ($newIdsArr[0] == '0')
                    array_shift($newIdsArr);    // 0は列の先頭をあらわす
                foreach($selectedIdsArr as $selectedId) {
                    if (!in_array($selectedId, $newIdsArr)) {
                        // 新規追加
                        array_unshift($newIdsArr, $selectedId);
                    }
                }
                array_unshift($newIdsArr, "0");
                
                $_SESSION['gen_setting_user']->dashboardWidgetIds = join("," , $newIdsArr);
            } else {
                array_unshift($selectedIdsArr, "0");
                $_SESSION['gen_setting_user']->dashboardWidgetIds = join("," , $selectedIdsArr);
            }
        }
        
        return;
    }

}