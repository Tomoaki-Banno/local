<?php

class Config_Personalize_Import
{

    function execute(&$form)
    {
        // トークンの確認（CSRF対策）
        //　　Ajax用のものを流用。トークンについての詳細はAjaxBaseのコメントを参照。
        if (!isset($form['gen_ajax_token']) || $_SESSION['gen_ajax_token'] != $form['gen_ajax_token']) {
            $form['response_noEscape'] = json_encode(array("status" => "tokenError", "success" => false, "msg" => ""));
            return 'simple.tpl';
        }
        
        $errMsg = self::_doExecute($form);
        
        $obj = array(
            'msg' => $errMsg,
            'success' => ($errMsg == ""),
        );
        $form['response_noEscape'] = json_encode($obj);
        return 'simple.tpl';
    }
    
    private function _doExecute($form)
    {
        global $gen_db;
        
        if (!isset($form['userId'])) {
            return _g("ユーザー指定が正しくありません。");
        } else {
            $userArr = explode(",", $form['userId']);
            foreach($userArr as $userId) {
                if (!Gen_String::isNumeric($userId)) {
                    return _g("ユーザー指定が正しくありません。");
                }
            }
        }

        if (!is_uploaded_file(@$_FILES['uploadFile']['tmp_name']) || $_FILES['uploadFile']['size'] == 0) {
            return _g("ファイルが正しくありません。");
        }
        
        // パーソナライズデータのインポート
        $json = file_get_contents($_FILES['uploadFile']['tmp_name']);
        $importData = json_decode($json);
        if (!$importData) {
            return _g("ファイルの形式が正しくありません。");
        }
        
        $format = Logic_Personalize::getPersonalizeParams();

        $gen_db->begin();

        // 既存データのクリア
        foreach($format as $table => $data) {
            $query = "delete from {$table} where user_id in ({$form['userId']})";
            $gen_db->query($query);
        }

        foreach($importData as $table => $tableData) {
            // 各テーブル
            if (!isset($format[$table])) {
                continue;
            }

            foreach ($tableData as $oneData) {
                // データ１行分
                $insertArr = array();
                foreach($oneData as $col => $data) {
                    if (!isset($format[$table][$col])) {
                        continue;
                    }
                    $ok = true;
                    switch($format[$table][$col]) {
                        case "int":
                            if (!Gen_String::isNumeric($data)) {
                                $ok = false;
                            }
                            break;
                        case "bool":
                            if ($data == "t") {
                                $data = "true";
                            } else if ($data == "f") {
                                $data = "false";
                            } else {
                                $ok = false;
                            }
                            break;
                    }
                    if ($ok) {
                        $insertArr[$col] = $gen_db->quoteParam($data);
                    }
                }

                if (count($insertArr) > 0) {
                    foreach($userArr as $userId) {
                        $insertArr['user_id'] = $userId;
                        $gen_db->insert($table, $insertArr);
                    }
                }
            }
        }
        
        // Setting関連
        if (isset($importData->user_setting)) {
            $srcSetting = json_decode($importData->user_setting);
            if ($srcSetting) {
                $settingParams = Logic_Personalize::getPersonalizeSettingParams();
                $query = "select user_id, setting from user_master where user_id in ({$form['userId']})";
                $userIdArr = explode(",", $form['userId']);
                if (in_array("-1", $userIdArr)) {
                    $query .= " union all select -1 as user_id, admin_setting as setting from company_master";
                }
                $distSettingArr = $gen_db->getArray($query);
                $query = "";
                foreach($distSettingArr as $distSettingOne) {
                    $distSetting = json_decode($distSettingOne['setting']);
                    foreach($settingParams as $param => $type) {
                        if (isset($srcSetting->$param)) {
                            $ok = true;
                            switch($type) {
                                case "int":
                                    if (!Gen_String::isNumeric($srcSetting->$param)) {
                                        $ok = false;
                                    }
                                    break;
                                case "bool":
                                    if ($srcSetting->$param == "t") {
                                        $srcSetting->$param = "true";
                                    } else if ($srcSetting->$param == "f") {
                                        $srcSetting->$param = "false";
                                    } else {
                                        $ok = false;
                                    }
                                    break;
                            }
                            if ($ok) {
                                $distSetting->$param = str_replace("￥", "[yen]", $srcSetting->$param);
                            }
                        } else {
                            unset($distSetting->$param);
                        }
                    }
                    $jsonDistSetting = str_replace("[yen]", "￥", json_encode($distSetting));
                    if ($distSettingOne['user_id'] == "-1") {
                        $query .= "update company_master set admin_setting = '{$jsonDistSetting}';";
                    } else {
                        $query .= "update user_master set setting = '{$jsonDistSetting}' where user_id = '{$distSettingOne['user_id']}';";
                    }
                }

                if ($query != "") {
                    $gen_db->query($query);
                }
            }
            
        }

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("パーソナライズ"), _g("インポート"), "");

        $gen_db->commit();
        
        return "";  // success
    }

}