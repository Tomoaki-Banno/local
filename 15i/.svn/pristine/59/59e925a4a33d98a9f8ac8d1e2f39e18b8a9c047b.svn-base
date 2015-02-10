<?php

class Manufacturing_Mrp_AjaxMrpProgress extends Base_AjaxBase
{

    // mrp_progressテーブルより、現在の所要量計算の進捗状況を取得してテキストとして返す。
    // 引数：なし
    // 戻り値：カンマ区切り文字列
    //        計算実行中：「true」,開始時刻,開始ユーザー,状況,進捗%）
    //        計算非実行：「false」,最終実行時刻,最終実行ユーザー

    function _execute(&$form)
    {
        global $gen_db;

        $obj = "";
        // 進捗ファイルが存在し、データが書き込まれていたときは、そのデータを返す。
        // タイミングによってファイルの存在は確認されたがデータが取れないということがあるので、
        // 慎重な取り方をしている。
        $storage = new Gen_Storage("MRPProgress");
        $pFile = $storage->get('mrp_progress.dat');
        if (file_exists($pFile)) {
            $data = "";
            $fp = fopen($pFile, 'r');
            if ($fp != false) {
                flock($fp, LOCK_SH);
                $data = fgets($fp);
                flock($fp, LOCK_UN);
                if ($data != "") {
                    $obj = array(
                        'doing' => "true",
                        'data' => $data,
                    );
                }
            }
            fclose($fp);
            $storage->put($pFile, true);
        }

        // 進捗ファイルがなかったときは所要量計算未実行と判断する。
        if (!is_array($obj)) {
            // 最終実行時刻
            $query = "select last_mrp_date, last_mrp_user from company_master";
            $res = $gen_db->queryOneRowObject($query);
            $lastDate = $res->last_mrp_date;
            $lastUser = $res->last_mrp_user;
            if ($lastDate != '') {
                // 下記を有効にすれば曜日が表示されるようになるが、時刻のうしろに
                // 表示されてしまうので不自然
                //$lastDate .= "(" . Gen_String::weekdayStr($lastDate) . ")";
            } else {
                $lastDate = _g("なし");
            }
            if ($lastUser == '') {
                $lastUser = _g("なし");
            }
            $obj = array(
                'doing' => "false",
                'data' => $lastDate . "," . $lastUser,
            );
        }
        
        return $obj;
    }

}