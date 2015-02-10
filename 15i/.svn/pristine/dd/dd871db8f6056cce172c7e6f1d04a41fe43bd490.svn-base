<?php

class Master_User_AjaxAlterRestricted extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;
        
        // この機能はadmin専用
        if (Gen_Auth::getCurrentUserId() != -1) {        
            return;
        }
        
        if (!isset($form['userId']) || !Gen_String::isNumeric($form['userId'])) {
            return;
        }
        
        $gen_db->begin();
        
        // ユーザータイプ（一般 <-> 機能限定）の切り替え。
        // 同時にすべての画面へのアクセス権を削除。一般ユーザーを機能限定に降格したときのため。
        // 本来、限定機能へのアクセス権だけは残してもいいかもしれないが手抜き。（画面上で再設定してもらう）
        $query = "
            update user_master set restricted_user = not restricted_user where user_id = '{$form['userId']}';
            delete from permission_master where user_id = '{$form['userId']}';
        ";
        $gen_db->query($query);
            
        $gen_db->commit();

        $obj['status'] = "success";
        return $obj;
    }

}