<?php

class Config_Personalize_AjaxCopy extends Base_AjaxBase
{
    function _execute(&$form)
    {
        $errMsg = self::_doCopy($form);
        
        $obj = array(
            'msg' => $errMsg,
            'result' => ($errMsg == "" ? 'success' : 'failure'),
        );
        return $obj;
    }
    
    private function _doCopy($form)
    {
        global $gen_db;

        if (!isset($form['srcUserId']) || !Gen_String::isNumeric($form['srcUserId'])) {
            return _g("コピー元ユーザー指定が正しくありません。");
        }
        if (!isset($form['distUserId'])) {
            return _g("コピー先ユーザー指定が正しくありません。");
        } else {
            $distUserArr = explode(",", $form['distUserId']);
            foreach($distUserArr as $key => $userId) {
                if (!Gen_String::isNumeric($userId)) {
                    return _g("コピー先ユーザー指定が正しくありません。");
                }
                if ($userId == $form['srcUserId']) {
                    unset($distUserArr[$key]);
                }
            }
            $form['distUserId'] = join(",", $distUserArr);
        }
        
        $format = Logic_Personalize::getPersonalizeParams();

        $gen_db->begin();

        $query = "";
        $distIdArr = explode(",", $form['distUserId']);
        foreach($format as $table => $data) {
            $query .= "delete from {$table} where user_id in ({$form['distUserId']});";
            
            $colList = "";
            foreach($data as $col => $type) {
                if ($colList != "") {
                    $colList .= ",";
                }
                $colList .= $col;
            }
            foreach($distIdArr as $distId) {
                $query .= "insert into {$table} (user_id, {$colList}) select {$distId} as user_id, {$colList} from {$table} where user_id = {$form['srcUserId']};";
            }
        }
        $gen_db->query($query);
        
        // Setting関連
        if ($form['srcUserId'] == '-1') {
            $query = "select admin_setting from company_master";
        } else {
            $query = "select setting from user_master where user_id = {$form['srcUserId']}";
        }
        $jsonSrcSetting = $gen_db->queryOneValue($query);
        if ($jsonSrcSetting) {
            $settingParams = Logic_Personalize::getPersonalizeSettingParams();
            $srcSetting = json_decode($jsonSrcSetting);
            $query = "select user_id, setting from user_master where user_id in ({$form['distUserId']})";
            if (in_array("-1", $distIdArr)) {
                $query .= " union all select -1 as user_id, admin_setting as setting from company_master";
            }
            $distSettingArr = $gen_db->getArray($query);
            $query = "";
            foreach($distSettingArr as $distSettingOne) {
                $distSetting = json_decode($distSettingOne['setting']);
                foreach($settingParams as $param => $type) {
                    if (isset($srcSetting->$param)) {
                        $distSetting->$param = str_replace("￥", "[yen]", $srcSetting->$param);
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
        

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("パーソナライズ"), _g("コピー"), "");

        $gen_db->commit();
        
        return "";  // success
    }
}