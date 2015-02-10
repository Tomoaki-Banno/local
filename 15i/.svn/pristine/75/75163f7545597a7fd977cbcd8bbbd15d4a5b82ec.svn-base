<?php

class Config_Storage_Usage
{

    function execute(&$form)
    {
        $form['gen_pageTitle'] = _g("ストレージ使用量");
        $form['gen_pageHelp'] = _g("ストレージ");
        
        // --------- データストレージ ------------

        // データベース使用量（MB）
        // これで取得されるサイズはPostgreSQLが使用するテンポラリファイル等も含まれるため、
        // ユーザーに見せる値としては適切ではない。
        // $size = $gen_db->getDatabaseSize();

        // 最終バックアップファイルサイズ(MB)
        // 自動取得されたバックアップのファイルサイズを gen_server_info.yml から読み取る。
        $infoFile = ROOT_DIR . '/gen_server_info.yml';
        $size = '';     // サイズが非数値だとグラフは表示されない
        $bkTime = '';
        if (file_exists($infoFile)) {
            $serverInfo = Spyc::YAMLLoad($infoFile);
            $size = @$serverInfo['latest_backup']['filesize'];     // Byte
            if (is_numeric($size)) {
                $size = round($size / 1024 / 1024, 2);     // MB
            }
            $bkTime = @$serverInfo['latest_backup']['modify_time'];
        }
        $form['backupSize'] = $size;    // MB
        $form['lastBackupTime'] = $bkTime;

        // データストレージサイズ（バックアップファイルのサイズの上限）（MB）
        $limit = GEN_DATA_STORAGE_SIZE;
        if (!is_numeric($limit) || $limit == 0) {
            $limit = 50;
        }
        $form['backupLimit'] = $limit;

        // パーセント
        $per = round($size / $limit * 100);
        if ($per < 0)
            $per = 0;
        if ($per > 100)
            $per = 100;
        $form['backupPercent'] = $per;

        // --------- ファイルストレージ ------------
        
        // ファイルストレージ使用量（MB）
        $storage1 = new Gen_Storage("Files");
        $form['uploadFileSize'] = round($storage1->getDirSize() / 1024 / 1024, 2);
        $storage2 = new Gen_Storage("ChatFiles");
        $form['chatFileSize'] = round($storage2->getDirSize() / 1024 / 1024, 2);
        $storage3 = new Gen_Storage("ItemImage");
        $form['itemImageFileSize'] = round($storage3->getDirSize() / 1024 / 1024, 2);
        
        $form['fileTotalSize'] = $form['uploadFileSize'] + $form['chatFileSize'] + $form['itemImageFileSize'];

        // 一応、以下は使用量に含めないこととする。
        // Gen_Files::checkFileStorageSize() を参照。
//        $storage4 = new Gen_Storage("BackupData");
//        $form['backupFileSize'] = round($storage4->getDirSize() / 1024 / 1024, 2);
//        $storage5 = new Gen_Storage("CompanyLogo");
//        $form['companyLogoFileSize'] = round($storage5->getDirSize() / 1024 / 1024, 2);
//        $storage6 = new Gen_Storage("ProfileImage");
//        $form['profileImageFileSize'] = round($storage6->getDirSize() / 1024 / 1024, 2);
//        $storage7 = new Gen_Storage("ReportTemplates");
//        $form['templateFileSize'] = round($storage7->getDirSize() / 1024 / 1024, 2);

        // ファイルストレージサイズ（レコードやチャットの添付ファイルの合計サイズの上限）（MB）
        $storageSize = GEN_FILE_STORAGE_SIZE;
        if (!is_numeric($storageSize) || $storageSize == 0) {
            $storageSize = 50;
        }
        $form['fileStorageSize'] = $storageSize;

        // パーセント
        $form['uploadFilePercent'] = self::_calcPercent($form['uploadFileSize'], $storageSize);
        $form['chatFilePercent'] = self::_calcPercent($form['chatFileSize'], $storageSize);
        $form['itemImagePercent'] = self::_calcPercent($form['itemImageFileSize'], $storageSize);
        $form['fileTotalPercent'] = $form['uploadFilePercent'] + $form['chatFilePercent'] + $form['itemImagePercent'];
        if ($form['fileTotalPercent'] > 100) {
            $form['fileTotalPercent'] = 100;
            $form['itemImagePercent'] = 100 - $form['uploadFilePercent'] - $form['chatFilePercent'];
        }
        
        // SERVER_INFO_CLASS によって、S3にアクセスする場合とfiles_dirにアクセスする場合がある。
        if ($_SESSION['user_id'] == -1) {
            $storage = new Gen_Storage("Files");
            $form['adminMessage'] = "(adminのみ表示)<br>S3 or filds_dir： " . ($storage->isS3() ? "S3" : "files_dir(" . GEN_FILES_DIR . ")") . "<br>SERVER_INFO_CLASS： " . GEN_SERVER_INFO_CLASS;
        }
        
        return 'config_storage_usage.tpl';
    }
    
    private function _calcPercent($val, $max)
    {
        $per = round($val / $max * 100);
        if ($per < 0)
            $per = 0;
        if ($per > 100)
            $per = 100;
        return $per;
    }
}