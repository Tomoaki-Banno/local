<?php

class Config_Setting_AjaxCsvFormatInfo extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['csvAction'])) {
            return "";
        }
        
        $actionSepForParam = explode("&", $form['csvAction']);
        $actionName = $actionSepForParam[0];            // 純粋なaction name
        $actionNameWithClassification = $actionName;
        $classification = "";
        for ($i=1; $i<count($actionSepForParam); $i++) {
            if (substr($actionSepForParam[$i], 0, 15) == "classification=") {
                $classification = substr($actionSepForParam[$i], 15);
                $actionNameWithClassification .= "_" . $classification;
                break;
            }
        }
        
        $actionNameSep = explode("_", $actionName);
        if (count($actionNameSep) >= 2) {
            $classGroup = $actionNameSep[0] . "_" . $actionNameSep[1];
        } else {
            return "";
        }
        if (Gen_Auth::sessionCheck(strtolower($classGroup)) != 2) {
            return "";
        }

        require_once(Gen_File::safetyPathForAction($actionName));
        $action = new $actionName;
        if ($classification != "") {
            $form["classification"] = $classification;
        }
        // BOMのCSVは特殊。Master_Bom_List には setCsvParam() がない
        if (!method_exists($action, "setCsvParam")) {
            return "";
        }
        
        $action->setCsvParam($form);
        if (!isset($form['gen_csvArray'])) {
            return "";
        }
        $customColumnArr = Logic_CustomColumn::getCustomColumnParamByClassGroup($classGroup);
        if (isset($customColumnArr[1])) {
            foreach($customColumnArr[1] as $customCol => $customArr) {
                $customName = $customArr[1];
                $form["gen_csvArray"][] =
                    array(
                        'label' => $customName,
                        'field' => "gen_custom_{$customCol}",
                    );
            }
        }
        
        $selectedFormat = "";
        $settingName = "gen_csv_format_" . $actionNameWithClassification;
        if (isset($form['formatName'])) {
            // フォーマットが指定されている場合は、そのフォーマットを選択状態にする
            $_SESSION['gen_setting_user']->$settingName = $form['formatName'];
            Gen_Setting::saveSetting();
        }
        
        if (isset($_SESSION['gen_setting_user']->$settingName)) {
            $selectedFormat = $_SESSION['gen_setting_user']->$settingName;
        }
        
        if (isset($form['dataMode'])) {
            // 選択されたフォーマットデータを取得
            $formatInfo = array();
            $data = "";
            if ($selectedFormat != "") {
                $query = "select format_data from csv_format where action='{$actionNameWithClassification}' and format_name = '{$selectedFormat}'";
                $data = $gen_db->queryOneValue($query);
            }

            return
                array(
                    'format_data' => str_replace("￥", "\\", $data),
                );
            
        } else {
            // フォーマット一覧の取得
            $formatInfo = array();
            $query = "select * from csv_format where action='{$actionNameWithClassification}'";
            $arr = $gen_db->getArray($query);
            if ($arr) {
                foreach($arr as $row) {
                    $formatInfo[] = array(
                        "format" => $row['format_name'],
                        "description" => $row['description'],
                        "uploader" => $row['record_creator'],
                        "date" => $row['record_create_date'],
                    );
                }
            }

            return
                array(
                    'format_info' => $formatInfo,
                    'csv_array' => $form['gen_csvArray'],
                    'selected_format' => $selectedFormat,
                );
        }
        
    }

}