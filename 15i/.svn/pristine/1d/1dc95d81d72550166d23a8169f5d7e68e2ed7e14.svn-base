<?php

class Config_Backup_Backup
{

    function execute(&$form)
    {
        // タブ、マイメニュー用
        $form['gen_pageTitle'] = _g("バックアップ");
        $form['gen_pageHelp'] = _g("バックアップ");

        // バックアップファイル情報
        $infoArr = array();
        for ($i = 1; $i <= GEN_BACKUP_MAX_NUMBER; $i++) {
            $infoArr[$i] = array("number" => $i, "date" => "", "size" => "", "remarks" => "");
        }
        $storage = new Gen_Storage("BackupData");
        $remFile = $storage->get("gen_backup.dat");
        if (file_exists($remFile)) {
            $fp = fopen($remFile, 'r');
            $remArr = explode(",", fgets($fp));
            fclose($fp);
        } else {
            $remFile = GEN_TEMP_DIR . 'gen_backup.dat';
            if (file_exists($remFile)) {
                unlink($remFile);
            }
            touch($remFile);
            $storage->put($remFile, false, 'gen_backup.dat');
        }
        for ($i = 1; $i <= GEN_BACKUP_MAX_NUMBER; $i++) {
            if ($storage->exist("Gen_15i_{$i}.bak")) {
                $fileInfo = $storage->getFileInfo("Gen_15i_{$i}.bak");
                $infoArr[$i]["number"] = $i;
                $datetime = $fileInfo["LastModified"];
                $infoArr[$i]["date"] = date("Y-m-d", $datetime) . " (" . Gen_String::weekdayStr(date("Y-m-d", $datetime)) . ") " . date("H:i:s", $datetime);
                $size = $fileInfo["Size"];
                if (is_numeric($size))
                    $size = number_format($size / 1000) . " KB";
                $infoArr[$i]["size"] = $size;
                if (isset($remArr[$i - 1])) {
                    $remUserArr = explode(";", $remArr[$i - 1]);
                    $infoArr[$i]["remarks"] = $remUserArr[0];
                    $infoArr[$i]["user"] = @$remUserArr[1];
                } else {
                    $infoArr[$i]["remarks"] = '';
                    $infoArr[$i]["user"] = '';
                }
            }
        }
        $form['backupFileInfo'] = $infoArr;

        // デフォルト（あいている番号）
        $def = 1;
        for ($i = 1; $i <= GEN_BACKUP_MAX_NUMBER; $i++) {
            if ($infoArr[$i]["date"] == "") {
                $def = $i;
                break;
            }
        }
        $form['defaultNumber'] = $def;

        return 'config_backup_backup.tpl';
    }

}