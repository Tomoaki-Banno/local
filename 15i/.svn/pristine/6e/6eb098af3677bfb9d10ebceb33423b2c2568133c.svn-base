<?php

/**
 * Gen_Setting
 *
 * @author S.Ito
 * @copyright 2008 e-commode
 */

/**
 * 設定値関連クラス
 *
 * @author S.Ito
 */
class Gen_Setting
{

    /**
     * company_setting、user_setting を session変数に読み出す（DB⇒Session）。
     * ログイン時に実行。
     *
     * @access  public
     * @param   int     $user_id   ユーザーID。省略時はsessionから読み出される
     * @param   class   $gen_db
     * @return  bool               成功ならtrue
     */
    static function loadSetting($user_id)
    {
        global $gen_db;

        unset($_SESSION["gen_setting_company"]);
        unset($_SESSION["gen_setting_user"]);

        if (!is_numeric($user_id)) {
            $user_id = Gen_Auth::getCurrentUserId($gen_db);
        }

        $query = "select setting, company_setting_update_time from company_master";
        $obj = $gen_db->queryOneRowObject($query);
        $companySetting = $obj->setting;
        
        $query = "select setting from user_master where user_id = {$user_id}";
        $userSetting = $gen_db->queryOneValue($query);
        if ($user_id == -1) {
            // admin
            $query = "select admin_setting from company_master";
            $userSetting = $gen_db->queryOneValue($query);
        } else {
            // 一般
            $query = "select setting from user_master where user_id = '{$user_id}'";
            $userSetting = $gen_db->queryOneValue($query);
        }

        // DB格納時（$gen_db->update）にエスケープされている文字を元に戻す
        $companySetting = str_replace("￥", "\\", $companySetting);
        $userSetting = str_replace("￥", "\\", $userSetting);

        if ($companySetting == "") {
            $_SESSION["gen_setting_company"] = (object)"";
        } else {
            $_SESSION["gen_setting_company"] = json_decode($companySetting);
        }
        $_SESSION["gen_setting_company_update_time"] = $obj->company_setting_update_time;
        if ($userSetting == "") {
            $_SESSION["gen_setting_user"] = (object)"";
        } else {
            $_SESSION["gen_setting_user"] = json_decode($userSetting);
        }

        return true;
    }

    /**
     * company_setting を session変数に読み出す（DB⇒Session）。
     *
     * Gen_Setting::loadSetting() の、company_setting 限定版。
     * index.php で実行。
     *
     * @access  public
     * @return  bool               成功ならtrue
     */
    static function loadCompanySetting()
    {
        global $gen_db;

        unset($_SESSION["gen_setting_company"]);

        $query = "select setting, company_setting_update_time from company_master";
        $obj = $gen_db->queryOneRowObject($query);

        // DB格納時（$gen_db->update）にエスケープされている文字を元に戻す
        $companySetting = str_replace("￥", "\\", $obj->setting);

        $_SESSION["gen_setting_company"] = json_decode($companySetting);
        
        $_SESSION["gen_setting_company_update_time"] = $obj->company_setting_update_time;

        return true;
    }

    /**
     * Session変数の設定値をDBに格納（Session⇒DB）
     *
     * Session変数の設定値をデータベースに格納する。
     * （アプリケーション設定値はcompany_master、ユーザー設定値はuser_masterに格納。）
     *
     * @access  public
     * @param   class   $gen_db
     * @return  bool               成功ならtrue
     */
    static function saveSetting()
    {
        global $gen_db;

        $user_id = Gen_Auth::getCurrentUserId($gen_db);

        $companySetting = json_encode($_SESSION["gen_setting_company"]);
        $userSetting = json_encode($_SESSION["gen_setting_user"]);

        $data = array(
            "setting" => $companySetting,
            "company_setting_update_time" => date('Y-m-d H:i:s'),
        );
        $where = "";
        $gen_db->update("company_master", $data, $where);

        if ($user_id == -1) {
            // admin
            $data = array("admin_setting" => $userSetting);
            $where = "";
            $gen_db->update("company_master", $data, $where);
        } else {
            // 一般
            $data = array("setting" => $userSetting);
            $where = "user_id = {$user_id}";
            $gen_db->update("user_master", $data, $where);
        }

        return true;
    }

}