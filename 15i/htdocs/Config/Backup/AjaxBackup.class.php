<?php

class Config_Backup_AjaxBackup extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

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
        // バックアップ処理
        //-----------------------------------------------------------
        //
        $storage = new Gen_Storage("BackupData");

        // バックアップファイル名を決める
        $file = "Gen_15i_{$number}.bak";

        // 同一ナンバーの既存ファイルがあるなら、リネームしておく （バックアップ失敗時に復旧するため、ここでは消さない）
        $oldExists = $storage->exist($file);
        if ($oldExists) {
            $storage->rename($file, $file . "_oldbackup");
        }

        // データベースをバックアップ。
        $backupFile = $gen_db->backup($file);
        if ($backupFile) {
            // 成功時
            $res = "success";
            // バックアップファイルをディレクトリに移動
            $storage->put($backupFile, true);
            unlink($backupFile);
            
            // 既存バックアップファイルを削除
            if ($oldExists) {
                 $storage->delete($file . "_oldbackup");
            }
            // 備考記録ファイルを更新
            $remFile = $storage->get("gen_backup.dat");
            if (file_exists($remFile)) {
                $fp = fopen($remFile, 'r');
                $logArr = explode(",", fgets($fp));
                fclose($fp);
            } else {
                // gen_backup.dat はバックアップ画面表示時に作成されるので、これは起こらないはず
                throw new Exception("gen_files の BackupData に gen_backup.dat が存在していません。");
            }
            // デリミタとして使われる「,」「;」は大文字に変換しておく
            $logArr[$number - 1] = str_replace(',', '、', str_replace(';', '；', @$form['remarks'])) . ';' . $_SESSION['user_name'];
            $data = "";
            for ($i = 1; $i <= GEN_BACKUP_MAX_NUMBER; $i++) {
                if ($i != 1)
                    $data .=",";
                $data .= @$logArr[$i - 1];
            }
            file_put_contents($remFile, $data, LOCK_EX);   // LOCK_EXはPHP5.1以降
            $storage->put($remFile, true);
        } else {
            // 失敗時
            // 既存ファイルを復旧
            $res = "failure";
            if ($oldExists) {
                if ($storage->exist($file)) {
                    $storage->delete($file);
                }
                $storage->rename($file . "_oldbackup", $file);
            }
        }

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("バックアップ"), "", "[" . _g("バックアップ番号") . "] " . $number);

        // 通知メール
        $title = ("バックアップ");
        $body = _g("バックアップが行われました。") . "\n\n"
                . "[" . _g("実行日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("実行者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("バックアップ番号") . "] {$number}\n"
                . "";
        Gen_Mail::sendAlertMail('config_backup_backup', $title, $body);

        return
            array(
                'result' => $res,
            );
    }

}