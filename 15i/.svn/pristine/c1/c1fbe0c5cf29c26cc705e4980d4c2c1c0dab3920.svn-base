<?php

class SystemUtility_DataClear_DataClear
{
    function reloadCheck(&$form)
    {
        // CSRF対策
        if (!Gen_Reload::reloadCheck($form['gen_page_request_id'])) {
            die('ページを再表示することはできません。');
        }
    }

    function execute(&$form)
    {
        set_time_limit(120);

        if (isset($form['log'])) {
            Logic_SystemUtility::clearLog();
            $logTitle = _g("内部ログクリア");
            
        } else {
            Logic_SystemUtility::clearTranData();
            $logTitle = _g("トランデータクリア");

            if (isset($form['item_master'])) {
                Logic_SystemUtility::clearItemMaster();
                $logTitle = _g("品目マスタクリア");
            }
            if (isset($form['bom_master'])) {
                Logic_SystemUtility::clearBomMaster();
                $logTitle = _g("構成表マスタクリア");
            }
        }

        // データアクセスログ
        Gen_Log::dataAccessLog($logTitle, "", "");

        $form['gen_done'] = "true";

        return 'action:Menu_Admin';
    }

}