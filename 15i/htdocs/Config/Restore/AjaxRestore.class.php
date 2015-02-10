<?php

class Config_Restore_AjaxRestore extends Base_AjaxBase
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
        // PHPがセーフモードの場合は効かない。
        // このスクリプトの中だけで有効。
        // 無制限にするのはやめたほうがいい。
        //
        // リストア処理（pg_restore + vacuum analyze）がこの秒数を超えるとエラーになる。
        // ただしpg_restoreはsystem()で実行しているため、このスクリプトはエラーになっても
        // restore自体は最後まで行われているはず。
        set_time_limit(1200);

        //-----------------------------------------------------------
        // 読み込み処理
        //-----------------------------------------------------------
        //
        $storage = new Gen_Storage("BackupData");
        $file = "Gen_15i_{$number}.bak";
        if (!$storage->exist($file)) {
            return 
                array(
                    'result' => 'fileMissing'
                );
        }

        $restoreFile = $storage->get($file);
        if ($gen_db->restore($restoreFile)) {
            // 成功
            $res = "success";
            $logMsg = _g("成功");
        } else {
            // 失敗
            $res = "failure";
            $logMsg = _g("エラー");
        }
        // データアクセスログ
        Gen_Log::dataAccessLog(_g("読み込み"), "", "$logMsg [" . _g("バックアップ番号") . "] " . $number);

        // 通知メール
        $title = ("バックアップの読み込み");
        $body = _g("バックアップの読み込みが行われました。") . "\n\n"
                . "[" . _g("実行日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("実行者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("バックアップ番号") . "] " . $number . "\n"
                . "";
        Gen_Mail::sendAlertMail('config_restore_restore', $title, $body);

        return
            array(
                'result' => $res,
            );
    }
}