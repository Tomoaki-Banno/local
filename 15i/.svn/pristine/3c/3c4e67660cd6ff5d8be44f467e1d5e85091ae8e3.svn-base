<?php

class Config_Restore_Restore
{

    function execute(&$form)
    {
        // タブ、マイメニュー用
        $form['gen_pageTitle'] = _g("バックアップ読み込み");
        $form['gen_pageHelp'] = _g("バックアップ読み込み");

        // バックアップファイル情報
        $infoArr = array();
        for ($i = 1; $i <= GEN_BACKUP_MAX_NUMBER; $i++) {
            $infoArr[$i] = array("number" => $i, "date" => "", "size" => "", "remarks" => "");
        }

        $storage = new Gen_Storage("BackupData");
        $fileExist = false;
        $firstNum = 0;
        $remFile = $storage->get("gen_backup.dat");
        if (file_exists($remFile)) {
            $fp = fopen($remFile, 'r');
            $remArr = explode(",", fgets($fp));
            fclose($fp);
        }
        for ($i = 1; $i <= GEN_BACKUP_MAX_NUMBER; $i++) {
            if ($storage->exist("Gen_15i_{$i}.bak")) {
                $fileInfo = $storage->getFileInfo("Gen_15i_{$i}.bak");
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
                $fileExist = true;
                if ($firstNum == 0)
                    $firstNum = $i;
            }
        }
        $form['restoreFileInfo'] = $infoArr;
        $form['fileExist'] = $fileExist;
        $form['defaultNumber'] = $firstNum;

        return 'config_restore_restore.tpl';
    }

}