<?php

class Config_Setting_AjaxCsvFormatRegist extends Base_AjaxBase
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
            // 入出庫画面のフォーマット登録時はclassificationが指定されているが、単なる「classification」だと
            // 登録CSV項目名とかぶるので、JS側で名前を変換している
            if (substr($actionSepForParam[$i], 0, 19) == "gen_classification=") {
                $classification = substr($actionSepForParam[$i], 19);
                $actionNameWithClassification .= "_" . $classification;
                break;
            }
        }

        $actionNameSep = explode("_", $actionName);
        $classGroup = "";
        if (count($actionNameSep) >= 2) {
            $classGroup = $actionNameSep[0] . "_" . $actionNameSep[1];
        } else {
            return "";
        }
        if (Gen_Auth::sessionCheck(strtolower($classGroup)) != 2) {
            return "";
        }
        if (!isset($form['name'])) {
            return "";
        }
        
        $gen_db->begin();

        $selectedFormat = "";
        if (isset($form['delete'])) {
            // 削除モード
            $query = "delete from csv_format where format_name = '{$form['name']}'";
            $gen_db->query($query);
            
            // 選択フォーマットの調整
            $settingName = "gen_csv_format_" . $actionNameWithClassification;
            if (!isset($_SESSION['gen_setting_user']->$settingName)) {
                $selectedFormat = "";
            } else if ($_SESSION['gen_setting_user']->$settingName == $form['name']) {
                $_SESSION['gen_setting_user']->$settingName = "";
                Gen_Setting::saveSetting();
                $selectedFormat = "";
            } else {
                $selectedFormat =  $_SESSION['gen_setting_user']->$settingName;
            }
            
            // ログ
            Gen_Log::dataAccessLog(_g("CSVフォーマット"), _g("削除"), $actionNameWithClassification . " " . $form['name']);
            
        } else {
            // 登録モード
        
            require_once(Gen_File::safetyPathForAction($actionName));
            $action = new $actionName;
            if ($classification != "") {
                $orgClassification = $form["classification"];
                $form["classification"] = $classification;
            }
            $action->setCsvParam($form);
            if (!isset($form['gen_csvArray'])) {
                return "";
            }
            if ($classification != "") {
                $form["classification"] = $orgClassification;
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
            
            // 数式処理用
            // 同様の処理が Gen_Csv::CsvImportForModel() にもある。
            // 下の2行は、式の一部がセルやシートの名前とみなされる場合（"=test" など）に Fatal Error になるのを避けるための定義。
            //  CALCULATION_REGEXP_NAMEDRANGE をダミー値にしているのがポイント
            define('CALCULATION_REGEXP_CELLREF','(((\w*)|(\'[^\']*\')|(\"[^\"]*\"))!)?\$?([a-z]{1,3})\$?(\d+)');
            define('CALCULATION_REGEXP_NAMEDRANGE','DUMMY_STRING');
            require_once ROOT_DIR . "/PHPExcel/PHPExcel/Calculation.php";
            $calculation = PHPExcel_Calculation::getInstance();

            $isTab = (isset($form['isTab']) && $form['isTab'] == "true");
            $headerNumberOfLines = (isset($form['headerNumberOfLines']) && Gen_String::isNumeric($form['headerNumberOfLines']) ? $form['headerNumberOfLines'] : 1);
            $dataArr = array(
                "gen_isTab" => $isTab,
                "gen_headerNumberOfLines" => $headerNumberOfLines,
            );
            foreach($form['gen_csvArray'] as $col) {
                if (!isset($form[$col['field']])) {
                    return "";
                }
                list($type, $value) = explode("[sep]", $form[$col['field']]);
                switch ($type) {
                    case "0":   // 列参照
                        if (!Gen_String::isNumeric($value)) {
                            return "";
                        }
                        break;
                    case "1":   // 固定値/数式
                        try {
                            $calculation->calculateFormula(preg_replace("/\[[0-9]+\]/", "\"あ\"", $value));
                        } catch(Exception $e) {
                            // このエラーだけはクライアント側でチェックされていないのでメッセージをちゃんと出しておく
                            return array("field" => $col['field'], "msg" => _g("数式が正しくありません。"));
                        }
                        break;
                    case "2":   // ブランク
                    case "3":   // ファイル名
                        $value = "";
                        break;
                    default:
                        return "";
                }
                $dataArr[$col['field']] = $form[$col['field']];
            }

            $userId = Gen_Auth::getCurrentUserId();

            $query = "delete from csv_format where action='{$actionNameWithClassification}' and format_name = '{$form['name']}'";
            $gen_db->query($query);
            
            $data = array(
                'action' => $actionNameWithClassification, 
                'format_name' => $form['name'],
                'format_data' => json_encode($dataArr),
                'description' => (isset($form['desc']) ? $form['desc'] : ""),
            );
            $gen_db->insert('csv_format', $data);

            // いま登録したフォーマットを選択状態に
            $settingName = "gen_csv_format_" . $actionNameWithClassification;
            $_SESSION['gen_setting_user']->$settingName = $form['name'];
            Gen_Setting::saveSetting();
            $selectedFormat = $form['name'];

            // ログ
            Gen_Log::dataAccessLog(_g("CSVフォーマット"), _g("登録"), $actionNameWithClassification . " " . $form['name']);
        }
        
        $gen_db->commit();

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
                'success' => true,
                'format_info' => $formatInfo,
                'selected_format' => $selectedFormat,
            );
    }

}