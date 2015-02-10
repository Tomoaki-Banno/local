<?php

class Config_AdminRestore_Restore
{

    function execute(&$form)
    {
        global $gen_db;

        // アップロードされたファイルのセキュリティチェック
        if (is_uploaded_file(@$_FILES['restoreFile']['tmp_name']) && @$_FILES['restoreFile']['size'] > 0) {
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
            set_time_limit(600);

            //-----------------------------------------------------------
            // リストア処理
            //-----------------------------------------------------------

            if ($gen_db->restore($_FILES['restoreFile']['tmp_name'])) {
                // 成功
                $form['gen_restore_done'] = true;
                $logMsg = _g("成功");
            } else {
                // 失敗
                $form['gen_restore_error'] = true;
                unset($form['gen_restore_done']);
                $logMsg = _g("失敗");
            }

            // データアクセスログ
            // basename() は使用しない。日本語の文字化け問題があるため。
            // アップロードされたファイル名の機種依存文字の削除とサニタイジングの処理を行うようにした。
            $fileName = $_FILES['restoreFile']['name'];
            $fileName = Gen_String::cutSjisDependencyChar($fileName);
            $fileName = $gen_db->quoteParam($fileName);
            Gen_Log::dataAccessLog(_g("読み込み"), "", "$logMsg [" . _g("ファイル名") . "] $fileName");
            
            // 復元成功すると、session_idは存在しているがuser_idがとれない状態になり、index.phpでエラーになる
            if (isset($form['gen_restore_done'])) {
                unset($_SESSION['session_id']);
            }
        } else {
            unset($form['gen_size_error']);
            if (isset($form['MAX_FILE_SIZE'])) {  // これがセットされているということはポストバック
                $form['gen_size_error'] = true;
            }
            unset($form['gen_restore_error']);
            unset($form['gen_restore_done']);
        }

        // 開発版はバックアップデータ読み込み禁止
        if (GEN_SERVER_INFO_CLASS == 90) {
            $form['gen_msg_noEscape'] = "<span style='background-color:#ffcc99'>" . _g("開発版") . " ： " . _g("バックアップデータ読み込み禁止") . "</span><br>";
            $form['gen_javascript_noEscape'] = "
                jQuery.event.add(window, \"load\", function() {
                    $('#restoreFile').attr('disabled', true);
                    $('#restoreButton').attr('disabled', true);
                });
            ";
        }

        $form['gen_max_upload_file_size'] = GEN_MAX_UPLOAD_FILE_SIZE;

        return 'config_adminrestore_restore.tpl';
    }

}