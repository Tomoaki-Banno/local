<?php

class SystemUtility_AllClear_AllClear
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
        global $gen_db;
        
        // サンプルデータ削除モード　ag.cgi?page=ProjectDocView&pPID=1574&pbid=221340
        //  オールクリアとの違いは
        //  ・user_master、permission_master を削除しない
        //  ・ファイル類（S3/files_dir）を削除する （ただし一部のカテゴリは除外。下の削除処理部のコメントを参照）
        $isSampleData = isset($form['sampledata']);

        set_time_limit(600);

        // sqlファイルを取得
        $dir_name = ROOT_DIR . 'gen_query' . SEPARATOR;
        $tableArr = array();
        $masterArr = array();
        $afterArr = array();
        $sessionSql = null;
        foreach (glob("{$dir_name}*.sql", GLOB_NOSORT) as $value) {
            $value = basename($value);
            if ($value == 'company_master.sql') {
                continue;
            }
            if ($isSampleData) {
                // サンプルデータ削除モードでは user_master、permission_master を削除しない
                if ($value == 'user_master.sql' || $value == 'permission_master.sql') {
                    continue;
                }
            }
            if (preg_match('/_master.sql/', $value)) {
                $masterArr[] = $value;
                continue;
            }
            if ($value == 'number_table.sql') {
                $afterArr[] = $value;
                continue;
            }
            if ($value == 'session_table.sql') {
                $sessionSql = $value;
                continue;
            }
            $tableArr[] = $value;
        }

        $gen_db->begin();
        
        // トランザクション系
        foreach ($tableArr as $value) {
            $res = $gen_db->executeQueryFile($dir_name, $value);
        }
        // マスタ系
        foreach ($masterArr as $value) {
            $res = $gen_db->executeQueryFile($dir_name, $value);
        }
        // その他（各テーブル作成後に実行が必要なテーブル）
        foreach ($afterArr as $value) {
            $res = $gen_db->executeQueryFile($dir_name, $value);
        }
        // セッションテーブル
        if (isset($sessionSql)) {
            $res = $gen_db->executeQueryFile($dir_name, $sessionSql);
        }
     
        // サンプルデータ削除モードのみ、ファイル類（S3/files_dir）の削除を行う
        if ($isSampleData) {        
            // ファイル類（S3、files_dir）
            //  CompanyLogo、ProfileImage、ReportTemplates、MRPProgress、JSGetText は削除しない。
            //  ReportTemplates については、本来は削除したほうがいい。しかしサブディレクトリがあるために削除が難しく
            //  （Gen_Strorageが複数階層の再帰処理に対応していない。またディレクトリごと削除しようとするとパーミッ
            //  ションの問題が発生することがある）処理から外した。
            self::_deleteFiles("Files");
            self::_deleteFiles("ItemImage");
            self::_deleteFiles("ChatFiles");
            self::_deleteFiles("BackupData");
        }

        $gen_db->commit();

        return 'action:Logout';
    }
    
    // ファイル削除。
    // サブディレクトリに対応していないので、ReportTemplatesには使用できないことに注意。
    private function _deleteFiles($category)
    {
        $storage = new Gen_Storage($category);
        $arr = $storage->listFiles();
        foreach($arr as $file) {
             $storage->delete($file);
        }
    }
}