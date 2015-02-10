<?php

class SystemUtility_AllColumnReset_AllColumnReset
{

    function execute(&$form)
    {
        global $gen_db;

        $query = "truncate search_column_info";
        $gen_db->query($query);

        $query = "truncate column_info";
        $gen_db->query($query);

        $query = "truncate page_info";
        $gen_db->query($query);

        $query = "truncate control_info";
        $gen_db->query($query);
        
        $query = "truncate dropdown_info";
        $gen_db->query($query);

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("表示設定クリア（全ユーザー）"), "", "");

        $form['gen_done'] = "true";

        return 'action:Menu_Admin';
    }

}