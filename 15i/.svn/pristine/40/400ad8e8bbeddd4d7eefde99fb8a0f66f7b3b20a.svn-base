<?php

class Config_Backup_AjaxBackupDelete extends Base_AjaxBase
{

    function _execute(&$form)
    {
        $number = @$form['backup_number'];
        if (!is_numeric($number)) {
            return;
        }
        if ($number < 1 || $number > GEN_BACKUP_MAX_NUMBER) {
            return;
        }

        //-----------------------------------------------------------
        // 実行時間制限を変更
        //-----------------------------------------------------------
        //
        // PHPがセーフモードの場合は効かない。
        // このスクリプトの中だけで有効。
        // 何かのことでpg_dumpが無応答になることがあるので、無制限にするのは
        // やめたほうがいい。
        set_time_limit(60);

        //-----------------------------------------------------------
        // バックアップ削除
        //-----------------------------------------------------------
        //
        // バックアップ保存ディレクトリ
        $storage = new Gen_Storage("BackupData");

        // バックアップファイル名を決める
        $file = "Gen_15i_{$number}.bak";

        // 削除
        $storage->delete($file);
        $res = "success";

        // 備考記録ファイルを更新
        $remFile = $storage->get("gen_backup.dat");
        if (file_exists($remFile)) {
            $fp = fopen($remFile, 'r');
            $logArr = explode(",", fgets($fp));
            fclose($fp);
        } else {
            $logArr = array();
        }
        $logArr[$number - 1] = "";
        $data = "";
        for ($i = 1; $i <= GEN_BACKUP_MAX_NUMBER; $i++) {
            if ($i != 1)
                $data .=",";
            $data .= @$logArr[$i - 1];
        }
        file_put_contents($remFile, $data, LOCK_EX);   // LOCK_EXはPHP5.1以降
        $storage->put($remFile, true);

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("バックアップ削除"), "", "[" . _g("バックアップ番号") . "] " . $number);

        return
            array(
                'result' => $res,
            );
    }

}