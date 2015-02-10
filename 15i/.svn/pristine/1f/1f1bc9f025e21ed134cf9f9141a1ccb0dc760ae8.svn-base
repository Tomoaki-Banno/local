<?php

class Manufacturing_Mrp_Mrp
{

    function execute(&$form)
    {
        global $gen_db;

        //------------------------------------------------------
        //  リロードチェック
        //------------------------------------------------------
        // 実行後のList画面（URLはEntryになっている）でF5を押した場合や、実行後に他の画面に移ってから
        // 「戻る」で戻った場合に、計算が再実行されてしまう現象を防ぐ。

        if (!Gen_Reload::reloadCheck($form['gen_page_request_id']))
            return 'action:' . (isset($form['isMobile']) ? 'Mobile_Mrp_List' : 'Manufacturing_Mrp_List');

        //------------------------------------------------------
        //  日付チェック (OS Command Injection 対策)
        //------------------------------------------------------

        if (!Gen_String::isDateString($form['mrp_date'])) {
            return 'action:' . (isset($form['isMobile']) ? 'Mobile_Mrp_List' : 'Manufacturing_Mrp_List');
        }

        //------------------------------------------------------
        //  進捗ファイルの作成
        //------------------------------------------------------
        // 進捗ファイルが既存だったときはすでに実行中と判断し、なにもせず戻る。
        // ちなみに進捗ファイルの作成をLogic_Mrp内で行うようにすると、タイミングの制御が
        // 難しい。実行開始後、Logic内で進捗ファイルが作られる前にList画面で進捗チェックが
        // 行われて「未実行」と判断されてしまうことがある。
        // そのため、ここで行うようにした。

        $storage = new Gen_Storage("MRPProgress");
        if ($storage->exist('mrp_progress.dat')) {
            return 'action:' . (isset($form['isMobile']) ? 'Mobile_Mrp_List' : 'Manufacturing_Mrp_List');
        }

        $fp = fopen(GEN_TEMP_DIR . 'mrp_progress.dat', 'w');
        fputs($fp, date("Y-m-d H:i:s") . "," . $_SESSION['user_name'] . "," . _g("初期化処理中") . ",0");
        fclose($fp);
        $storage->put(GEN_TEMP_DIR . 'mrp_progress.dat', true);

        //------------------------------------------------------
        //  実行コマンドラインの準備
        //------------------------------------------------------
        // 計算対象開始日は「明日」に固定とする。理由については Logic_ExecMrp のコメント参照

        $startDate = date('Y-m-d', time() + (3600 * 24));
        $endDate = date('Y-m-d', strtotime($form['mrp_date']));
        $userName = "'" . $_SESSION['user_name'] . "'";
        $isNaiji = "false";
        if (isset($form['isNaiji'])) {
            if ($form['isNaiji']) {
                $isNaiji = "true";
            }
        }
        $isNonSafetyStock = "false";
        if (isset($form['isNonSafetyStock'])) {
            if ($form['isNonSafetyStock']) {
                $isNonSafetyStock = "true";
            }
        }
        $days = GEN_MRP_DAYS;

        //------------------------------------------------------
        //  計算結果確定日をクリア
        //------------------------------------------------------

        unset($_SESSION['gen_setting_user']->mrp_fix_date_manufacturing);
        unset($_SESSION['gen_setting_user']->mrp_fix_date_partner);
        unset($_SESSION['gen_setting_user']->mrp_fix_date_subcontract);
        unset($_SESSION['gen_setting_user']->mrp_fix_date_seiban);
        Gen_Setting::saveSetting($gen_db);

        //------------------------------------------------------
        //  実行
        //------------------------------------------------------
        // ExecMrp.phpを非同期実行する

        if (GEN_IS_WIN) {
            // For Win
            //    bgexecは、PHPスクリプトをバックグラウンド実行するためのフリーソフト。
            //    これがないと非同期にならない。
            //    09iでは、ExecMrpのパスを「LOGIC_DIR」で指定していたが、これだとパスにスペースが入る
            //    場合に正しく実行できない。（ダブルコーテーションで囲んでもダメ。system()の仕様？）
            //    それで、下記のように相対パス指定するようにした。
            $cmdLine = "..\Logic\ExecMrp.php {$startDate} {$endDate} {$isNaiji} {$isNonSafetyStock} {$userName} {$days}";
            $cmd = "bgexec.exe \"" . GEN_PHP_BIN_DIR . "php {$cmdLine} \"";
        } else {
            // For Linux
            //    リダイレクトすることにより非同期実行になる。また&をつけることでバックグラウンド実行になる。
            $cmdLine = LOGIC_DIR . "ExecMrp.php {$startDate} {$endDate} {$isNaiji} {$isNonSafetyStock} {$userName} {$days}";
            $cmd = GEN_PHP_BIN_DIR . "php {$cmdLine} > /dev/null &";
        }

        $res = system($cmd);

        //------------------------------------------------------
        // データアクセスログ
        //------------------------------------------------------
        $msg = "[" . _g("開始日") . "] {$startDate} [" . _g("終了日") . "] {$endDate}";
        $msg .= " [" . _g("内示モード") . "] " . ($isNaiji == "true" ? "on" : "off");
        $msg .= " [" . _g("安全在庫除外") . "] " . ($isNonSafetyStock == "true" ? "on" : "off");
        $mobile = ($form['gen_iPad'] ? " (". _g("iPad") .")" : ($form['gen_iPhone'] ? " (". _g("iPhone") .")" : ""));
        Gen_Log::dataAccessLog(_g("所要量計算") . ($form['gen_mobile'] ? " (" . _g("モバイル") . ")" : ""), _g("実行") . $mobile, $msg);

        //------------------------------------------------------
        //  戻り
        //------------------------------------------------------

        $form['gen_restore_search_condition'] = 'true';
        return 'action:' . (isset($form['isMobile']) ? 'Mobile_Mrp_List' : 'Manufacturing_Mrp_List');
    }

}