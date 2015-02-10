<?php

class Config_AdminBackup_Backup
{

    function execute(&$form)
    {
        // 以前は、Config_AdminBackup_AjaxBackup においてバックアップをWorkに作成し、
        // config_adminbackup_backup.tpl でそれをクライアントにダウンロードさせていた。
        // しかしその方式では Workにバックアップファイルが残るため、ファイル名さえわかれば
        // だれでもファイルを取得できる状態だった。
        // 13iの途中からは負荷分散対応としてWorkの使用をやめ、 DownloadFile() を使用する
        // ようにしたため、結果として上記の問題も解消された。
        if (isset($form['doBackup'])) {
            global $gen_db;
            
            // データベースをバックアップ（GEN_TEMP_DIR）
            $backupFile = $gen_db->backup();
            if ($backupFile) {
                // 成功時
                $filename = "Gen_15i_" . date("Y_m_d_H_i_s") . ".bak";
                
                // データアクセスログ
                Gen_Log::dataAccessLog(_g("バックアップ(admin)"), "", "[" . _g("ファイル名") . "] " . $filename);
                
                // バックアップデータを送信
                Gen_Download::DownloadFile($backupFile, $filename);
            } else {
                // 失敗時
                $form['result'] = "fail";
            }
        }

        return 'config_adminbackup_backup.tpl';
    }

}